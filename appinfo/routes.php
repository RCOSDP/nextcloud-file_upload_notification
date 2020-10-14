<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'Config#createSecret', 'url' => '/secret', 'verb' => 'GET'],
        ['name' => 'Config#getConfig', 'url' => '/config', 'verb' => 'GET'],
        ['name' => 'Config#setConfig', 'url' => '/config', 'verb' => 'POST']
    ],
    'ocs' => [
        ['name' => 'Config#deleteConfig', 'url' => '/api/config', 'verb' => 'DELETE'],
        ['name' => 'Recent#getRecent', 'url' => '/api/recent', 'verb' => 'GET']
    ]
];