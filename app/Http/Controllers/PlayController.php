<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Participation;
use App\Models\Question;

class PlayController extends Controller
{
    public function index()
    {
        $activities = Activity::with('teacher')
            ->withCount('questions')
            ->whereHas('questions')
            ->latest()
            ->get();

        $participations = Participation::where('user_id', auth()->id())
            ->latest('id')
            ->get()
            ->unique('activity_id')
            ->keyBy('activity_id');

        return view('play.index', compact('activities', 'participations'));
    }

    public function start(Activity $activity)
    {
        $first = $activity->questions()->first();
        abort_if(! $first, 404);

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
        $this->authorizeOwner($participation);
        $participation->load('activity');

        if ($participation->isFinished()) {
            return view('play.finished', compact('participation'));
        }

        $question = $this->resolveQuestion($participation);
        [$number, $total] = $this->progress($participation, $question);

        return view('play.show', compact('participation', 'number', 'total'));
    }

    public function ar(Participation $participation)
    {
        $this->authorizeOwner($participation);
        abort_if($participation->isFinished(), 404);
        $participation->load('activity');

        return view('play.ar', compact('participation'));
    }

    public function state(Participation $participation)
    {
        $this->authorizeOwner($participation);

        if ($participation->isFinished()) {
            return response()->json(['finished' => true]);
        }

        $question = $this->resolveQuestion($participation);
        [$number, $total] = $this->progress($participation, $question);

        return response()->json([
            'finished' => false,
            'number'   => $number,
            'total'    => $total,
            'question' => [
                'id'          => $question->id,
                'description' => $question->description,
                'lat'         => $question->lat,
                'lng'         => $question->lng,
                'radius'      => $question->radius,
                // caminho relativo: funciona em localhost e no túnel HTTPS
                'object_url'  => $question->object3d
                    ? '/storage/' . $question->object3d->file_path
                    : null,
                // sem is_correct e sem clue_text: isso só sai na validação (Etapa 7)
                'answers'     => $question->answers->map(fn ($a) => [
                    'id'          => $a->id,
                    'alternative' => $a->alternative,
                    'description' => $a->description,
                ])->values(),
            ],
        ]);
    }

    private function resolveQuestion(Participation $participation): Question
    {
        $question = $participation->currentQuestion()
            ->with(['object3d', 'answers'])
            ->first();

        // a pergunta atual pode ter sido apagada (nullOnDelete): volta ao início
        if (! $question) {
            $question = $participation->activity->questions()
                ->with(['object3d', 'answers'])
                ->first();
            abort_if(! $question, 404);
            $participation->update(['current_question_id' => $question->id]);
        }

        return $question;
    }

    private function progress(Participation $participation, Question $question): array
    {
        $questions = $participation->activity->questions();

        return [
            (clone $questions)->where('position', '<=', $question->position)->count(),
            (clone $questions)->count(),
        ];
    }

    private function authorizeOwner(Participation $participation): void
    {
        abort_if($participation->user_id !== auth()->id(), 403);
    }
}