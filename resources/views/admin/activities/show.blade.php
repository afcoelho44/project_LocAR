<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $activity->name }}</h2>
            <a href="{{ route('admin.activities.questions.create', $activity) }}"
               class="px-4 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Nova pergunta</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-700">{{ $activity->description ?: 'Sem descrição.' }}</p>
            </div>

            <div class="mt-6 bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Pergunta</th>
                            <th class="px-4 py-3">Local</th>
                            <th class="px-4 py-3">Raio</th>
                            <th class="px-4 py-3">Objeto 3D</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activity->questions as $question)
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $question->position }}</td>
                                <td class="px-4 py-3">
                                    {{ \Illuminate\Support\Str::limit($question->description, 60) }}
                                    <span class="block text-xs text-gray-500">{{ $question->answers->count() }} alternativas</span>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $question->lat }}, {{ $question->lng }}</td>
                                <td class="px-4 py-3">{{ $question->radius }} m</td>
                                <td class="px-4 py-3">{{ $question->object3d?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.questions.edit', $question) }}" class="text-indigo-600">Editar</a>
                                    <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                                          class="inline" onsubmit="return confirm('Remover esta pergunta?')">
                                        @csrf @method('DELETE')
                                        <button class="ml-3 text-red-600">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhuma pergunta cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.activities.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
            </div>
        </div>
    </div>
</x-app-layout>