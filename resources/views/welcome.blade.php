<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Caça ao Tesouro em RA</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">
    <div class="min-h-screen flex flex-col">

        <header class="max-w-5xl w-full mx-auto px-6 py-5 flex justify-between items-center">
            <span class="font-semibold text-gray-800">Caça ao Tesouro RA</span>
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 underline">Ir para o painel</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Entrar</a>
            @endauth
        </header>

        <main class="flex-1 max-w-5xl w-full mx-auto px-6">
            <section class="py-10 sm:py-16 text-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Realidade Aumentada por localização</p>
                <h1 class="mt-3 text-4xl sm:text-5xl font-bold text-gray-900">Caça ao Tesouro em RA</h1>
                <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">
                    Ande até os pontos marcados no mapa, encontre o objeto 3D pela câmera do celular
                    e responda à pergunta para desbloquear a próxima pista.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg">Continuar</a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg">Entrar</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="px-6 py-3 bg-white border border-gray-300 text-gray-800 font-semibold rounded-lg">Sou aluno, criar conta</a>
                        @endif
                    @endauth
                </div>
            </section>

            <section class="pb-16">
                <h2 class="text-center text-sm font-semibold uppercase tracking-wide text-gray-500">Como funciona</h2>
                <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['Vá até o ponto', 'Abra o modo RA e caminhe até o local da etapa. A tela mostra a distância até o ponto.'],
                        ['Encontre o objeto', 'Aponte a câmera ao redor: o objeto 3D aparece no local cadastrado pelo professor.'],
                        ['Responda e avance', 'Ao chegar, toque em "Responder pergunta". Acertou? Você recebe a pista do próximo ponto.'],
                    ] as $i => [$title, $text])
                        <li class="bg-white rounded-lg shadow-sm p-5">
                            <span class="inline-flex w-8 h-8 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-semibold">{{ $i + 1 }}</span>
                            <h3 class="mt-3 font-semibold text-gray-800">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-8 text-center text-xs text-gray-500">
                    Use o celular com GPS e câmera liberados, de preferência ao ar livre.
                </p>
            </section>
        </main>

        <footer class="py-6 text-center text-xs text-gray-500">
            Prova de conceito · TEMTC II · UDESC
        </footer>
    </div>
</body>
</html>
