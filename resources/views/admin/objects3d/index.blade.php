<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Objetos 3D</h2>
            <a href="{{ route('admin.objects3d.create') }}"
               class="px-4 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Novo objeto</a>
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
                            <th class="px-4 py-3">Arquivo</th>
                            <th class="px-4 py-3">Usado em</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($objects as $object)
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $object->name }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ $object->url }}" class="text-blue-600" target="_blank">{{ basename($object->file_path) }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $object->questions_count }} pergunta(s)</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.objects3d.edit', $object) }}" class="text-indigo-600">Editar</a>
                                    <form method="POST" action="{{ route('admin.objects3d.destroy', $object) }}"
                                          class="inline" onsubmit="return confirm('Remover este objeto?')">
                                        @csrf @method('DELETE')
                                        <button class="ml-3 text-red-600">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Nenhum objeto cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $objects->links() }}</div>
        </div>
    </div>
</x-app-layout>