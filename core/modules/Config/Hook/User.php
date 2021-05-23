<?php

declare(strict_types=1);

namespace SoosyzeCore\Config\Hook;

use Soosyze\App;

class User implements \SoosyzeCore\User\UserInterface
{
    /**
     * @var App
     */
    private $core;

    public function __construct(App $core)
    {
        $this->core = $core;
    }

    public function hookUserPermissionModule(array &$permissions): void
    {
        $menu = [];
        $this->core->callHook('config.edit.menu', [ &$menu ]);

        $permissions[ 'Configuration' ][ 'config.manage' ] = 'Administer all configurations';
        foreach ($menu as $key => $link) {
            $permissions[ 'Configuration' ][ $key . '.config.manage' ] = [
                'name' => 'Administer :name configurations',
                'attr'  => [ ':name' => $link[ 'title_link' ] ]
            ];
        }
    }

    public function hookConfigAdmin(): array
    {
        $menu  = [];
        $this->core->callHook('config.edit.menu', [ &$menu ]);

        $out[] = 'config.manage';
        foreach (array_keys($menu) as $key) {
            $out[] = $key . '.config.manage';
        }

        return $out;
    }

    public function hookConfigManage(string $id): array
    {
        return [ 'config.manage', "$id.config.manage" ];
    }
}
