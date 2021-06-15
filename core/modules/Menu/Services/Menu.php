<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Services;

use Core;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Soosyze\Components\Http\Uri;
use Soosyze\Components\Router\Router;
use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\System\Services\Alias;
use SoosyzeCore\Template\Services\Block;
use SoosyzeCore\Template\Services\Templating;

class Menu
{
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Templating
     */
    private $templating;

    public function __construct(Alias $alias, Config $config, Core $core, Query $query, Router $router, Templating $templating)
    {
        $this->alias      = $alias;
        $this->config     = $config;
        $this->core       = $core;
        $this->query      = $query;
        $this->router     = $router;
        $this->templating = $templating;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function getPathViews(): string
    {
        return $this->pathViews;
    }

    public function find(int $id): array
    {
        return $this->query
                ->from('menu_link')
                ->where('id', '==', $id)
                ->fetch();
    }

    public function deleteLinks(callable $callable): void
    {
        $links = $callable();

        foreach ($links as $link) {
            $this->query
                ->from('menu_link')
                ->delete()
                ->where('id', '=', $link['id'])
                ->execute();
            $this->query
                ->update('menu_link', [ 'parent' => $link[ 'parent' ] ])
                ->where('parent', '=', $link['id'])
                ->execute();
        }
    }

    public function getMenu(string $name): Query
    {
        return $this->query
                ->from('menu')
                ->where('name', '=', $name);
    }

    public function getAllMenu(): array
    {
        return $this->query
                ->from('menu')
                ->fetchAll();
    }

    public function getLinkPerMenu(string $name): Query
    {
        $menu = $this->getMenu($name)->fetch();

        return $this->query
                ->from('menu_link')
                ->where('menu', '==', $menu[ 'name' ]);
    }

    public function getInfo(string $link, RequestInterface $request): array
    {
        if (filter_var($link, FILTER_VALIDATE_URL)) {
            return [
                'fragment'    => '',
                'key'         => '',
                'link'        => $link,
                'link_router' => null,
                'query'       => ''
            ];
        }

        $uri        = Uri::create($link);
        $linkSource = $this->alias->getSource($uri->getPath(), $uri->getPath());
        $uriSource  = $uri->withPath($linkSource);

        $route = $this->router->parse($request->withUri($uriSource)->withMethod('get'));

        return [
            'key'         => $route[ 'key' ] ?? null,
            'link'        => $uri->getPath(),
            'link_router' => isset($route[ 'key' ]) && $linkSource !== $uri->getPath()
                ? $linkSource
                : null,
            'query'       => $uri->getQuery(),
            'fragment'    => $uri->getFragment(),
        ];
    }

    public function renderMenuSelect(string $nameMenu, int $parent = -1, int $level = 1): array
    {
        $query = $this->query
            ->from('menu_link')
            ->where('active', '==', 1)
            ->where('menu', '=', $nameMenu)
            ->where('parent', '==', $parent)
            ->orderBy('weight')
            ->fetchAll();

        if (empty($query)) {
            return [];
        }

        $options = $level === 1
            ? [ [ 'label' => '« ' . t('Root') . ' »', 'value' => -1] ]
            : [];

        $space = str_repeat('│··· ', $level - 1);
        $count = count($query) - 1;

        foreach ($query as $key => $menu) {
            $seperator = $count === $key
                ? '└─ '
                : '├─ ';

            $options[] = [
                'label' => $space . $seperator . t($menu[ 'title_link' ]),
                'value' => $menu[ 'id' ]
            ];

            $options = array_merge(
                $options,
                ($menu[ 'has_children' ]
                    ? $this->renderMenuSelect($nameMenu, $menu[ 'id' ], $level + 1)
                    : [])
            );
        }

        return $options;
    }

    public function renderMenu(string $nameMenu, int $parent = -1, int $level = 1): ?Block
    {
        $query = $this->query
            ->from('menu_link')
            ->where('active', '==', 1)
            ->where('menu', '=', $nameMenu)
            ->where('parent', '==', $parent)
            ->orderBy('weight')
            ->fetchAll();

        if (empty($query)) {
            return null;
        }

        foreach ($query as &$link) {
            $link[ 'title_link' ] = t($link[ 'title_link' ]);
            $link[ 'submenu' ]    = $link['has_children']
                ? $this->renderMenu($nameMenu, $link[ 'id' ], $level + 1)
                : null;
        }
        unset($link);

        return $this->templating
                ->createBlock('menu.php', $this->pathViews)
                ->addNameOverride($nameMenu . '.php')
                ->addVars([
                    'level' => $level,
                    'menu'  => $this->getGrantedLink($query)
        ]);
    }

    public function rewiteUri(string $link, ?string $query, ?string $fragment): UriInterface
    {
        $basePath = $this->core->getRequest()->getBasePath() . trim($link, '//');

        return Uri::create($basePath)
                ->withQuery($query)
                ->withFragment($fragment);
    }

    /**
     * Retire les liens restreins dans un menu et définit le lien courant.
     *
     * @param array $query liens du menu
     *
     * @return array
     */
    private function getGrantedLink(array $query): array
    {
        $route   = $this->router->parseQueryFromRequest();
        $request = $this->core->getRequest()->withMethod('GET');

        foreach ($query as $key => &$menu) {
            if (!$menu[ 'key' ]) {
                $menu[ 'link_active' ] = '';

                continue;
            }

            $linkRouter = $menu[ 'link_router' ]
                ? $menu[ 'link_router' ]
                : $menu[ 'link' ];

            $link = $request->withUri($this->rewiteUri($linkRouter, $menu['query'], $menu['fragment']));

            /* Test avec un hook si le menu doit-être affiché à partir du lien du menu. */
            if (!$this->core->callHook('app.granted.request', [ $link ])) {
                unset($query[ $key ]);

                continue;
            }

            $menu[ 'link_active' ] = strpos($route, $menu[ 'link' ]) === 0
                ? 'active'
                : '';

            $menu[ 'link' ] = $this->rewiteUri($menu[ 'link' ], $menu['query'], $menu['fragment']);
        }
        unset($menu);

        return $query;
    }
}
