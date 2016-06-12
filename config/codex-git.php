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
    ],
    'default-project-config' => [
        'git' => [
            'enabled'    => false,
            // The owner (organisation or username)
            'owner'      => '',
            // The repository name
            'repository' => '',
            // The connection name (defined in services.php
            'connection' => '',
            // The downloader (git api vs zip downloader)
            'downloader' => 'git',

            'sync'       => [
                'constraints' => [
                    // Branches to sync
                    'branches'            => [ 'master' ],

                    // Version (tags) constraints makes one able to define ranges and whatnot
                    // 1.x || >=2.5.0 || 5.0.0 - 7.2.3'
                    'versions'            => '*',

                    // will skip versions (tags) like 3.0.1, 3.0.2. Will result in 3.0.0, 3.1.0, 3.2.0 etc
                    'skip_patch_versions' => false,

                    // Will skip versions (tags) like 3.1.0, 3.2.0. Will result in 3.0.0, 4.0.0, 5.0.0 etc.
                    // Setting this to true will automaticly set skip_patch_version to true
                    'skip_minor_versions' => false,
                ],
                'paths'       => [
                    // relative path to the root folder where the docs are
                    'docs'  => 'docs',

                    // relative path to the menu definition file
                    'menu'  => 'docs/menu.yml',

                    // relative path to the index.md file. You can use the README.md or docs/index.md for example
                    'index' => 'docs/index.md' // 'index' => 'README.md',
                ],
            ],
            'webhook'    => [
                // Enable webhook support. Configure it in Github/Bitbucket.
                // This will automaticly sync your project every time a 'push' event occurs
                // This also requires you to configure queues properly (by using for example, redis with supervisord)
                'enabled' => false,

                // Github webhooks allow a 'secret' that has to match. Put it in here
                'secret'  => env('CODEX_GIT_GITHUB_WEBHOOK_SECRET', ''),
            ],
        ],
    ],
];
