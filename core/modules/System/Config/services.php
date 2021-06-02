<?php

return [
    'alias' => [
        'class' => 'SoosyzeCore\System\Services\Alias'
    ],
    'composer' => [
        'class' => 'SoosyzeCore\System\Services\Composer'
    ],
    'semver' => [
        'class' => 'SoosyzeCore\System\Services\Semver'
    ],
    'module' => [
        'class' => 'SoosyzeCore\System\Services\Modules'
    ],
    'migration' => [
        'class' => 'SoosyzeCore\System\Services\Migration'
    ],
    'system.hook.app' => [
        'class' => 'SoosyzeCore\System\Hook\App',
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
            'user.permission.module' => 'hookUserPermissionModule',
            'route.system.module.edit' => 'hookModuleManage',
            'route.system.module.update' => 'hookModuleManage',
            'route.system.migration.check' => 'hookModuleManage',
            'route.system.migration.update' => 'hookModuleManage',
            'route.system.theme.index' => 'hookThemeManage',
            'route.system.theme.admin' => 'hookThemeManage',
            'route.system.theme.active' => 'hookThemeManage',
            'route.system.theme.edit' => 'hookThemeManage',
            'route.system.theme.update' => 'hookThemeManage',
            'route.system.tool.admin' => 'hookToolManage',
            'route.system.tool.cron' => 'hookToolAction',
            'route.system.tool.trans' => 'hookToolAction',
            'route.api.route' => 'apiRoute'
        ]
    ],
    'system.hook.config' => [
        'class' => 'SoosyzeCore\System\Hook\Config',
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
