<?php

/*
| Mobile app versions, served by GET /api/v1/app-version.
| "latest"  — newest build in the store; older apps may show an optional update prompt.
| "minimum" — oldest build the API still supports; older apps must update to continue.
*/

return [
    'android' => [
        'latest' => env('ANDROID_LATEST_VERSION', '1.0.0'),
        'minimum' => env('ANDROID_MIN_VERSION', '1.0.0'),
        'store_url' => env('ANDROID_STORE_URL'),
    ],
    'ios' => [
        'latest' => env('IOS_LATEST_VERSION', '1.0.0'),
        'minimum' => env('IOS_MIN_VERSION', '1.0.0'),
        'store_url' => env('IOS_STORE_URL'),
    ],
];
