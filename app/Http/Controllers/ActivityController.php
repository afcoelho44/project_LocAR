<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::where('user_id', auth()->id())
            ->withCount('questions')
            ->latest()
            ->paginate(10);

        return view('admin.activities.index', compact('activities'));
    }

    public function create()
    {
        return view('admin.activities.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();

        $activity = Activity::create($data);

        return redirect()
            ->route('admin.activities.show', $activity)
            ->with('success', 'Atividade criada!');
    }

    public function show(Activity $activity)
    {
        $this->authorizeOwner($activity);
        $activity->load('questions.object3d', 'questions.answers');

        return view('admin.activities.show', compact('activity'));
    }

    public function edit(Activity $activity)
    {
        $this->authorizeOwner($activity);

        return view('admin.activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorizeOwner($activity);
        $activity->update($this->validated($request));

        return redirect()
            ->route('admin.activities.index')
            ->with('success', 'Atividade atualizada!');
    }

    public function destroy(Activity $activity)
    {
        $this->authorizeOwner($activity);
        $activity->delete();

        return redirect()
            ->route('admin.activities.index')
            ->with('success', 'Atividade removida!');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    }

    private function authorizeOwner(Activity $activity): void
    {
        abort_if($activity->user_id !== auth()->id(), 403);
    }
}