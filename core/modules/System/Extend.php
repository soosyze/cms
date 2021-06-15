<?php

declare(strict_types=1);

namespace SoosyzeCore\System;

use Psr\Container\ContainerInterface;
use Queryflatfile\TableBuilder;

class Extend extends \SoosyzeCore\System\ExtendModule
{
    public function getDir(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        $translations = [
            'config',
            'config_mailer',
            'humans_time',
            'main',
            'permission',
            'standard',
            'theme',
            'validation'
        ];
        foreach ($translations as $name) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$name.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
        $ci->schema()
            ->createTableIfNotExists('module_active', static function (TableBuilder $table): void {
                $table->string('title')
                ->string('version');
            })
            ->createTableIfNotExists('module_controller', static function (TableBuilder $table): void {
                $table->string('title')
                ->string('controller');
            })
            ->createTableIfNotExists('module_require', static function (TableBuilder $table): void {
                $table->string('title_module')
                ->string('title_required')
                ->string('version');
            })
            ->createTableIfNotExists('system_alias_url', static function (TableBuilder $table): void {
                $table->string('source')
                ->string('alias');
            })
            ->createTableIfNotExists('migration', static function (TableBuilder $table): void {
                $table->string('migration')
                ->string('extension');
            });

        $ci->config()
            ->set('settings.maintenance', false)
            ->set('settings.module_update_time', '')
            ->set('settings.module_update', false)
            ->set('settings.path_no_found', 'node/1')
            ->set('settings.path_index', 'node/2')
            ->set('settings.path_access_denied', 'user/login')
            ->set('settings.path_maintenance', '')
            ->set('settings.meta_title', 'Soosyze')
            ->set('settings.meta_description', 'Hello world !')
            ->set('settings.meta_keyboard', '')
            ->set('settings.favicon', '')
            ->set('settings.lang', 'en')
            ->set('settings.timezone', 'Europe/Paris')
            ->set('settings.theme_admin_dark', true);
    }

    public function seeders(ContainerInterface $ci): void
    {
    }

    public function hookInstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Menu')) {
            $this->hookInstallMenu($ci);
        }
        if ($ci->module()->has('User')) {
            $this->hookInstallUser($ci);
        }
    }

    public function hookInstallMenu(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('menu_link', [
                'key', 'icon', 'title_link', 'link', 'menu', 'weight', 'parent'
            ])
            ->values([
                'system.module.edit', 'fa fa-th-large', 'Modules', 'admin/modules',
                'menu-admin', 5, -1
            ])
            ->values([
                'system.theme.index', 'fa fa-paint-brush', 'Themes', 'admin/theme',
                'menu-admin', 6, -1
            ])
            ->values([
                'system.tool.admin', 'fa fa-tools', 'Tools', 'admin/tool',
                'menu-admin', 7, -1
            ])
            ->execute();
    }

    public function hookInstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 3, 'system.module.manage' ])
            ->values([ 3, 'system.theme.manage' ])
            ->values([ 3, 'system.tool.manage' ])
            ->values([ 3, 'system.tool.action' ])
            ->values([ 3, 'system.config.maintenance' ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        $tables = [
            'module_required',
            'module_controller',
            'module_active',
            'system_alias_url',
            'migration'
        ];
        foreach ($tables as $table) {
            $ci->schema()->dropTableIfExists($table);
        }
    }

    public function hookUninstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Menu')) {
            $this->hookUninstallMenu($ci);
        }
        if ($ci->module()->has('User')) {
            $this->hookUninstallUser($ci);
        }
    }

    public function hookUninstallMenu(ContainerInterface $ci): void
    {
        $ci->menu()->deleteLinks(static function () use ($ci): array {
            return $ci->query()
                    ->from('menu_link')
                    ->where('key', 'like', 'system%')
                    ->fetchAll();
        });
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'system.%')
            ->execute();
    }
}
