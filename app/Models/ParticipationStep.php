<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipationStep extends Model
{
    protected $fillable = ['participation_id', 'question_id', 'started_at', 'arrived_at', 'completed_at', 'wrong_attempts'];

    protected $casts = [
        'started_at'   => 'datetime',
        'arrived_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function participation() { return $this->belongsTo(Participation::class); }
    public function question()      { return $this->belongsTo(Question::class); }

    /** segundos entre a liberação da etapa e a chegada ao ponto */
    public function travelSeconds(): ?int
    {
        return $this->arrived_at ? $this->started_at->diffInSeconds($this->arrived_at) : null;
    }

    /** segundos entre a chegada ao ponto e o acerto */
    public function answerSeconds(): ?int
    {
        return $this->arrived_at && $this->completed_at
            ? $this->arrived_at->diffInSeconds($this->completed_at)
            : null;
    }

    /** segundos totais da etapa (liberação até o acerto) */
    public function totalSeconds(): ?int
    {
        return $this->completed_at ? $this->started_at->diffInSeconds($this->completed_at) : null;
    }
}
