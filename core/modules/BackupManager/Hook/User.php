<?php

declare(strict_types=1);

namespace SoosyzeCore\BackupManager\Hook;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'Backups' ] = [
            'backups.manage' => 'Manage the backups'
        ];
    }

    public function hookBackupManage(): string
    {
        return 'backups.manage';
    }
}
