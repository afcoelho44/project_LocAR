<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar pergunta: {{ $activity->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.questions.update', $question) }}">
                    @method('PUT')
                    @include('admin.questions._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>