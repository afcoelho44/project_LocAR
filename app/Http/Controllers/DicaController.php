<?php

namespace App\Http\Controllers;

use App\Models\Dica;
use App\Models\Participation;
use App\Support\Proximidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dica de proximidade (quente / morno / frio) da tela de RA.
 *
 * O celular manda as últimas leituras do GPS; a coordenada do objeto vem do banco
 * (etapa atual da participação). Quem classifica depende de IA_MODO:
 *   - ia       : componente de IA (API Flask, repo componente_IA)
 *   - regra    : só a regra de distância (com a distância filtrada pela API, se IA_URL estiver definida)
 *   - alternar : metade das etapas com a IA e metade com a regra, alternando a ordem
 *                entre participantes (para comparar as duas no teste piloto)
 * Se a IA não estiver configurada, falhar ou demorar, usa a regra (fonte = 'regra')
 * — a dica nunca trava o jogo. Cada dica fica registrada na tabela `dicas`.
 *
 * Filtro de Kalman (IA_FILTRO = kalman | bruto): quem filtra é a API, que guarda o histórico
 * da sessão (participação + etapa). Por isso a API é chamada nas duas etapas (IA e regra):
 * o filtro não perde leituras e a regra usa a mesma distância que a IA (classe_regra).
 * Se a API não responder, a regra cai na distância bruta calculada aqui (filtro = 'bruto').
 */
class DicaController extends Controller
{
    /** depois de uma falha, fica este tempo sem tentar a IA (evita esperar o timeout a cada segundo) */
    private const PAUSA_APOS_FALHA_S = 30;

    public function classificar(Request $request, Participation $participation)
    {
        abort_if($participation->user_id !== auth()->id(), 403);
        abort_if($participation->isFinished(), 409, 'Participação já concluída.');

        $question = $participation->currentQuestion;
        abort_if($question === null, 409, 'Sem etapa atual.');

        $dados = $request->validate([
            'leituras'            => ['required', 'array', 'min:1', 'max:20'],
            'leituras.*.lat'      => ['required', 'numeric', 'between:-90,90'],
            'leituras.*.lng'      => ['required', 'numeric', 'between:-180,180'],
            'leituras.*.accuracy' => ['nullable', 'numeric', 'min:0'],
            'leituras.*.speed'    => ['nullable', 'numeric', 'min:0'],
            'leituras.*.t_ms'     => ['nullable', 'numeric'],
        ]);

        $leituras = array_map(fn ($l) => [
            'lat'      => (float) $l['lat'],
            'lng'      => (float) $l['lng'],
            'accuracy' => isset($l['accuracy']) ? (float) $l['accuracy'] : null,
            'speed'    => isset($l['speed']) ? (float) $l['speed'] : null,
            't_ms'     => isset($l['t_ms']) ? (float) $l['t_ms'] : null,
        ], array_values($dados['leituras']));

        $ultima = end($leituras);
        $dist = Proximidade::distancia($ultima['lat'], $ultima['lng'], (float) $question->lat, (float) $question->lng);
        $braco = $this->braco($participation, (int) $question->position);

        $resultado = ['classe' => Proximidade::classe($dist), 'fonte' => 'regra', 'dist_m' => round($dist, 2)];
        $extra = ['filtro' => 'bruto', 'dist_bruto_m' => round($dist, 2), 'dist_kalman_m' => null];

        $ia = config('services.ia.url')
            ? $this->perguntarIa($leituras, $participation, $question, $request->userAgent())
            : null;
        if ($ia !== null) {
            $resultado = [
                // etapa da regra: os limites na distância filtrada que a própria API calculou
                'classe' => $braco === 'ia' ? $ia['classe'] : $ia['classe_regra'],
                'fonte'  => $braco === 'ia' ? $ia['fonte'] : 'regra',
                'dist_m' => $ia['dist_m'],
            ];
            $extra = ['filtro' => $ia['filtro'], 'dist_bruto_m' => $ia['dist_bruto_m'], 'dist_kalman_m' => $ia['dist_kalman_m']];
        }

        Dica::create([
            'participation_id' => $participation->id,
            'question_id'      => $question->id,
            'braco'            => $braco,
            'fonte'            => $resultado['fonte'],
            'classe'           => $resultado['classe'],
            'dist_m'           => $resultado['dist_m'],
            'accuracy'         => $ultima['accuracy'],
            ...$extra,
        ]);

        // o braço não vai para o celular: o aluno não sabe se a dica veio da IA ou da regra
        return response()->json($resultado);
    }

    /** 'ia' ou 'regra' para esta etapa, conforme IA_MODO */
    private function braco(Participation $participation, int $posicao): string
    {
        $modo = config('services.ia.modo');
        if ($modo === 'regra' || ! config('services.ia.url')) {
            return 'regra';
        }
        if ($modo === 'alternar') {
            // ids consecutivos começam por braços diferentes; etapas alternam
            return ($participation->id + $posicao) % 2 === 0 ? 'ia' : 'regra';
        }

        return 'ia';
    }

    /** resposta da IA (classe, classe_regra, distâncias), ou null se ela estiver fora do ar / responder errado */
    private function perguntarIa(array $leituras, Participation $participation, $question, ?string $userAgent): ?array
    {
        if (Cache::has('ia_fora_do_ar')) {
            return null;
        }

        try {
            $resposta = Http::timeout(config('services.ia.timeout'))
                ->acceptJson()
                ->post(rtrim(config('services.ia.url'), '/') . '/classificar', [
                    'objeto'      => ['lat' => (float) $question->lat, 'lng' => (float) $question->lng],
                    'leituras'    => $leituras,
                    'dispositivo' => $this->dispositivo($userAgent),
                    // histórico do Kalman: uma sessão por participação e etapa
                    'sessao'      => 'p' . $participation->id . '-q' . $question->id,
                    'filtro'      => config('services.ia.filtro'),
                ]);

            $classe = $resposta->json('classe');
            $classeRegra = $resposta->json('classe_regra');
            if ($resposta->ok() && in_array($classe, Proximidade::CLASSES, true)
                && in_array($classeRegra, Proximidade::CLASSES, true)) {
                $num = fn ($v) => $v === null ? null : round((float) $v, 2);

                return [
                    'classe'        => $classe,
                    'classe_regra'  => $classeRegra,
                    // fonte 'regra' quando a própria API caiu na regra (longe demais do treino)
                    'fonte'         => $resposta->json('fonte') === 'regra' ? 'regra' : 'ia',
                    'filtro'        => $resposta->json('filtro') === 'kalman' ? 'kalman' : 'bruto',
                    'dist_m'        => $num($resposta->json('dist_m')),
                    'dist_bruto_m'  => $num($resposta->json('dist_bruto_m')),
                    'dist_kalman_m' => $num($resposta->json('dist_kalman_m')),
                ];
            }

            Log::warning('IA respondeu sem classe válida', ['status' => $resposta->status()]);
        } catch (\Throwable $e) {
            Log::warning('IA fora do ar: ' . $e->getMessage());
        }

        Cache::put('ia_fora_do_ar', true, self::PAUSA_APOS_FALHA_S);

        return null;
    }

    /** mesmo agrupamento do analisar.py (coluna dispositivo_curto) */
    private function dispositivo(?string $userAgent): string
    {
        $ua = $userAgent ?? '';

        return match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'Android') => 'Android',
            default => 'outro',
        };
    }
}
