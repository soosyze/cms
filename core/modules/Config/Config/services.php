<?php

return [
    'config.hook.user' => [
        'class' => 'SoosyzeCore\Config\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.config.admin' => 'hookConfigAdmin',
            'route.config.edit' => 'hookConfigManage',
            'route.config.update' => 'hookConfigManage'
        ]
    ],
    'config.extend' => [
        'class' => 'SoosyzeCore\Config\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ]
];
