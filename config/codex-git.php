<?php

return [
    'default-project-config' => [
        'git' => [
            'enabled'    => false,
            'owner'      => '',
            'repository' => '',
            'remote'     => 'github',
            'sync'       => [
                'constraints' => [
                    'branches' => [ 'master' ],
                    'versions' => '*', //1.x || >=2.5.0 || 5.0.0 - 7.2.3'
                ],
                'paths'       => [
                    'docs'  => 'docs',
                    'menu'  => 'docs/menu.yml',
                    'index' => 'README.md',
                ],
            ],
            'webhook'    => [
                'enabled' => false,
                'secret'  => env('CODEX_PROJECT_GITHUB_WEBHOOK_SECRET', null),
            ],
        ],
    ],
];