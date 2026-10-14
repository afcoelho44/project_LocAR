<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participation->activity->name }}</h2>
            <span class="text-sm text-gray-600">Etapa {{ $number }} de {{ $total }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold">Pergunta</h3>
                <p class="mt-2 text-gray-700">{{ $question->description }}</p>

                <ul class="mt-4 space-y-1 text-sm">
                    @foreach ($question->answers as $answer)
                        <li><span class="font-semibold">{{ $answer->alternative }})</span> {{ $answer->description }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Área temporária: na Etapa 6 vira a cena de RA --}}
            <div class="bg-yellow-50 border border-yellow-200 sm:rounded-lg p-6 text-sm text-yellow-900">
                <p class="font-semibold">Área de RA (Etapa 6)</p>
                <p class="mt-1">Destino: {{ $question->lat }}, {{ $question->lng }} · raio {{ $question->radius }} m</p>
                <p>Objeto 3D: {{ $question->object3d?->name ?? 'nenhum' }}</p>
                @if ($question->object3d)
                    <p class="break-all">Arquivo: {{ $question->object3d->url }}</p>
                @endif
            </div>

            <a href="{{ route('play.index') }}" class="text-sm text-gray-600 underline">Voltar às atividades</a>
        </div>
    </div>
</x-app-layout>