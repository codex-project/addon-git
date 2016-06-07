<?php

return [
    'route-prefix' => '_git-webhook',
    
    'connections'            => [
        'github1'    => [
            'remote' => 'github',
            'type'   => 'token',
            'token'  => env('GITHUB_TOKEN', ''),
        ],
        'bitbucket1' => [
            'remote' => 'bitbucket',
            'type'   => 'oauth',
            'key'    => env('BITBUCKET_CLIENT_KEY', ''),
            'secret' => env('BITBUCKET_CLIENT_SECRET', ''),
        ],

        /*
        # type: token is not supported for bitbucket
        'bitbucket_basic'  => [
            'remote'   => 'bitbucket',
            'type'     => 'basic',
            'username' => '',
            'password' => '',
        ],
        'bitbucket_oauth'  => [
            'remote' => 'bitbucket',
            'type'   => 'oauth',
            'key'    => '',
            'secret' => '',
        ],
        'bitbucket_oauth2' => [
            'remote' => 'bitbucket',
            'type'   => 'oauth2',
            'id'     => '',
            'secret' => '',
        ],

        # type: oauth is not supported for github
        'github_basic'     => [
            'remote'   => 'github',
            'type'     => 'basic',
            'username' => '',
            'password' => '',
        ],
        'github_token'     => [
            'remote' => 'github',
            'type'   => 'oauth',
            'token'  => '',
        ],
        'github_oauth2'    => [
            'remote' => 'github',
            'type'   => 'oauth2',
            'key'    => '',
            'secret' => '',
        ],
        */
    ],
    'default-project-config' => [
        'git' => [
            'enabled'    => false,
            'owner'      => '',
            'repository' => '',
            'connection' => '',
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