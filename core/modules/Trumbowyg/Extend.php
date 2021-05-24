<?php

declare(strict_types=1);

namespace SoosyzeCore\Trumbowyg;

use Psr\Container\ContainerInterface;

class Extend extends \SoosyzeCore\System\ExtendModule
{
    public function getDir(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        foreach ([ 'main', 'permission' ] as $file) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$file.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
    }

    public function uninstall(ContainerInterface $ci): void
    {
    }

    public function hookInstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('User')) {
            $this->hookInstallUser($ci);
        }
    }

    public function hookInstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 2, 'trumbowyg.upload' ])
            ->values([ 3, 'trumbowyg.upload' ])
            ->execute();
    }

    public function hookUninstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('User')) {
            $this->hookUninstallUser($ci);
        }
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'trumbowyg%')
            ->execute();
    }

    public function seeders(ContainerInterface $ci): void
    {
    }
}
