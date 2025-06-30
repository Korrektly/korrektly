<?php

return [
    'enabled' => env('WORKSPACE_ENABLED', true),

    'roles' => [
        'owner' => [
            'permissions' => [
                'workspace.manage',
                'workspace.invite',
                'members.manage',
                'apps.manage',
                'installations.view',
                '*',
            ],
        ],
        'admin' => [
            'permissions' => [
                'workspace.invite',
                'members.manage',
                'apps.manage',
                'installations.view',
                '*',
            ],
        ],
        'member' => [
            'permissions' => [
                'apps.view',
                'installations.view',
                '*',
            ],
        ],
    ],
];
