<?php

declare(strict_types=1);

namespace SoosyzeCore\Dashboard\Hook;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'Dashboard' ] = [
            'dashboard.administer' => 'Use the dashboard'
        ];
    }

    public function hookDashboardAdminister(): string
    {
        return 'dashboard.administer';
    }
}
