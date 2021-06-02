<?php

return [
    'backupmanager.extend' => [
        'class' => 'SoosyzeCore\BackupManager\Extend',
        'hooks' => [
            'install.user' => 'hookInstallUser'
        ]
    ],
    'backupmanager' => [
        'class' => 'SoosyzeCore\BackupManager\Services\BackupManager'
    ],
    'backupmanager.hook.config' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\Config',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'backupmanager.hook.user' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\User',
        'hooks' => [
            'user.permission.module' => 'hookUserPermissionModule',
            'route.backupmanager.admin' => 'hookBackupManage',
            'route.backupmanager.dobackup' => 'hookBackupManage',
            'route.backupmanager.download' => 'hookBackupManage',
            'route.backupmanager.restore' => 'hookBackupManage',
            'route.backupmanager.delete' => 'hookBackupManage',
            'route.backupmanager.delete.all' => 'hookBackupManage'
        ]
    ],
    'backupmanager.hook.cron' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\Cron',
        'hooks' => [
            'app.cron' => 'hookAppCron'
        ]
    ],
    'backupmanager.hook.tool' => [
        'class' => 'SoosyzeCore\BackupManager\Hook\Tool',
        'hooks' => [
            'tools.admin' => 'hookToolAdmin'
        ]
    ]
];
