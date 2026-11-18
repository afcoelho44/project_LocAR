<?php

namespace App\Support;

class Duracao
{
    /** 75 -> "1:15", 3725 -> "1:02:05", null -> "–" */
    public static function format(?float $seconds): string
    {
        if ($seconds === null) {
            return '–';
        }

        $s = (int) round($seconds);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $s = $s % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
