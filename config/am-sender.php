<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AM-Sender API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the AM-Sender API integration.
    | You can obtain your auth_key from your AM-Sender dashboard.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the AM-Sender API. This should not be changed unless
    | you are using a different API endpoint.
    |
    */

    'base_url' => env('AM_SENDER_BASE_URL', 'https://am-sender.com/api'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Key
    |--------------------------------------------------------------------------
    |
    | Your AM-Sender authentication key. This can be found in your
    | AM-Sender dashboard under API settings.
    |
    */

    'auth_key' => env('AM_SENDER_AUTH_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests to AM-Sender.
    |
    */

    'timeout' => env('AM_SENDER_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed API requests.
    |
    */

    'retry' => [
        'times' => env('AM_SENDER_RETRY_TIMES', 3),
        'sleep' => env('AM_SENDER_RETRY_SLEEP', 1000), // milliseconds
    ],

];
