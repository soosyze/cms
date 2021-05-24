<?php

declare(strict_types=1);

namespace SoosyzeCore\News;

use Psr\Container\ContainerInterface;
use Queryflatfile\TableBuilder;
use Soosyze\Components\Template\Template;

class Extend extends \SoosyzeCore\System\ExtendModule
{
    private $pathContent;

    public function __construct()
    {
        $this->pathContent = __DIR__ . '/Views/install/';
    }

    public function getDir(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        foreach ([ 'block', 'config', 'main' ] as $file) {
            $this->loadTranslation('fr', __DIR__ . "/Lang/fr/$file.json");
        }
    }

    public function install(ContainerInterface $ci): void
    {
        $ci->schema()
            ->createTableIfNotExists('entity_article', static function (TableBuilder $table): void {
                $table->increments('article_id')
                ->string('image')
                ->text('summary')
                ->text('body')
                ->integer('reading_time')->comment('In minute');
            });
        $ci->query()->insertInto('node_type', [
                'node_type',
                'node_type_name',
                'node_type_description',
                'node_type_icon',
                'node_type_color'
            ])
            ->values([
                'node_type'             => 'article',
                'node_type_name'        => 'Article',
                'node_type_description' => 'Use articles for your news and blog posts.',
                'node_type_icon'        => 'fas fa-newspaper',
                'node_type_color'       => '#ddd'
            ])
            ->execute();

        $idImage   = $ci->query()->from('field')->where('field_name', '=', 'image')->fetch()[ 'field_id' ];
        $idSummary = $ci->query()->from('field')->where('field_name', '=', 'summary')->fetch()[ 'field_id' ];
        $idBody    = $ci->query()->from('field')->where('field_name', '=', 'body')->fetch()[ 'field_id' ];
        $idReading = $ci->query()->from('field')->where('field_name', '=', 'reading_time')->fetch()[ 'field_id' ];

        $ci->query()
            ->insertInto('node_type_field', [
                'node_type', 'field_id', 'field_weight', 'field_label', 'field_rules',
                'field_description', 'field_show_form'
            ])
            ->values([
                'article', $idImage, 1, 'Picture',
                '!required|image|max:800kb',
                'The weight of the image must be less than or equal to 800ko',
                true
            ])
            ->values([
                'article', $idSummary, 2, 'Summary',
                'required|string|max:512',
                'Briefly summarize your article in less than 512 characters',
                true
            ])
            ->values([
                'article', $idBody, 3, 'Body',
                'string',
                '',
                true
            ])
            ->values([
                'article', $idReading, 4, 'Reading time',
                'number|min:1',
                '',
                false
            ])
            ->execute();

        $ci->config()
            ->set('settings.new_title', 'Articles')
            ->set('settings.news_pagination', 6)
            ->set('settings.new_default_image', '')
            ->set('settings.new_default_icon', 'fas fa-newspaper')
            ->set('settings.new_default_color', '#fff')
            ->set('settings.node_url_article', 'news/:date_created_year/:date_created_month/:date_created_day/:node_title');
    }

    public function seeders(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('entity_article', [ 'image', 'summary', 'body', 'reading_time' ])
            ->values([
                'https://picsum.photos/id/1/650/300',
                '<p>Un article se met en valeur par un résumé qui décrit brièvement son contenu avec un nombre de caractères limité (maximum 255 caractères).</p>',
                (new Template('article_1.php', $this->pathContent))->render(),
                1
            ])
            ->values([
                'https://picsum.photos/id/11/650/300',
                '<p>Consectetur adipiscing elit. Etiam orci nulla, dignissim eu hendrerit ullamcorper, blandit et arcu.</p>',
                (new Template('article_2.php', $this->pathContent))->render(),
                1
            ])
            ->execute();

        $time = (string) time();
        $ci->query()
            ->insertInto('node', [
                'title', 'type', 'date_created', 'date_changed', 'node_status_id',
                'entity_id', 'sticky', 'user_id'
            ])
            ->values([
                'Bienvenue sur mon site', 'article', $time, $time, 1, 1, true, 1
            ])
            ->values([
                'Lorem ipsum dolor sit amet', 'article', $time, $time, 1, 2, false, 1
            ])
            ->execute();

        /* Création des Alias. */
        $idFirstNews  = $ci->query()->from('node')->where('entity_id', '=', 1)->where('type', '=', 'article')->fetch()[ 'id' ];
        $idSecondNews = $ci->query()->from('node')->where('entity_id', '=', 2)->where('type', '=', 'article')->fetch()[ 'id' ];

        $Y = date('Y', (int) $time);
        $m = date('m', (int) $time);
        $d = date('d', (int) $time);

        $ci->query()
            ->insertInto('system_alias_url', [ 'source', 'alias' ])
            ->values([ "node/$idFirstNews", "news/$Y/$m/$d/bienvenue-sur-mon-site" ])
            ->values([ "node/$idSecondNews", "news/$Y/$m/$d/lorem-ipsum-dolor-sit-amet" ])
            ->execute();
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
                'key', 'title_link', 'link', 'menu', 'weight', 'parent', 'active'
            ])
            ->values([ 'news.index', 'Blog', 'news', 'menu-main', 3, -1, false ])
            ->execute();
    }

    public function hookInstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 2, 'node.show.published.article' ])
            ->values([ 1, 'node.show.published.article' ])
            ->execute();
    }

    public function uninstall(ContainerInterface $ci): void
    {
        $ci->node()->deleteAliasByType('article');
        $ci->node()->deleteByType('article');
    }

    public function hookUninstall(ContainerInterface $ci): void
    {
        if ($ci->module()->has('Block')) {
            $this->hookUninstallBlock($ci);
        }
        if ($ci->module()->has('Menu')) {
            $this->hookUninstallMenu($ci);
        }
        if ($ci->module()->has('User')) {
            $this->hookUninstallUser($ci);
        }
    }

    public function hookUninstallBlock(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('block')
            ->delete()
            ->where('hook', 'like', 'news.%')
            ->execute();
    }

    public function hookUninstallMenu(ContainerInterface $ci): void
    {
        $ci->menu()->deleteLinks(static function () use ($ci): array {
            return $ci->query()
                    ->from('menu_link')
                    ->where('key', 'like', 'news%')
                    ->fetchAll();
        });
    }

    public function hookUninstallUser(ContainerInterface $ci): void
    {
        $ci->query()
            ->from('role_permission')
            ->delete()
            ->where('permission_id', 'like', '%article%')
            ->execute();
    }
}
