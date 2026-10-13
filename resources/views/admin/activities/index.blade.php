<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Minhas atividades</h2>
            <a href="{{ route('admin.activities.create') }}"
               class="px-4 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Nova atividade</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Perguntas</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $activity->name }}</td>
                                <td class="px-4 py-3">{{ $activity->questions_count }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.activities.show', $activity) }}" class="text-blue-600">Abrir</a>
                                    <a href="{{ route('admin.activities.edit', $activity) }}" class="ml-3 text-indigo-600">Editar</a>
                                    <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}"
                                          class="inline" onsubmit="return confirm('Remover esta atividade?')">
                                        @csrf @method('DELETE')
                                        <button class="ml-3 text-red-600">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhuma atividade cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $activities->links() }}</div>
        </div>
    </div>
</x-app-layout>