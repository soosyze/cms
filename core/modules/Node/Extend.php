<?php

declare(strict_types=1);

namespace SoosyzeCore\Node;

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
        foreach ([ 'block', 'config', 'main', 'permission' ] as $file) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$file.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
        $ci->schema()
            ->createTableIfNotExists('node', static function (TableBuilder $table): void {
                $table->increments('id')
                ->string('date_changed')
                ->string('date_created')
                ->integer('entity_id')->nullable()
                ->string('meta_description')->valueDefault('')
                ->boolean('meta_noarchive')->valueDefault(false)
                ->boolean('meta_nofollow')->valueDefault(false)
                ->boolean('meta_noindex')->valueDefault(false)
                ->string('meta_title')->valueDefault('')
                ->integer('node_status_id')->valueDefault(3)
                ->boolean('sticky')->valueDefault(false)
                ->string('title')
                ->string('type', 32)
                ->integer('user_id')->nullable();
            })
            ->createTableIfNotExists('node_type', static function (TableBuilder $table): void {
                $table->string('node_type')
                ->string('node_type_name')
                ->string('node_type_icon')
                ->text('node_type_description')
                ->string('node_type_color', 7)->valueDefault('#ddd');
            })
            ->createTableIfNotExists('node_status', static function (TableBuilder $table): void {
                $table->increments('node_status_id')
                ->text('node_status_name');
            })
            ->createTableIfNotExists('field', static function (TableBuilder $table): void {
                $table->increments('field_id')
                ->string('field_name')
                ->string('field_type');
            })
            /* Table pivot. */
            ->createTableIfNotExists('node_type_field', static function (TableBuilder $table): void {
                $table->string('node_type')
                ->integer('field_id')
                ->string('field_label')
                ->string('field_rules')
                /* Si la donnée doit-être affichée. */
                ->boolean('field_show')->valueDefault(true)
                /* Si la donnée doit-être affichée dans le formulaire. */
                ->boolean('field_show_form')->valueDefault(true)
                /* Si le label doit-être affiché. */
                ->boolean('field_show_label')->valueDefault(false)
                ->text('field_description')->valueDefault('')
                ->text('field_option')->valueDefault('')
                ->text('field_default_value')->nullable()
                /* Poisition du champ. */
                ->integer('field_weight')->valueDefault(1)
                /* Poisition de la donnée dans l'affichage. */
                ->integer('field_weight_form')->valueDefault(1);
            })
            ->createTableIfNotExists('entity_page', static function (TableBuilder $table): void {
                $table->increments('page_id')
                ->text('body');
            })
            ->createTableIfNotExists('entity_page_private', static function (TableBuilder $table): void {
                $table->increments('page_private_id')
                ->text('body');
            });

        $ci->query()->insertInto('node_status', [
                'node_status_id', 'node_status_name'
            ])
            ->values([ 1, 'Published' ])
            ->values([ 2, 'Pending publication' ])
            ->values([ 3, 'Draft' ])
            ->values([ 4, 'Archived' ])
            ->execute();

        $ci->query()->insertInto('node_type', [
                'node_type',
                'node_type_name',
                'node_type_description',
                'node_type_icon',
                'node_type_color'
            ])
            ->values([
                'page',
                'Page',
                'Use the pages for your static content.',
                'fa fa-file',
                '#7fff88'
            ])
            ->values([
                'page_private',
                'Private page',
                'Use private pages for content reserved for your members.',
                'far fa-file',
                '#005706'
            ])
            ->execute();

        $ci->query()->insertInto('field', [
                'field_name', 'field_type'
            ])
            ->values([ 'body', 'textarea' ])
            ->values([ 'image', 'image' ])
            ->values([ 'summary', 'textarea' ])
            ->values([ 'reading_time', 'number' ])
            ->values([ 'weight', 'number' ])
            ->execute();

        $ci->query()
            ->insertInto('node_type_field', [
                'node_type', 'field_id', 'field_label', 'field_weight', 'field_rules',
                'field_option'
            ])
            ->values([ 'page', 1, 'Body', 2, '!required|string', '' ])
            ->values([ 'page_private', 1, 'Body', 2, '!required|string', '' ])
            ->execute();

        $ci->config()
            ->set('settings.node_default_url', ':node_type/:node_title')
            ->set('settings.node_url_page_private', 'page/:node_title')
            ->set('settings.node_cron', false);
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
                'key', 'icon', 'title_link', 'link', 'link_router', 'menu', 'weight', 'parent'
            ])
            ->values([
                'node.admin', 'fa fa-file', 'Contents', 'admin/node', null, 'menu-admin',
                2, -1
            ])
            ->values([
                'node.show', null, 'Home', '/',  'node/3', 'menu-main', 1, -1
            ])
            ->execute();

        $ci->schema()
            ->createTableIfNotExists('node_menu_link', static function (TableBuilder $table): void {
                $table->integer('node_id')
                ->integer('menu_link_id');
            });
    }

    public function hookInstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            /* Admin */
            ->values([ 3, 'node.administer' ])
            ->values([ 3, 'node.user.edit' ])
            /* Utilisateur */
            ->values([ 2, 'node.show.own' ])
            ->values([ 2, 'node.cloned.own' ])
            ->values([ 2, 'node.edited.own' ])
            ->values([ 2, 'node.deleted.own' ])
            ->values([ 2, 'node.show.published.page_private' ])
            ->values([ 2, 'node.show.published.page' ])
            /* Non utilisateur. */
            ->values([ 1, 'node.show.published.page' ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        $types = $ci->query()->from('node_type')->lists('node_type');
        foreach ($types as $type) {
            $ci->schema()->dropTableIfExists('entity_' . $type);
        }

        foreach ([ 'node_type_field', 'field', 'node_type', 'node', 'node_status' ] as $table) {
            $ci->schema()->dropTableIfExists($table);
        }

        $ci->query()->from('system_alias_url')
            ->delete()
            ->where('source', 'like', 'node%')
            ->execute();
    }

    public function hookUninstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Block')) {
            $this->hookUninstallBlock($ci);
        }

        $this->hookUninstallMenu($ci);

        if ($ci->module()->has('User')) {
            $this->hookUninstallUser($ci);
        }
    }

    public function hookUninstallBlock(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('block')
            ->delete()
            ->where('hook', 'like', 'node.%')
            ->execute();
    }

    public function hookUninstallMenu(ContainerInterface $ci): void
    {
        $ci->schema()->dropTableIfExists('node_menu_link');

        if ($ci->module()->has('Menu')) {
            $ci->menu()->deleteLinks(static function () use ($ci): array {
                return $ci->query()
                        ->from('menu_link')
                        ->where('key', 'like', 'node%')
                        ->orWhere('key', 'like', 'entity%')
                        ->fetchAll();
            });
        }
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'node%')
            ->execute();
    }
}
