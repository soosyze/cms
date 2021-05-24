<?php

declare(strict_types=1);

namespace SoosyzeCore\User;

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
        foreach ([ 'config', 'main', 'permission' ] as $file) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$file.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
        $ci->schema()
            ->createTableIfNotExists('user', static function (TableBuilder $table): void {
                $table->increments('user_id')
                ->string('email')
                ->string('username')
                ->text('password')
                ->string('key_api')->nullable()
                ->string('color', 7)->valueDefault('#e6e7f4')
                ->string('picture')->nullable()
                ->string('bio')->nullable()
                ->string('firstname')->nullable()
                ->string('name')->nullable()
                ->boolean('actived')->valueDefault(false)
                ->text('token_connected')->nullable()
                ->text('token_forget')->nullable()
                ->text('token_actived')->nullable()
                ->string('time_reset')->nullable()
                ->string('time_installed')
                ->string('time_access')->nullable()
                ->boolean('rgpd')->valueDefault(false)
                ->boolean('terms_of_service')->valueDefault(false)
                ->text('timezone');
            })
            ->createTableIfNotExists('role', static function (TableBuilder $table): void {
                $table->increments('role_id')
                ->string('role_description')->nullable()
                ->string('role_label')
                ->string('role_color', 7)->valueDefault('#e6e7f4')
                ->string('role_icon')->nullable()
                ->integer('role_weight')->valueDefault(1);
            })
            ->createTableIfNotExists('user_role', static function (TableBuilder $table): void {
                $table->integer('user_id')
                ->integer('role_id');
            })
            ->createTableIfNotExists('role_permission', static function (TableBuilder $table): void {
                $table->integer('role_id')
                ->string('permission_id');
            });

        $ci->query()
            ->insertInto('role', [
                'role_label', 'role_description', 'role_weight', 'role_icon', 'role_color'
            ])
            ->values([ 'User not logged in', 'Role required by the system', 1, 'fas fa-paper-plane', '#e5941f' ])
            ->values([ 'User logged in', 'Role required by the system', 2, 'fas fa-bolt', '#fe4341' ])
            ->values([ 'Administrator', 'Role required by the system', 3, 'fas fa-crown', '#858eec' ])
            ->execute();

        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 3, 'role.all' ])
            ->values([ 3, 'user.permission.manage' ])
            ->values([ 3, 'user.people.manage' ])
            ->values([ 3, 'user.showed' ])
            ->values([ 3, 'user.edited' ])
            ->values([ 3, 'user.deleted' ])
            ->values([ 2, 'user.showed' ])
            ->values([ 2, 'user.edited' ])
            ->execute();

        $ci->config()
            ->set('settings.user_delete', 2)
            ->set('settings.user_register', false)
            ->set('settings.user_relogin', true)
            ->set('settings.terms_of_service_show', false)
            ->set('settings.terms_of_service_page', '')
            ->set('settings.rgpd_show', false)
            ->set('settings.rgpd_page', '')
            ->set('settings.connect_url', '')
            ->set('settings.connect_redirect', 'user/account')
            ->set('settings.connect_https', true)
            ->set('settings.password_show', true)
            ->set('settings.password_policy', true)
            ->set('settings.password_length', 8)
            ->set('settings.password_upper', 1)
            ->set('settings.password_digit', 1)
            ->set('settings.password_special', 1)
            ->set('settings.password_reset_timeout', '1 day');
    }

    public function seeders(ContainerInterface $ci): void
    {
    }

    public function hookInstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Menu')) {
            $this->hookInstallMenu($ci);
        }
    }

    public function hookInstallMenu(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('menu_link', [
                'key', 'icon', 'title_link', 'link', 'menu', 'weight', 'parent'
            ])
            ->values([
                'user.admin', 'fa fa-user', 'User', 'admin/user',
                'menu-admin', 4, -1
            ])
            ->values([
                'user.account', null, 'My account', 'user/account', 'menu-user',
                1, -1
            ])
            ->values([
                'user.login', null, 'Sign in', 'user/login', 'menu-user', 2,
                -1
            ])
            ->values([
                'user.logout', 'fa fa-power-off', 'Sign out', 'user/logout',
                'menu-user', 3, -1
            ])
            ->values([
                'user.register.create', 'fas fa-user-circle', 'Sign up', 'user/register',
                'menu-user', 4, -1
            ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        foreach ([ 'user_role', 'role_permission', 'user', 'role' ] as $table) {
            $ci->schema()->dropTableIfExists($table);
        }
    }

    public function hookUninstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Menu')) {
            $this->hookUninstallMenu($ci);
        }
    }

    public function hookUninstallMenu(ContainerInterface $ci): void
    {
        $ci->menu()->deleteLinks(static function () use ($ci): array {
            return $ci->query()
                    ->from('menu_link')
                    ->where('key', 'like', 'user%')
                    ->fetchAll();
        });
    }
}
