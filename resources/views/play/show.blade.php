<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participation->activity->name }}</h2>
            <span class="text-sm text-gray-600">Etapa {{ $number }} de {{ $total }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-gray-700">Vá até o ponto indicado e aponte a câmera para encontrar a pista.</p>
                <p class="mt-2 text-xs text-gray-500">Use o celular, com GPS e câmera liberados.</p>
                <a href="{{ route('play.ar', $participation) }}"
                   class="mt-6 inline-block px-6 py-3 bg-gray-800 text-white text-sm uppercase rounded-md">
                    Abrir modo RA
                </a>
            </div>
            <a href="{{ route('play.index') }}" class="text-sm text-gray-600 underline">Voltar às atividades</a>
        </div>
    </div>
</x-app-layout>