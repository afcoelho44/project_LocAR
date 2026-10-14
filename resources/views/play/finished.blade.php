<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participation->activity->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-2xl font-semibold text-green-600">Parabéns, você concluiu o percurso!</p>
                <p class="mt-2 text-sm text-gray-600">
                    Início: {{ $participation->started_at->format('d/m/Y H:i') }}<br>
                    Fim: {{ $participation->finished_at->format('d/m/Y H:i') }}
                </p>
                <a href="{{ route('play.index') }}" class="mt-6 inline-block text-sm text-gray-600 underline">Voltar às atividades</a>
            </div>
        </div>
    </div>
</x-app-layout>