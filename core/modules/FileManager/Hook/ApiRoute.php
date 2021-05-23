<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Hook;

use Soosyze\Components\Router\Router;
use Soosyze\Config;
use SoosyzeCore\System\Services\Alias;

class ApiRoute implements \SoosyzeCore\System\ApiRouteInterface
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
     * @var Router
     */
    private $router;

    public function __construct(Alias $alias, Config $config, Router $router)
    {
        $this->alias  = $alias;
        $this->config = $config;
        $this->router = $router;
    }

    public function apiRoute(array &$routes, string $search, string $exclude, int $limit): void
    {
        $title = t('Public file manager');
        if ($title === $exclude || stristr($title, $search) === false) {
            return;
        }

        $routes[] = [
            'link'  => $this->router->getRoute(
                'filemanager.public',
                [ ':path' => '/download' ]
            ),
            'route' => $this->alias->getAlias(
                'filemanager/public/download',
                'filemanager/public/download'
            ),
            'title' => $title
        ];
    }
}
