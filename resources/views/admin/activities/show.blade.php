<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $activity->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-700">{{ $activity->description ?: 'Sem descrição.' }}</p>

                <h3 class="mt-6 font-semibold">Perguntas ({{ $activity->questions->count() }})</h3>
                <p class="text-sm text-gray-500">O cadastro de perguntas será feito na Etapa 4.</p>

                <div class="mt-6">
                    <a href="{{ route('admin.activities.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>