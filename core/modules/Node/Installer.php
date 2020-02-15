<?php

namespace SoosyzeCore\Node;

use Psr\Container\ContainerInterface;
use Queryflatfile\TableBuilder;

class Installer implements \SoosyzeCore\System\Migration
{
    public function getDir()
    {
        return __DIR__;
    }

    public function install(ContainerInterface $ci)
    {
        $ci->schema()
            ->createTableIfNotExists('node', function (TableBuilder $table) {
                $table->increments('id')
                ->string('date_changed')
                ->string('date_created')
                ->integer('entity_id')->nullable()
                ->boolean('meta_noarchive')->valueDefault(false)
                ->boolean('meta_nofollow')->valueDefault(false)
                ->boolean('meta_noindex')->valueDefault(false)
                ->boolean('published')
                ->string('title')
                ->string('type');
            })
            ->createTableIfNotExists('node_type', function (TableBuilder $table) {
                $table->string('node_type')
                ->string('node_type_name')
                ->text('node_type_description');
            })
            ->createTableIfNotExists('field', function (TableBuilder $table) {
                $table->increments('field_id')
                ->string('field_name')
                ->string('field_type');
            })
            /* Table pivot. */
            ->createTableIfNotExists('node_type_field', function (TableBuilder $table) {
                $table->string('node_type')
                ->integer('field_id')
                ->string('field_label')
                ->string('field_rules')->valueDefault('')
                ->boolean('field_show')->valueDefault(true)
                ->text('field_description')->valueDefault('')
                ->text('field_option')->valueDefault('')
                ->text('field_default_value')->nullable()
                ->integer('field_weight')->valueDefault(1)
                ->integer('field_weight_show')->valueDefault(1);
            })
            ->createTableIfNotExists('entity_page', function (TableBuilder $table) {
                $table->increments('page_id')
                ->text('body');
            });

        $ci->query()->insertInto('node_type', [
                'node_type', 'node_type_name', 'node_type_description'
            ])
            ->values([ 'page', 'Page', 'Use the pages for your static content.' ])
            ->execute();

        $ci->query()->insertInto('field', [
                'field_name', 'field_type'
            ])
            ->values([ 'body', 'textarea'])
            ->values([ 'image', 'image'])
            ->values([ 'summary', 'textarea' ])
            ->execute();

        $ci->query()
            ->insertInto('node_type_field', [
                'node_type', 'field_id', 'field_label', 'field_weight', 'field_rules', 'field_option'
            ])
            ->values([ 'page', 1, 'Body', 2, '!required|string', ''])
            ->execute();
    }

    public function seeders(ContainerInterface $ci)
    {
    }

    public function hookInstall(ContainerInterface $ci)
    {
        $this->hookInstallUser($ci);
        $this->hookInstallMenu($ci);
    }

    public function hookInstallUser(ContainerInterface $ci)
    {
        if ($ci->module()->has('User')) {
            $ci->query()
                ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
                ->values([ 3, 'node.show.not_published' ])
                ->values([ 3, 'node.show.published' ])
                ->values([ 3, 'node.administer' ])
                ->values([ 3, 'node.index' ])
                ->values([ 2, 'node.show.published' ])
                ->values([ 1, 'node.show.published' ])
                ->execute();
        }
    }

    public function hookInstallMenu(ContainerInterface $ci)
    {
        if ($ci->module()->has('Menu')) {
            $ci->query()
                ->insertInto('menu_link', [
                    'key', 'icon', 'title_link', 'link', 'menu', 'weight', 'parent'
                ])
                ->values([
                    'node.index', 'fa fa-file', 'Contents', 'admin/node', 'menu-admin',
                    2, -1
                ])
                ->values([
                    'node.show', null, 'Home', '/', 'menu-main', 1, -1
                ])
                ->values([
                    'node.show', 'fa fa-home', 'Home', '/', 'menu-admin', 1, -1
                ])
                ->execute();

            $ci->schema()
                ->createTableIfNotExists('node_menu_link', function (TableBuilder $table) {
                    $table->integer('node_id')
                    ->integer('menu_link_id');
                });
        }
    }

    public function uninstall(ContainerInterface $ci)
    {
        $types = $ci->query()->from('node_type')->lists('node_type');
        foreach ($types as $type) {
            $ci->schema()->dropTable('entity_' . $type);
        }
        $ci->schema()->dropTable('node_type_field');
        $ci->schema()->dropTable('field');
        $ci->schema()->dropTable('node_type');
        $ci->schema()->dropTable('node');
    }

    public function hookUninstall(ContainerInterface $ci)
    {
        $this->hookUninstallMenu($ci);
        $this->hookUninstallUser($ci);
    }

    public function hookUninstallMenu(ContainerInterface $ci)
    {
        if ($ci->schema()->hasTable('node_menu_link')) {
            $ci->schema()->dropTable('node_menu_link');
        }
        if ($ci->module()->has('Menu')) {
            $ci->query()
                ->from('menu_link')
                ->delete()
                ->orWhere('key', 'like', 'node%')
                ->execute();
        }
    }

    public function hookUninstallUser(ContainerInterface $ci)
    {
        if ($ci->module()->has('User')) {
            $ci->query()
                ->from('role_permission')
                ->delete()
                ->where('permission_id', 'like', 'node%')
                ->execute();
        }
    }
}
