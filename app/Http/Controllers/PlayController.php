<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Participation;

class PlayController extends Controller
{
    public function index()
    {
        $activities = Activity::with('teacher')
            ->withCount('questions')
            ->whereHas('questions')
            ->latest()
            ->get();

        // última participação do usuário em cada atividade
        $participations = Participation::where('user_id', auth()->id())
            ->latest('id')
            ->get()
            ->unique('activity_id')
            ->keyBy('activity_id');

        return view('play.index', compact('activities', 'participations'));
    }

    public function start(Activity $activity)
    {
        $first = $activity->questions()->first(); // já vem ordenada por position
        abort_if(! $first, 404);

        // retoma a participação em andamento ou cria uma nova
        $participation = Participation::firstOrCreate(
            [
                'user_id'     => auth()->id(),
                'activity_id' => $activity->id,
                'finished_at' => null,
            ],
            [
                'current_question_id' => $first->id,
                'started_at'          => now(),
            ]
        );

        return redirect()->route('play.show', $participation);
    }

    public function show(Participation $participation)
    {
        abort_if($participation->user_id !== auth()->id(), 403);

        $participation->load('activity');

        if ($participation->isFinished()) {
            return view('play.finished', compact('participation'));
        }

        $question = $participation->currentQuestion()
            ->with(['object3d', 'answers'])
            ->first();

        // o professor pode ter apagado a pergunta atual (nullOnDelete): volta ao início
        if (! $question) {
            $question = $participation->activity->questions()->with(['object3d', 'answers'])->first();
            abort_if(! $question, 404);
            $participation->update(['current_question_id' => $question->id]);
        }

        $total  = $participation->activity->questions()->count();
        $number = $participation->activity->questions()
            ->where('position', '<=', $question->position)
            ->count();

        return view('play.show', compact('participation', 'question', 'number', 'total'));
    }
}