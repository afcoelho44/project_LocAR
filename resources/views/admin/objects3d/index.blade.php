<x-app-layout>
    @push('scripts')
        <script type="module"
            src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js"></script>
    @endpush

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Objetos 3D</h2>
            <a href="{{ route('admin.objects3d.create') }}"
               class="px-4 py-2 bg-gray-800 text-white text-xs uppercase rounded-md">Novo objeto</a>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
            open: false,
            src: '',
            name: '',
            show(src, name) { this.src = src; this.name = name; this.open = true },
            close() { this.open = false; this.src = '' }
         }"
         @keydown.escape.window="close()">

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
                                <td class="px-4 py-3 text-gray-600">{{ basename($object->file_path) }}</td>
                                <td class="px-4 py-3">{{ $object->questions_count }} pergunta(s)</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button"
                                            class="text-blue-600"
                                            @click="show(@js($object->url), @js($object->name))">
                                        Ver
                                    </button>
                                    <a href="{{ route('admin.objects3d.edit', $object) }}" class="ml-3 text-indigo-600">Editar</a>
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

        {{-- Modal --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
             @click.self="close()">

            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl overflow-hidden">
                <div class="flex justify-between items-center px-4 py-3 border-b">
                    <h3 class="font-semibold text-gray-800" x-text="name"></h3>
                    <button type="button" class="text-gray-500 hover:text-gray-800 text-xl leading-none"
                            @click="close()">&times;</button>
                </div>

                <div class="p-4">
                    {{-- x-if recria o viewer a cada abertura e libera o WebGL ao fechar --}}
                    <template x-if="open">
                        <model-viewer
                            :src="src"
                            :alt="name"
                            camera-controls
                            auto-rotate
                            shadow-intensity="1"
                            style="width: 100%; height: 480px; background-color: #f3f4f6; border-radius: 6px;">
                        </model-viewer>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>