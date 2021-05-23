<?php

namespace SoosyzeCore\Menu\Services;

use Soosyze\Components\Http\Uri;

class Menu
{
    /**
     * @var \SoosyzeCore\System\Services\Alias
     */
    private $alias;

    /**
     * @var \Soosyze\Config
     */
    private $config;

    /**
     * @var \Soosyze\App
     */
    private $core;

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    /**
     * @var \Soosyze\Components\Router\Router
     */
    private $router;

    public function __construct($alias, $config, $core, $query, $router)
    {
        $this->alias  = $alias;
        $this->config = $config;
        $this->core   = $core;
        $this->query  = $query;
        $this->router = $router;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function getPathViews()
    {
        return $this->pathViews;
    }

    public function find($id)
    {
        return $this->query
                ->from('menu_link')
                ->where('id', '==', $id)
                ->fetch();
    }

    public function deleteLinks(callable $callable)
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

    public function getMenu($name)
    {
        return $this->query
                ->from('menu')
                ->where('name', '=', $name);
    }

    public function getAllMenu()
    {
        return $this->query
                ->from('menu')
                ->fetchAll();
    }

    public function getLinkPerMenu($name)
    {
        $menu = $this->getMenu($name)->fetch();

        return $this->query
                ->from('menu_link')
                ->where('menu', '==', $menu[ 'name' ]);
    }

    public function getInfo($link, $request)
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

        $isRewrite = $this->router->isRewrite();
        $uri       = Uri::create(($isRewrite
                ? ''
                : 'q=') . $link);
        if (!$isRewrite) {
            $parse = parse_url("?q=$link");
            parse_str($parse[ 'query' ], $result);

            $uri = $uri->withPath($result[ 'q' ]);
            unset($result[ 'q' ]);
            $uri = $uri->withQuery(http_build_query($result));
        }

        $linkSource = $this->alias->getSource($uri->getPath(), $uri->getPath());

        $uriSource = $isRewrite
            ? $uri->withPath($linkSource)
            : $uri->withQuery('q=' . $linkSource);

        $route = $this->router->parse($request->withUri($uriSource)->withMethod('get'));

        return [
            'key'         => isset($route[ 'key' ])
                ? $route[ 'key' ]
                : null,
            'link'        => $uri->getPath(),
            'link_router' => isset($route[ 'key' ]) && $linkSource !== $uri->getPath()
                ? $linkSource
                : null,
            'query'       => $uri->getQuery(),
            'fragment'    => $uri->getFragment(),
        ];
    }

    public function renderMenuSelect($nameMenu, $parent = -1, $level = 1)
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

    public function renderMenu($nameMenu, $parent = -1, $level = 1)
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

        return $this->core
                ->get('template')
                ->createBlock('menu.php', $this->pathViews)
                ->addNameOverride($nameMenu . '.php')
                ->addVars([
                    'level' => $level,
                    'menu'  => $this->getGrantedLink($query)
        ]);
    }

    public function rewiteUri($link)
    {
        $basePath = $this->core->getRequest()->getBasePath();
        $uri      = Uri::create($basePath)->withFragment($link[ 'fragment' ]);

        if ($this->router->isRewrite()) {
            return $uri->withPath($link[ 'link' ])
                    ->withQuery($link[ 'query' ]);
        }

        $path = $link[ 'link' ] === '/'
            ? ''
            : 'q=' . $link[ 'link' ];

        $query = $path && $link[ 'query' ]
            ? '&' . $link[ 'query' ]
            : $link[ 'query' ];

        return $uri->withQuery($path . $query);
    }

    /**
     * Retire les liens restreins dans un menu et définit le lien courant.
     *
     * @param array $query liens du menu
     *
     * @return array
     */
    private function getGrantedLink($query)
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

            $link = $request->withUri(
                $this->router->isRewrite()
                ? $request->getUri()->withPath($linkRouter)
                : $request->getUri()->withQuery('q=' . $linkRouter)
            );

            /* Test avec un hook si le menu doit-être affiché à partir du lien du menu. */
            if (!$this->core->callHook('app.granted.request', [ $link ])) {
                unset($query[ $key ]);

                continue;
            }

            $menu[ 'link_active' ] = strpos($route, $menu[ 'link' ]) === 0
                ? 'active'
                : '';

            $menu[ 'link' ] = $this->rewiteUri($menu);
        }
        unset($menu);

        return $query;
    }
}
