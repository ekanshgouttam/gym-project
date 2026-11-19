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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    // Claude vision for the AI Body Scanner.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
        'effort' => env('ANTHROPIC_EFFORT', 'medium'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 180),
        // USD per million tokens, for the Super Admin AI cost report. Update when Anthropic pricing changes.
        'pricing' => [
            'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.20],
            'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.10],
            'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01],
            'claude-opus-5' => ['input' => 5.00, 'output' => 25.00, 'cache_read' => 0.50],
            'claude-opus-4-8' => ['input' => 5.00, 'output' => 25.00, 'cache_read' => 0.50],
        ],
    ],

    // OTP delivery. "log" writes codes to storage/logs (development only).
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'msg91_auth_key' => env('MSG91_AUTH_KEY'),
        'msg91_template_id' => env('MSG91_TEMPLATE_ID'),
    ],

];
