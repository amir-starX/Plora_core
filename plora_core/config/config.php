<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'My PHP Framework',
        'env' => 'development',
        'debug' => true,
        'database_enabled' => false,
        'timezone' => 'Asia/Baku',
    ],

    'plugins' => [
        'SamplePlugin' => true,
        'TasksPlugin' => true,
    ],
];
