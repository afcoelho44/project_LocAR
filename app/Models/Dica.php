<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** cada dica de proximidade mostrada ao aluno (para comparar IA x regra no teste piloto) */
class Dica extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['participation_id', 'question_id', 'braco', 'fonte', 'filtro', 'classe',
                           'dist_m', 'dist_bruto_m', 'dist_kalman_m', 'accuracy'];

    protected $casts = [
        'dist_m'        => 'float',
        'dist_bruto_m'  => 'float',
        'dist_kalman_m' => 'float',
        'accuracy'      => 'float',
    ];

    public function participation() { return $this->belongsTo(Participation::class); }
    public function question()      { return $this->belongsTo(Question::class); }
}
