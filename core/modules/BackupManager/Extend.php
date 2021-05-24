<?php

declare(strict_types=1);

namespace SoosyzeCore\BackupManager;

use Psr\Container\ContainerInterface;

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
        /* Création du dossier de backup. */
        $dir = $ci->core()->getDir('backup_dir', '../soosyze_backups/default');
        if (!is_dir($dir)) {
            \mkdir($dir, 0755, true);
            $f = fopen($dir . '/.htaccess', 'w');
            fwrite($f, '
# Apache 2.4+.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

# Apache 2.0 & 2.2.*
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>

Options None
Options +FollowSymLinks

<IfModule mod_php5.c>
  php_flag engine off
</IfModule>');
            fclose($f);
        }

        $ci->config()
            ->set('settings.max_backups', 30)
            ->set('settings.backup_frequency', '1 day')
            ->set('settings.backup_time', 0)
            ->set('settings.backup_cron', false);
    }

    public function seeders(ContainerInterface $ci): void
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
            ->values([ 3, 'backups.manage' ])
            ->execute();
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
                    ->where('key', 'like', 'backupmanager%')
                    ->fetchAll();
        });
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'backups.%')
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        $dir = $ci->core()->getDir('backup_dir', '../soosyze_backups');
        if (!is_dir($dir)) {
            return;
        }
        /* Suppression du dossier de sauvegarde. */
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isDot()) {
                continue;
            }
            \unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
