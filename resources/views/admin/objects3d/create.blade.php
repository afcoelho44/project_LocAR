<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo objeto 3D</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.objects3d.store') }}" enctype="multipart/form-data">
                    @include('admin.objects3d._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>