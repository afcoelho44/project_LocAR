<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Participation;
use App\Models\ParticipationStep;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    /** visão geral da turma numa atividade */
    public function activity(Activity $activity)
    {
        $this->authorizeActivity($activity);

        $activity->load('questions');
        $total = $activity->questions->count();

        $participations = $activity->participations()
            ->with(['user', 'currentQuestion', 'steps', 'attempts'])
            ->withCount('samples')
            ->latest('started_at')
            ->get();

        // ---------- linha por aluno ----------
        $rows = $participations->map(function (Participation $p) use ($total) {
            $done = $p->steps->whereNotNull('completed_at')->count();
            $end = $p->finished_at ?? now();

            $lastActivity = collect([
                $p->started_at,
                $p->attempts->max('created_at'),
                $p->steps->max('arrived_at'),
            ])->filter()->max();

            return (object) [
                'participation' => $p,
                'student'       => $p->user?->name ?? '(aluno removido)',
                'finished'      => $p->isFinished(),
                'done'          => $done,
                'total'         => $total,
                'current'       => $p->currentQuestion?->position,
                'seconds'       => $p->started_at ? $p->started_at->diffInSeconds($end) : null,
                'errors'        => $p->attempts->where('is_correct', false)->count(),
                'samples'       => $p->samples_count,
                'last'          => $lastActivity,
            ];
        });

        // ---------- resumo por pergunta ----------
        $steps = $participations->flatMap->steps;

        $questions = $activity->questions->map(function ($q) use ($steps) {
            /** @var Collection<ParticipationStep> $s */
            $s = $steps->where('question_id', $q->id);
            $completed = $s->whereNotNull('completed_at');

            return (object) [
                'question'    => $q,
                'reached'     => $s->whereNotNull('arrived_at')->count(),
                'completed'   => $completed->count(),
                'errors'      => $s->sum('wrong_attempts'),
                'first_try'   => $completed->count()
                    ? round(100 * $completed->where('wrong_attempts', 0)->count() / $completed->count())
                    : null,
                'avg_travel'  => $this->avg($s->map->travelSeconds()),
                'avg_answer'  => $this->avg($s->map->answerSeconds()),
            ];
        });

        // ---------- cartões do topo ----------
        $finished = $rows->where('finished', true);
        $summary = (object) [
            'participants' => $rows->count(),
            'finished'     => $finished->count(),
            'avg_time'     => $this->avg($finished->pluck('seconds')),
            'avg_errors'   => $rows->count() ? round($rows->avg('errors'), 1) : null,
        ];

        return view('admin.reports.activity', compact('activity', 'rows', 'questions', 'summary'));
    }

    /** detalhe de um aluno: etapa por etapa e cada tentativa */
    public function participation(Participation $participation)
    {
        $participation->load([
            'activity.questions.answers',
            'user',
            'steps',
            'attempts.answer',
        ])->loadCount('samples');

        $this->authorizeActivity($participation->activity);

        $steps = $participation->steps->keyBy('question_id');
        $attempts = $participation->attempts->sortBy('created_at')->groupBy('question_id');

        return view('admin.reports.participation', compact('participation', 'steps', 'attempts'));
    }

    /** CSV com uma linha por etapa de cada aluno (para análise no Python/Excel) */
    public function csv(Activity $activity)
    {
        $this->authorizeActivity($activity);

        $participations = $activity->participations()
            ->with(['user', 'steps.question', 'attempts'])
            ->get();

        $filename = 'acompanhamento-atividade-' . $activity->id . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($participations) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: acentos certos no Excel

            fputcsv($out, [
                'participacao_id', 'aluno', 'pergunta_posicao', 'pergunta',
                'liberada_em', 'chegou_em', 'acertou_em',
                'tempo_deslocamento_s', 'tempo_resposta_s', 'tempo_total_s',
                'erros', 'tentativas',
            ], ';');

            foreach ($participations as $p) {
                foreach ($p->steps->sortBy(fn ($s) => $s->question?->position) as $s) {
                    fputcsv($out, [
                        $p->id,
                        $p->user?->name,
                        $s->question?->position,
                        $s->question?->description,
                        $s->started_at?->format('Y-m-d H:i:s'),
                        $s->arrived_at?->format('Y-m-d H:i:s'),
                        $s->completed_at?->format('Y-m-d H:i:s'),
                        $s->travelSeconds(),
                        $s->answerSeconds(),
                        $s->totalSeconds(),
                        $s->wrong_attempts,
                        $p->attempts->where('question_id', $s->question_id)->count(),
                    ], ';');
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** apaga todas as participações da atividade (etapas, tentativas e amostras vão junto) */
    public function reset(Activity $activity)
    {
        $this->authorizeActivity($activity);

        $n = $activity->participations()->count();
        $activity->participations()->delete();

        return redirect()->route('admin.reports.activity', $activity)
            ->with('status', "{$n} participação(ões) removida(s).");
    }

    private function avg(Collection $values): ?float
    {
        $v = $values->filter(fn ($x) => $x !== null);

        return $v->isEmpty() ? null : $v->avg();
    }

    private function authorizeActivity(Activity $activity): void
    {
        abort_if($activity->user_id !== auth()->id(), 403);
    }
}
