<?php

namespace App\Support;

class Duracao
{
    /** 75 -> "1:15", 3725 -> "1:02:05", 2403806 -> "27d 19h", null -> "–" */
    public static function format(?float $seconds): string
    {
        if ($seconds === null) {
            return '–';
        }

        $s = (int) round($seconds);

        // mais de um dia: horas:minutos não fazem sentido de ler
        if ($s >= 86400) {
            return sprintf('%dd %dh', intdiv($s, 86400), intdiv($s % 86400, 3600));
        }
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $s = $s % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
