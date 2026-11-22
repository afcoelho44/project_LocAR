<?php

namespace App\Support;

/**
 * Regra de proximidade (abordagem determinística do trabalho).
 *
 * Os limites são os mesmos do componente_IA (modelo_classe.py, LIMITES) e do
 * resources/js/dicas.js. Se mudar aqui, mude nos três.
 */
class Proximidade
{
    /** limite superior (m) de cada faixa, em ordem; acima do último é frio */
    public const LIMITES = [10.0 => 'quente', 15.0 => 'morno'];

    public const CLASSES = ['quente', 'morno', 'frio'];

    public static function classe(float $distancia): string
    {
        foreach (self::LIMITES as $limite => $nome) {
            if ($distancia <= $limite) {
                return $nome;
            }
        }

        return 'frio';
    }

    /** distância em metros (Haversine, mesmo raio do analisar.py) */
    public static function distancia(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371008.8;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $a = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }
}
