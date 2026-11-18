<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>{{ $participation->activity->name }} · RA</title>
    @vite('resources/js/ar.js')
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #000; font-family: system-ui, sans-serif; touch-action: manipulation; overscroll-behavior: none; }
        #ar-canvas { position: fixed; inset: 0; width: 100%; height: 100%; touch-action: none; }
        #hud {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            padding: calc(env(safe-area-inset-top, 0px) + 8px) 12px 8px;
            background: rgba(0,0,0,.55); color: #fff; font-size: 14px;
            display: flex; justify-content: space-between; gap: 8px;
        }
        #hud a { color: #93c5fd; }
        #btn-responder {
            position: fixed; left: 50%; transform: translateX(-50%); z-index: 20;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 88px);
            padding: 14px 28px; font-size: 16px; font-weight: 600; border-radius: 999px;
            background: #16a34a; color: #fff; text-decoration: none; white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0,0,0,.35);
        }
        #btn-responder[hidden] { display: none; }
    </style>
</head>
<body>
    <div id="ar-root"
         data-state-url="{{ route('play.state', $participation, false) }}"
         data-samples-url="{{ route('play.samples.store', $participation, false) }}">
        <canvas id="ar-canvas"></canvas>

        <div id="hud">
            <div>
                <div id="step"></div>
                <div id="status">Iniciando…</div>
            </div>
            <a href="{{ route('play.show', $participation, false) }}">Sair</a>
        </div>

        <button id="btn-sensores" type="button"
        style="position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:20;
            padding:14px 24px; font-size:16px; border-radius:8px; border:0; background:#2563eb; color:#fff;">
            Ativar sensores
        </button>

        {{-- aparece só quando o aluno entra no raio do ponto; leva para a página da pergunta --}}
        <a id="btn-responder" hidden href="{{ route('play.question', $participation, false) }}">
            Responder pergunta
        </a>
    </div>
</body>
</html>