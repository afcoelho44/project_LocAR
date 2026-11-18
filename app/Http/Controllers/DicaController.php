<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ponte entre a tela de RA e o componente de IA (API Flask, repo componente_IA).
 *
 * O celular manda a distância bruta do GPS até o objeto; a IA devolve a
 * distância corrigida. Se a IA não estiver configurada, falhar ou demorar,
 * devolve a própria distância do GPS (fonte = 'gps') — a dica nunca trava o jogo.
 */
class DicaController extends Controller
{
    /** depois de uma falha, fica este tempo sem tentar a IA (evita esperar o timeout a cada segundo) */
    private const PAUSA_APOS_FALHA_S = 30;

    public function distancia(Request $request, Participation $participation)
    {
        abort_if($participation->user_id !== auth()->id(), 403);

        $dados = $request->validate([
            'd_gps_m'  => ['required', 'numeric', 'min:0', 'max:100000'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $gps = ['dist_m' => round((float) $dados['d_gps_m'], 2), 'fonte' => 'gps'];

        $url = config('services.ia.url');
        if (! $url || Cache::has('ia_fora_do_ar')) {
            return response()->json($gps);
        }

        try {
            $resposta = Http::timeout(config('services.ia.timeout'))
                ->acceptJson()
                ->post(rtrim($url, '/') . '/corrigir', [
                    'd_gps_m'     => (float) $dados['d_gps_m'],
                    'accuracy'    => isset($dados['accuracy']) ? (float) $dados['accuracy'] : null,
                    'ambiente'    => config('services.ia.ambiente'),
                    'dispositivo' => $this->dispositivo($request->userAgent()),
                ]);

            $dist = $resposta->json('dist_m');
            if ($resposta->ok() && is_numeric($dist)) {
                return response()->json(['dist_m' => round(max(0, (float) $dist), 2), 'fonte' => 'ia']);
            }

            Log::warning('IA respondeu sem dist_m', ['status' => $resposta->status()]);
        } catch (\Throwable $e) {
            Log::warning('IA fora do ar: ' . $e->getMessage());
        }

        Cache::put('ia_fora_do_ar', true, self::PAUSA_APOS_FALHA_S);

        return response()->json($gps);
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
