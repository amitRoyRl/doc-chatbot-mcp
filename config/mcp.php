<?php

return [
    'server' => [
        'transport' => env('MCP_TRANSPORT', 'http'),
        'transports' => [
            'http' => ['enabled' => true],
            'stdio' => ['enabled' => false],
        ],
    ],
    'discovery' => [
        'base_path' => base_path(),
        'directories' => ['app/Mcp'],
        'auto_discover' => env('MCP_DISCOVER', true),
    ],

    'cache' => [
        'store' => null, // Use default Laravel cache store
        'prefix' => 'mcp_',
        'ttl' => 3600, // 1 hour
    ],

    'transports' => [
        'http' => [
            'enabled' => true,
            'prefix' => 'mcp',
            'middleware' => ['web'],
            'domain' => null,
        ],
        'stdio' => [
            'enabled' => true,
        ],
    ],

    'protocol_versions' => [
        '2024-11-05',
    ],

    'pagination_limit' => 50,

    'capabilities' => [
        'tools' => true,
        'resources' => true,
        'prompts' => true,
        'logging' => true,
        'list_changed' => true,
    ],

    'logging' => [
        'channel' => null, // Use default Laravel log channel
        'level' => 'info',
    ],
];