<?php

declare(strict_types=1);

namespace SoosyzeCore\Block;

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
        foreach ([ 'main', 'permission' ] as $file) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$file.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
        $ci->schema()
            ->createTableIfNotExists('block', static function (TableBuilder $table): void {
                $table->increments('block_id')
                ->string('title')
                ->string('section')
                ->text('content')->nullable()
                ->string('class')->valueDefault('')
                ->text('hook')->nullable()
                ->integer('weight')
                ->boolean('visibility_pages')->valueDefault(false)
                ->string('pages')->valueDefault('admin/%' . PHP_EOL . 'user/%')
                ->boolean('visibility_roles')->valueDefault(true)
                ->string('roles')->valueDefault('1,2')
                ->string('key_block')->nullable()
                ->string('options')->nullable();
            });

        $ci->config()->set('settings.icon_socials', [
            'blogger'    => '',
            'dribbble'   => '',
            'facebook'   => '#',
            'github'     => '',
            'instagram'  => '#',
            'linkedin'   => '#',
            'mastodon'   => '#',
            'snapchat'   => '',
            'soundcloud' => '',
            'spotify'    => '',
            'steam'      => '',
            'tumblr'     => '',
            'twitch'     => '#',
            'twitter'    => '#',
            'youtube'    => '#'
        ]);
    }

    public function seeders(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('block', [
                'section', 'title',
                'weight', 'visibility_pages', 'pages',
                'content'
            ])
            ->values([
                'content_footer', '',
                50, true, 'admin/%',
                '<div class="block-report_github">'
                . '<p>'
                . '<a href="https://github.com/soosyze/soosyze/issues" '
                . 'rel="noopener noreferrer" '
                . 'target="_blank" '
                . 'title="' . t('Found an bug? Please report it on GitHub.') . '">'
                . '<i class="fa fa-fw fa-bug" aria-hidden="true"></i> '
                . t('Report a bug.')
                . '</a>'
                . '</p>'
                . '</div>'
            ])
            ->values([
                'footer', '',
                50, false, '',
                '<p>Power by <a href="https://soosyze.com">SoosyzeCMS</a></p>',
            ])
            ->execute();
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
            ->values([ 3, 'block.administer' ])
            ->values([ 3, 'block.created' ])
            ->values([ 3, 'block.edited' ])
            ->values([ 3, 'block.deleted' ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        $ci->schema()->dropTableIfExists('block');
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
                    ->where('key', 'like', 'block%')
                    ->fetchAll();
        });
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', 'block.%')
            ->execute();
    }
}
