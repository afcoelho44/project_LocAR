<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // componente de IA (API Flask do repositório componente_IA) — classifica quente/morno/frio
    // url vazia = dicas só pela regra de distância. No Docker (Windows): http://host.docker.internal:5000
    'ia' => [
        'url' => env('IA_URL'),
        'timeout' => (float) env('IA_TIMEOUT', 1.5),   // segundos
        'modo' => env('IA_MODO', 'ia'),                // ia | regra | alternar (metade das etapas com cada)
        'filtro' => env('IA_FILTRO', 'kalman'),        // bruto | kalman — coordenadas usadas na distância (IA e regra)
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
