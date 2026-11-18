<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $fillable = ['participation_id', 'question_id', 'answer_id', 'is_correct'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function participation() { return $this->belongsTo(Participation::class); }
    public function question()      { return $this->belongsTo(Question::class); }
    public function answer()        { return $this->belongsTo(Answer::class); }
}
