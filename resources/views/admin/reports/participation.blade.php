@php use App\Support\Duracao; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participation->user?->name ?? '(aluno removido)' }}</h2>
                <p class="text-sm text-gray-600">{{ $participation->activity->name }}</p>
            </div>
            @if ($participation->isFinished())
                <span class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">Concluiu</span>
            @else
                <span class="px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800">Em andamento</span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $end = $participation->finished_at ?? now();
                $totalSeconds = $participation->started_at?->diffInSeconds($end);
                $errors = $participation->attempts->where('is_correct', false)->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ([
                    ['Início', $participation->started_at?->format('d/m H:i:s') ?? '–'],
                    ['Fim', $participation->finished_at?->format('d/m H:i:s') ?? '–'],
                    ['Tempo total', Duracao::format($totalSeconds)],
                    ['Erros · amostras', $errors . ' · ' . $participation->samples_count],
                ] as [$label, $value])
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            @foreach ($participation->activity->questions as $q)
                @php
                    $step = $steps->get($q->id);
                    $tries = $attempts->get($q->id, collect());
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-5 {{ $step ? '' : 'opacity-60' }}">
                    <div class="flex flex-wrap justify-between gap-2">
                        <h3 class="font-semibold text-gray-800">{{ $q->position }}. {{ $q->description }}</h3>
                        @if (! $step)
                            <span class="text-xs text-gray-500">não liberada</span>
                        @elseif ($step->completed_at)
                            <span class="text-xs text-green-700">acertou</span>
                        @elseif ($step->arrived_at)
                            <span class="text-xs text-amber-700">no ponto, respondendo</span>
                        @else
                            <span class="text-xs text-amber-700">a caminho do ponto</span>
                        @endif
                    </div>

                    @if ($step)
                        <dl class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Deslocamento</dt><dd class="tabular-nums">{{ Duracao::format($step->travelSeconds()) }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Resposta</dt><dd class="tabular-nums">{{ Duracao::format($step->answerSeconds()) }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Total da etapa</dt><dd class="tabular-nums">{{ Duracao::format($step->totalSeconds()) }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Erros</dt><dd class="tabular-nums {{ $step->wrong_attempts ? 'text-red-600 font-semibold' : '' }}">{{ $step->wrong_attempts }}</dd></div>
                        </dl>

                        @if ($tries->isNotEmpty())
                            <ol class="mt-4 space-y-1 text-sm">
                                @foreach ($tries as $t)
                                    <li class="flex gap-3">
                                        <span class="text-xs text-gray-500 tabular-nums w-16">{{ $t->created_at->format('H:i:s') }}</span>
                                        <span class="{{ $t->is_correct ? 'text-green-700' : 'text-red-600' }}">
                                            {{ $t->is_correct ? '✓' : '✗' }}
                                            {{ $t->answer ? $t->answer->alternative . ') ' . $t->answer->description : '(alternativa removida)' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    @endif
                </div>
            @endforeach

            <a href="{{ route('admin.reports.activity', $participation->activity) }}" class="text-sm text-gray-600 underline">Voltar ao acompanhamento</a>
        </div>
    </div>
</x-app-layout>
