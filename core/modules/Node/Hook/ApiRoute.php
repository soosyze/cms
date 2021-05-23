<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Hook;

use Soosyze\Components\Router\Router;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\System\Services\Alias;

class ApiRoute implements \SoosyzeCore\System\ApiRouteInterface
{
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Alias $alias, Query $query, Router $router)
    {
        $this->alias  = $alias;
        $this->query  = $query;
        $this->router = $router;
    }

    public function apiRoute(array &$routes, string $search, string $exclude, int $limit): void
    {
        $nodes = $this->query
            ->from('node')
            ->where('title', '!=', $exclude)
            ->where('title', 'ilike', "%$search%")
            ->limit($limit)
            ->fetchAll();

        foreach ($nodes as $node) {
            $alias = $this->alias->getAlias("node/{$node[ 'id' ]}", "node/{$node[ 'id' ]}");

            $routes[] = [
                'link'  => $this->router->makeRoute($alias),
                'route' => $alias,
                'title' => $node[ 'title' ]
            ];
        }
    }
}
