@csrf

<div>
    <x-input-label for="name" value="Nome" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
        :value="old('name', $object->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="file" value="Arquivo .glb {{ isset($object) ? '(deixe vazio para manter o atual)' : '' }}" />
    <input id="file" name="file" type="file" accept=".glb" class="block mt-1 w-full text-sm"
        {{ isset($object) ? '' : 'required' }}>
    <x-input-error :messages="$errors->get('file')" class="mt-2" />
    @isset($object)
        <p class="mt-2 text-xs text-gray-500">Atual: {{ $object->file_path }}</p>
    @endisset
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>Salvar</x-primary-button>
    <a href="{{ route('admin.objects3d.index') }}" class="text-sm text-gray-600 underline">Cancelar</a>
</div>