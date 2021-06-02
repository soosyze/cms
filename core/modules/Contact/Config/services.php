<?php

return [
    'contact.extend' => [
        'class' => 'SoosyzeCore\Contact\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser',
            'install.menu' => 'hookInstallMenu'
        ]
    ],
    'contact.hook.api.route' => [
        'class' => 'SoosyzeCore\Contact\Hook\ApiRoute',
        'hooks' => [
            'api.route' => 'apiRoute'
        ]
    ],
    'contact.hook.user' => [
        'class' => 'SoosyzeCore\Contact\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.contact' => 'hookContact',
            'route.contact.check' => 'hookContact'
        ]
    ]
];
