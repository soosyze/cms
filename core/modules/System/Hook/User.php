<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Hook;

use Psr\Http\Message\ServerRequestInterface;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'System' ] = [
            'system.module.manage'      => 'Administer the modules',
            'system.theme.manage'       => 'Administer the themes',
            'system.tool.manage'        => 'Use the tools interface',
            'system.tool.action'        => 'Use actions in the tools interface',
            'system.config.maintenance' => 'Access the site in maintenance mode'
        ];
    }

    public function hookModuleManage(): string
    {
        return 'system.module.manage';
    }

    public function hookThemeManage(): string
    {
        return 'system.theme.manage';
    }

    public function hookToolManage(): string
    {
        return 'system.tool.manage';
    }

    public function hookToolAction(): string
    {
        return 'system.tool.action';
    }

    public function apiRoute(?ServerRequestInterface $req, ?array $user): bool
    {
        return !empty($user);
    }
}
