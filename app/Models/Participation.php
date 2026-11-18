<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    protected $fillable = ['user_id', 'activity_id', 'current_question_id', 'started_at', 'finished_at'];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()            { return $this->belongsTo(User::class); }
    public function activity()        { return $this->belongsTo(Activity::class); }
    public function currentQuestion() { return $this->belongsTo(Question::class, 'current_question_id'); }
    public function steps()           { return $this->hasMany(ParticipationStep::class); }
    public function attempts()        { return $this->hasMany(Attempt::class); }
    public function samples()         { return $this->hasMany(SensorSample::class); }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }
}