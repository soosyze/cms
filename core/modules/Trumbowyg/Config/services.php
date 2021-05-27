<?php

return [
    'trumbowyg.install' => [
        'class' => 'SoosyzeCore\Trumbowyg\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser'
        ]
    ],
    'trumbowyg.hook.app' => [
        'class' => 'SoosyzeCore\Trumbowyg\Hook\App',
        'arguments' => ['@core', '@config', '@router'],
        'hooks' => [
            'node.create.response.after' => 'getEditor',
            'node.edit.response.after' => 'getEditor',
            'block.section.admin.response.after' => 'getEditor'
        ]
    ],
    'trumbowyg.hook.user' => [
        'class' => 'SoosyzeCore\Trumbowyg\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.trumbowyg.upload' => 'hookUpload'
        ]
    ]
];
