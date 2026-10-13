<?php

namespace App\Http\Controllers;

use App\Models\Object3d;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Object3dController extends Controller
{
    public function index()
    {
        $objects = Object3d::withCount('questions')->latest()->paginate(10);

        return view('admin.objects3d.index', compact('objects'));
    }

    public function create()
    {
        return view('admin.objects3d.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'file' => ['required', 'file', 'max:20480', function ($attr, $value, $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'glb') {
                    $fail('O arquivo deve ser um .glb');
                }
            }],
        ]);

        $path = $request->file('file')->store('models', 'public');

        Object3d::create([
            'name'      => $data['name'],
            'file_path' => $path,
        ]);

        return redirect()
            ->route('admin.objects3d.index')
            ->with('success', 'Objeto 3D cadastrado!');
    }

    public function edit(Object3d $objects3d)
    {
        return view('admin.objects3d.edit', ['object' => $objects3d]);
    }

    public function update(Request $request, Object3d $objects3d)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'file' => ['nullable', 'file', 'max:20480', function ($attr, $value, $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'glb') {
                    $fail('O arquivo deve ser um .glb');
                }
            }],
        ]);

        $update = ['name' => $data['name']];

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($objects3d->file_path);
            $update['file_path'] = $request->file('file')->store('models', 'public');
        }

        $objects3d->update($update);

        return redirect()
            ->route('admin.objects3d.index')
            ->with('success', 'Objeto 3D atualizado!');
    }

    public function destroy(Object3d $objects3d)
    {
        Storage::disk('public')->delete($objects3d->file_path);
        $objects3d->delete();

        return redirect()
            ->route('admin.objects3d.index')
            ->with('success', 'Objeto 3D removido!');
    }
}