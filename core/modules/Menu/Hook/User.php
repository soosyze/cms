<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Hook;

use Psr\Http\Message\ServerRequestInterface;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'Menu' ] = [
            'menu.administer' => 'Administer menus and menu items'
        ];
    }

    public function hookMenuAdminister(): string
    {
        return 'menu.administer';
    }

    public function hookMenuDelete(string $name): ?string
    {
        return in_array($name, [ 'menu-main', 'menu-admin', 'menu-user' ])
            ? null
            : 'menu.administer';
    }

    public function hookMenuApiShow(string $menu, ?ServerRequestInterface $req, ?array $user)
    {
        return !empty($user);
    }
}
