<?php

return [
    'default' => env('AI_PROVIDER', 'opencode'),

    'providers' => [
        'opencode' => [
            'driver' => 'groq',
            'key' => env('OPENCODE_API_KEY'),
            'url' => env('OPENCODE_BASE_URL', 'https://api.groq.com/openai/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_MODEL', 'openai/gpt-oss-120b'),
                ],
            ],
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
            'models' => [
                'text' => [
                    'default' => env('GEMINI_MODEL', 'gemini-3.7-flash'),
                    'cheapest' => env('GEMINI_CHEAPEST_MODEL', 'gemini-3.5-flash-lite'),
                    'smartest' => env('GEMINI_SMARTEST_MODEL', 'gemini-3.7-flash'),
                ],
            ],
        ],
    ],
];
