<?php

return [
    'block' => [
        'class' => 'SoosyzeCore\Block\Services\Block',
        'arguments' => ['@config', '@core'],
        'hooks' => [
            'block.social' => 'hookBlockSocial'
        ]
    ],
    'block.hook.app' => [
        'class' => 'SoosyzeCore\Block\Hook\App',
        'arguments' => ['@core', '@block', '@query', '@router', '@template', '@user'],
        'hooks' => [
            'app.response.after' => 'hookResponseAfter'
        ]
    ],
    'social.hook.config' => [
        'class' => 'SoosyzeCore\Block\Hook\Config',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'block.hook.user' => [
        'class' => 'SoosyzeCore\Block\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.block.section.admin' => 'hookBlockAdmin',
            'route.block.section.update' => 'hookBlockAdmin',
            'route.block.show' => 'hookBlockEdited',
            'route.block.create' => 'hookBlockCreated',
            'route.block.store' => 'hookBlockCreated',
            'route.block.edit' => 'hookBlockEdited',
            'route.block.update' => 'hookBlockEdited',
            'route.block.delete' => 'hookBlockDeleted'
        ]
    ],
    'block.extend' => [
        'class' => 'SoosyzeCore\Block\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser'
        ]
    ]
];
