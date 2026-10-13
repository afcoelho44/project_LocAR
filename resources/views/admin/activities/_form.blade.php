@csrf

<div>
    <x-input-label for="name" value="Nome" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
        :value="old('name', $activity->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Descrição" />
    <textarea id="description" name="description" rows="4"
        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $activity->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>Salvar</x-primary-button>
    <a href="{{ route('admin.activities.index') }}" class="text-sm text-gray-600 underline">Cancelar</a>
</div>