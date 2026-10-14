<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Caças ao tesouro</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($activities as $activity)
                @php $p = $participations->get($activity->id); @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-lg">{{ $activity->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $activity->description ?: 'Sem descrição.' }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $activity->questions_count }} pergunta(s) · Professor: {{ $activity->teacher->name }}
                        </p>
                        @if ($p)
                            <p class="mt-1 text-xs {{ $p->isFinished() ? 'text-green-600' : 'text-amber-600' }}">
                                {{ $p->isFinished() ? 'Concluída em ' . $p->finished_at->format('d/m/Y H:i') : 'Em andamento' }}
                            </p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('play.start', $activity) }}">
                        @csrf
                        <x-primary-button>
                            @if (! $p) Iniciar
                            @elseif ($p->isFinished()) Jogar de novo
                            @else Continuar
                            @endif
                        </x-primary-button>
                    </form>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Nenhuma atividade disponível ainda.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>