@php use App\Support\Duracao; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Acompanhamento · {{ $activity->name }}</h2>
                <p class="text-xs text-gray-500">Atualizado às {{ now()->format('H:i:s') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.activity', $activity) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs uppercase rounded-md">Atualizar</a>
                <a href="{{ route('admin.reports.csv', $activity) }}"
                   class="px-4 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Exportar CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- cartões --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ([
                    ['Participantes', $summary->participants],
                    ['Concluíram', $summary->finished . ' / ' . $summary->participants],
                    ['Tempo médio (concluídos)', Duracao::format($summary->avg_time)],
                    ['Erros por aluno (média)', $summary->avg_errors ?? '–'],
                ] as [$label, $value])
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-800 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- alunos --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 pt-4 font-semibold text-gray-800">Alunos</h3>
                <table class="min-w-full text-sm mt-2">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">Situação</th>
                            <th class="px-4 py-3">Progresso</th>
                            <th class="px-4 py-3 text-right">Tempo</th>
                            <th class="px-4 py-3 text-right">Erros</th>
                            <th class="px-4 py-3 text-right">Amostras</th>
                            <th class="px-4 py-3">Última ação</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium">{{ $r->student }}</td>
                                <td class="px-4 py-3">
                                    @if ($r->finished)
                                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Concluiu</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">
                                            Na etapa {{ $r->current ?? '?' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 min-w-[140px]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-2 bg-blue-600" style="width: {{ $r->total ? round(100 * $r->done / $r->total) : 0 }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600 tabular-nums">{{ $r->done }}/{{ $r->total }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ Duracao::format($r->seconds) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums {{ $r->errors ? 'text-red-600 font-semibold' : '' }}">{{ $r->errors }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $r->samples }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $r->last?->format('d/m H:i') ?? '–' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.reports.participation', $r->participation) }}" class="text-blue-600">Detalhes</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Nenhum aluno iniciou esta atividade ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- perguntas --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <h3 class="px-4 pt-4 font-semibold text-gray-800">Por pergunta</h3>
                <p class="px-4 text-xs text-gray-500">
                    Deslocamento = da liberação da etapa até abrir a pergunta no ponto · Resposta = de abrir a pergunta até acertar
                </p>
                <table class="min-w-full text-sm mt-2">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Pergunta</th>
                            <th class="px-4 py-3 text-right">Chegaram</th>
                            <th class="px-4 py-3 text-right">Acertaram</th>
                            <th class="px-4 py-3 text-right">Erros</th>
                            <th class="px-4 py-3 text-right">Acerto na 1ª</th>
                            <th class="px-4 py-3 text-right">Deslocamento médio</th>
                            <th class="px-4 py-3 text-right">Resposta média</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($questions as $q)
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $q->question->position }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($q->question->description, 60) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $q->reached }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $q->completed }}</td>
                                <td class="px-4 py-3 text-right tabular-nums {{ $q->errors ? 'text-red-600 font-semibold' : '' }}">{{ $q->errors }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $q->first_try !== null ? $q->first_try . '%' : '–' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ Duracao::format($q->avg_travel) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ Duracao::format($q->avg_answer) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Atividade sem perguntas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.activities.show', $activity) }}" class="text-sm text-gray-600 underline">Voltar à atividade</a>
        </div>
    </div>
</x-app-layout>
