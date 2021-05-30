<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteApi extends \Soosyze\Controller
{
    const LIMIT_ROUTE = 5;

    public function index(ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $get = $req->getQueryParams();

        $search  = $get[ 'title' ] ?? '';
        $exclude = $get[ 'exclude' ] ?? '';
        $limit   = $get[ 'limit' ] ?? self::LIMIT_ROUTE;

        $routes = [];
        $this->container->callHook('api.route', [ &$routes, $search, $exclude, $limit ]);

        if (empty($routes)) {
            $routes[] = [
                'title' => t('No results found'),
                'route' => '',
                'link'  => '#'
            ];
        }

        usort($routes, static function ($a, $b) use ($search) {
            $aPos = stripos($a[ 'title' ], $search);
            $bPos = stripos($b[ 'title' ], $search);

            if ($aPos == $bPos) {
                return 0;
            }

            return ($aPos < $bPos)
                ? -1
                : 1;
        });

        return $this->json(
            200,
            array_slice($routes, 0, self::LIMIT_ROUTE)
        );
    }
}
