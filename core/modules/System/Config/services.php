<?php

return [
    'alias' => [
        'class' => 'SoosyzeCore\System\Services\Alias',
        'arguments' => ['@config', '@query']
    ],
    'composer' => [
        'class' => 'SoosyzeCore\System\Services\Composer',
        'arguments' => ['@core', '@module', '@semver']
    ],
    'semver' => [
        'class' => 'SoosyzeCore\System\Services\Semver'
    ],
    'module' => [
        'class' => 'SoosyzeCore\System\Services\Modules',
        'arguments' => ['@query', '@translate']
    ],
    'migration' => [
        'class' => 'SoosyzeCore\System\Services\Migration',
        'arguments' => ['@composer', '@config', '@query', '@schema']
    ],
    'system.hook.app' => [
        'class' => 'SoosyzeCore\System\Hook\App',
        'arguments' => ['@alias', '@config', '@core', '@query', '@router', '@template'],
        'hooks' => [
            'app.response.before' => 'hookSys',
            'app.403' => 'hooks403',
            'app.404' => 'hooks404',
            'app.503' => 'hooks503',
            'app.response.after' => 'hookResponseAfter'
        ]
    ],
    'system.extend' => [
        'class' => 'SoosyzeCore\System\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ],
    'system.hook.user' => [
        'class' => 'SoosyzeCore\System\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookPermission',
            'route.system.module.edit' => 'hookModuleManage',
            'route.system.module.update' => 'hookModuleManage',
            'route.system.theme.index' => 'hookThemeManage',
            'route.system.theme.admin' => 'hookThemeManage',
            'route.system.theme.active' => 'hookThemeManage',
            'route.system.theme.edit' => 'hookThemeManage',
            'route.system.theme.update' => 'hookThemeManage',
            'route.api.route' => 'hookApiRoute'
        ]
    ],
    'system.hook.config' => [
        'class' => 'SoosyzeCore\System\Hook\Config',
        'arguments' => ['@file', '@router', '@translate'],
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'mailer.hook.config' => [
        'class' => 'SoosyzeCore\System\Hook\ConfigMailer',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ]
];
