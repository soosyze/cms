<?php

return [
    'dashboard' => [
        'class' => 'SoosyzeCore\Dashboard\Services\Dashboard'
    ],
    'dashboard.extend' => [
        'class' => 'SoosyzeCore\Dashboard\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ],
    'dashboard.hook.user' => [
        'class' => 'SoosyzeCore\Dashboard\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.dashboard.index' => 'hookDashboardAdminister',
            'route.dashboard.info' => 'hookDashboardAdminister'
        ]
    ]
];
