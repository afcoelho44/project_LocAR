<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorSample extends Model
{
    /**
     * Colunas que vêm do celular (mesmos nomes no JSON, no banco e no CSV).
     * Esta lista é a fonte única: o controller valida e o export escreve a partir dela.
     */
    public const CAMPOS = [
        'sessao', 'question_id', 't_ms',
        'alvo_lat', 'alvo_lng',
        'lat', 'lng', 'accuracy', 'speed', 'gps_heading', 'gps_t_ms',
        'bussola_media', 'bussola_desvio', 'bussola_n',
        'alpha_rel', 'beta', 'gamma',
        'giro_alpha_soma', 'giro_beta_media', 'giro_gamma_media',
        'acc_mag_media', 'acc_mag_desvio',
    ];

    protected $fillable = ['participation_id', ...self::CAMPOS];

    public function participation() { return $this->belongsTo(Participation::class); }
    public function question()      { return $this->belongsTo(Question::class); }
}
