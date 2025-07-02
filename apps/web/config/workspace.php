<?php

return [
    'enabled' => env('WORKSPACE_ENABLED', true),

    // maximum number of members allowed per workspace (null = unlimited)
    'member_limit' => env('WORKSPACE_MEMBER_LIMIT', null),

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
