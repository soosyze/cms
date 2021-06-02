<?php

return [
    'user' => [
        'class' => 'SoosyzeCore\User\Services\User',
        'hooks' => [
            'app.granted' => 'isGranted',
            'app.granted.request' => 'isGrantedRequest',
            'app.response.before' => 'hookResponseBefore',
            'app.response.after' => 'hookResponseAfter'
        ]
    ],
    'auth' => [
        'class' => 'SoosyzeCore\User\Services\Auth'
    ],
    'user.extend' => [
        'class' => 'SoosyzeCore\User\Extend',
        'hooks' => [
            'install.menu' => 'hookInstallMenu'
        ]
    ],
    'user.hook.api.route' => [
        'class' => 'SoosyzeCore\User\Hook\ApiRoute',
        'hooks' => [
            'api.route' => 'apiRoute'
        ]
    ],
    'user.hook.user' => [
        'class' => 'SoosyzeCore\User\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.user.login' => 'hookLogin',
            'route.user.login.check' => 'hookLoginCheck',
            'route.user.relogin' => 'hookRelogin',
            'route.user.relogin.check' => 'hookRelogin',
            'router.user.reset' => '',
            'route.user.logout' => 'hookLogout',
            'route.user.permission.admin' => 'hookPermissionAdminister',
            'route.user.permission.update' => 'hookPermissionAdminister',
            'route.user.register.create' => 'hookRegister',
            'route.user.register.store' => 'hookRegister',
            'route.user.activate' => 'hookActivate',
            'route.user.role.admin' => 'hookPeopleAdminister',
            'route.user.role.admin.check' => 'hookPeopleAdminister',
            'route.user.role.create' => 'hookPeopleAdminister',
            'route.user.role.store' => 'hookPeopleAdminister',
            'route.user.role.edit' => 'hookPeopleAdminister',
            'route.user.role.update' => 'hookPeopleAdminister',
            'route.user.role.remove' => 'hookRoleDeleted',
            'route.user.role.delete' => 'hookRoleDeleted',
            'route.user.account' => 'hookLogout',
            'route.user.show' => 'hookUserShow',
            'route.user.create' => 'hookPeopleAdminister',
            'route.user.store' => 'hookPeopleAdminister',
            'route.user.edit' => 'hookUserEdited',
            'route.user.update' => 'hookUserEdited',
            'route.user.remove' => 'hookUserDeleted',
            'route.user.delete' => 'hookUserDeleted',
            'route.user.admin' => 'hookPeopleAdminister',
            'route.user.filter' => 'hookPeopleAdminister',
            'route.user.filter.page' => 'hookPeopleAdminister',
            'route.user.api.select' => 'hookUserApiSelect'
        ]
    ],
    'user.hook.config' => [
        'class' => 'SoosyzeCore\User\Hook\Config',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'user.hook.block' => [
        'class' => 'SoosyzeCore\User\Hook\Block',
        'hooks' => [
            'block.create.form.data' => 'hookBlockCreateFormData',
            'block.user.login' => 'hookUserLogin',
        ]
    ]
];
