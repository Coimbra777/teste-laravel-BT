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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gateway_one' => [
        'url' => env('GATEWAY_ONE_URL', 'http://gateways:3001'),
    ],

    'gateway_two' => [
        'url' => env('GATEWAY_TWO_URL', 'http://gateways:3002'),
        'token' => env('GATEWAY_TWO_TOKEN', 'tk_f2198cc671b5289fa856'),
        'secret' => env('GATEWAY_TWO_SECRET', '3d15e8ed6131446ea7e3456728b1211f'),
    ],

];
