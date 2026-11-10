<?php

namespace App\Http\Controllers;

use App\Models\Participation;
use App\Models\SensorSample;
use Illuminate\Http\Request;

class SensorSampleController extends Controller
{
    public function store(Request $request, Participation $participation)
    {
        abort_if($participation->user_id !== auth()->id(), 403);

        $regras = [
            'samples'               => ['required', 'array', 'max:100'],
            'samples.*.sessao'      => ['required', 'string', 'max:64'],
            'samples.*.question_id' => ['nullable', 'integer', 'exists:questions,id'],
            'samples.*.t_ms'        => ['required', 'integer', 'min:0'],
            'samples.*.alvo_lat'    => ['required', 'numeric', 'between:-90,90'],
            'samples.*.alvo_lng'    => ['required', 'numeric', 'between:-180,180'],
            'samples.*.lat'         => ['required', 'numeric', 'between:-90,90'],
            'samples.*.lng'         => ['required', 'numeric', 'between:-180,180'],
            'samples.*.gps_t_ms'    => ['nullable', 'integer', 'min:0'],
            'samples.*.bussola_n'   => ['nullable', 'integer', 'min:0'],
        ];

        // o resto dos campos é numérico e pode vir null
        foreach (SensorSample::CAMPOS as $campo) {
            $regras["samples.*.$campo"] ??= ['nullable', 'numeric'];
        }

        $dados = $request->validate($regras);

        $agora = now();
        $linhas = array_map(function (array $s) use ($participation, $agora) {
            $linha = ['participation_id' => $participation->id];
            foreach (SensorSample::CAMPOS as $campo) {
                $linha[$campo] = $s[$campo] ?? null;
            }
            $linha['bussola_n']  = $linha['bussola_n'] ?? 0;
            $linha['created_at'] = $agora;
            $linha['updated_at'] = $agora;

            return $linha;
        }, $dados['samples']);

        SensorSample::insert($linhas); // um único INSERT para o lote inteiro

        return response()->noContent(); // 204
    }
}
