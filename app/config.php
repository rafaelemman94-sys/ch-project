<?php
date_default_timezone_set('Asia/Manila');

return [
    'db' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'kaya_portal',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'notifications' => [
        // seconds window to dedupe identical notifications
        'dedupe_window_seconds' => 60,
    ],
    'ai' => [
        // Keep this empty in source control and set OPENAI_API_KEY in production.
        'api_key' => '',
        'transcription_endpoint' => 'https://api.openai.com/v1/audio/transcriptions',
        'transcription_model' => 'gpt-4o-mini-transcribe',
        'transcription_max_mb' => 100,
    ],
];
