<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $participation->activity->name }} · RA</title>
    @vite('resources/js/ar.js')
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #000; font-family: system-ui, sans-serif; }
        #ar-canvas { position: fixed; inset: 0; width: 100%; height: 100%; }
        #hud {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            padding: calc(env(safe-area-inset-top, 0px) + 8px) 12px 8px;
            background: rgba(0,0,0,.55); color: #fff; font-size: 14px;
            display: flex; justify-content: space-between; gap: 8px;
        }
        #hud a { color: #93c5fd; }
        #panel {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 10; display: none;
            padding: 16px 16px calc(env(safe-area-inset-bottom, 0px) + 16px);
            background: #fff; border-radius: 16px 16px 0 0; max-height: 60%; overflow-y: auto;
        }
        #panel h3 { margin: 0 0 8px; font-size: 16px; }
        #panel button {
            display: block; width: 100%; text-align: left; margin-top: 8px; padding: 12px;
            border: 1px solid #d1d5db; border-radius: 8px; background: #f9fafb; font-size: 15px;
        }
    </style>
</head>
<body>
    <div id="ar-root" data-state-url="{{ route('play.state', $participation, false) }}">
        <canvas id="ar-canvas"></canvas>

        <div id="hud">
            <div>
                <div id="step"></div>
                <div id="status">Iniciando…</div>
            </div>
            <a href="{{ route('play.show', $participation, false) }}">Sair</a>
        </div>

        <div id="panel">
            <h3 id="q-text"></h3>
            <div id="q-answers"></div>
        </div>
    </div>
</body>
</html>