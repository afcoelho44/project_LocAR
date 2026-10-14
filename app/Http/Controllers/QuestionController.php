<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Activity;
use App\Models\Object3d;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    private const FIELDS = [
        'object_3d_id', 'description', 'clue_text', 'lat', 'lng', 'position', 'radius',
    ];

    public function create(Activity $activity)
    {
        $this->authorizeOwner($activity);

        $question = new Question([
            'position' => (Question::where('activity_id', $activity->id)->max('position') ?? 0) + 1,
            'radius'   => 20,
        ]);

        return view('admin.questions.create', [
            'activity' => $activity,
            'question' => $question,
            'objects'  => Object3d::orderBy('name')->get(),
        ]);
    }

    public function store(QuestionRequest $request, Activity $activity)
    {
        $this->authorizeOwner($activity);

        DB::transaction(function () use ($request, $activity) {
            $question = $activity->questions()->create($request->safe()->only(self::FIELDS));
            $this->syncAnswers($question, $request);
        });

        return redirect()
            ->route('admin.activities.show', $activity)
            ->with('success', 'Pergunta criada!');
    }

    public function edit(Question $question)
    {
        $this->authorizeOwner($question->activity);
        $question->load('answers');

        return view('admin.questions.edit', [
            'activity' => $question->activity,
            'question' => $question,
            'objects'  => Object3d::orderBy('name')->get(),
        ]);
    }

    public function update(QuestionRequest $request, Question $question)
    {
        $this->authorizeOwner($question->activity);

        DB::transaction(function () use ($request, $question) {
            $question->update($request->safe()->only(self::FIELDS));
            $this->syncAnswers($question, $request);
        });

        return redirect()
            ->route('admin.activities.show', $question->activity_id)
            ->with('success', 'Pergunta atualizada!');
    }

    public function destroy(Question $question)
    {
        $this->authorizeOwner($question->activity);
        $activityId = $question->activity_id;
        $question->delete();

        return redirect()
            ->route('admin.activities.show', $activityId)
            ->with('success', 'Pergunta removida!');
    }

    private function syncAnswers(Question $question, QuestionRequest $request): void
    {
        $question->answers()->delete();

        foreach ($request->input('answers') as $i => $answer) {
            if (blank($answer['description'] ?? null)) {
                continue; // alternativa opcional deixada em branco
            }

            $question->answers()->create([
                'alternative' => chr(65 + $i), // A, B, C, D
                'description' => $answer['description'],
                'is_correct'  => (int) $request->input('correct_answer') === (int) $i,
            ]);
        }
    }

    private function authorizeOwner(Activity $activity): void
    {
        abort_if($activity->user_id !== auth()->id(), 403);
    }
}