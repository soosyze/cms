<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Hook;

use Core;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Http\Uri;
use Soosyze\Components\Router\Router;
use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\System\Services\Alias;
use SoosyzeCore\Template\Services\Templating;

class App
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
    private $tpl;

    public function __construct(
        Alias $alias,
        Config $config,
        Core $core,
        Query $query,
        Router $router,
        Templating $template
    ) {
        $this->alias  = $alias;
        $this->config = $config;
        $this->core   = $core;
        $this->query  = $query;
        $this->router = $router;
        $this->tpl    = $template;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function hookSys(RequestInterface &$request, ResponseInterface &$response): void
    {
        $path = $this->router->parseQueryFromRequest();
        $path = $this->alias->getSource($path, $path);

        $request = $request
            ->withUri(
                Uri::create($this->router->getBasePath() . $path)
            );

        if (
            $this->config->get('settings.maintenance') &&
            $path !== 'user/login' &&
            !$this->core->callHook('app.granted', [ 'system.config.maintenance' ])) {
            $response = $response->withStatus(503);
        }
    }

    public function hooks404(RequestInterface $request, ResponseInterface &$response): void
    {
        if (($path = $this->config->get('settings.path_no_found', '')) !== '') {
            $path = $this->alias->getSource($path, $path);

            $requestNoFound = $request
                ->withUri(
                    Uri::create($this->router->getBasePath() . $path)
                )
                ->withMethod('GET');

            if ($route = $this->router->parse($requestNoFound)) {
                $responseNoFound = $this->router->execute($route, $requestNoFound);
            }
        }

        /*
         * Si il n'y a aucune réponse ou que la réponse est déjà une page 404,
         * une réponse sera construite à partir d'un template,
         * sinon renvoie la réponse 404 de base.
         */
        $response = empty($responseNoFound) || $responseNoFound->getStatusCode() === 404
            ? $this->tpl
                ->view('page', [
                    'title_main' => t('Not Found')
                ])
                ->make('page.content', 'page-404.php', $this->pathViews, [
                    'uri' => $request->getUri()
                ])
            : $responseNoFound;

        if (!$response instanceof Redirect) {
            $response = $response->withStatus(404);
        }
    }

    public function hooks403(RequestInterface $request, ResponseInterface &$response): void
    {
        if (($path = $this->config->get('settings.path_access_denied', '')) !== '') {
            $path = $this->alias->getSource($path, $path);

            $requestDenied = $request
                ->withUri(
                    Uri::create($this->router->getBasePath() . $path)
                )
                ->withMethod('GET');

            if ($route = $this->router->parse($requestDenied)) {
                $responseDenied = $this->router->execute($route, $requestDenied);
            }
        }

        $response = empty($responseDenied) || $responseDenied->getStatusCode() === 404
            ? $this->tpl
                ->view('page', [
                    'title_main' => t('Page Forbidden')
                ])
                ->make('page.content', 'page-403.php', $this->pathViews, [
                    'uri' => $request->getUri()
                ])
            : $responseDenied;

        if (!$response instanceof Redirect) {
            $response = $response->withStatus(403);
        }
    }

    public function hooks503(RequestInterface $request, ResponseInterface &$response): void
    {
        if (($path = $this->config->get('settings.path_maintenance', '')) !== '') {
            $path = $this->alias->getSource($path, $path);

            $requestMaintenance = $request
                ->withUri(
                    Uri::create($this->router->getBasePath() . $path)
                )
                ->withMethod('GET');

            if ($route = $this->router->parse($requestMaintenance)) {
                $responseMaintenance = $this->router->execute($route, $requestMaintenance);
            }
        }

        if (empty($responseMaintenance) || in_array($responseMaintenance->getStatusCode(), [
                403, 404 ])) {
            $response = $this->tpl
                ->getTheme()
                ->make('page', 'page-maintenance.php', $this->pathViews, [
                    'icon'       => '<i class="fa fa-cog" aria-hidden="true"></i>',
                    'title_main' => t('Site under maintenance'),
                ]);
        } else {
            $content = $responseMaintenance->getBlock('page.content');
            $response = $this->tpl
                ->getTheme()
                ->make('page', 'page-maintenance.php', $this->pathViews, [
                    'icon'       => '<i class="fa fa-cog" aria-hidden="true"></i>',
                    'title_main' => t('Site under maintenance'),
                ])
                ->addBlock('page.content', $content);
        }

        if (!$response instanceof Redirect) {
            $response = $response->withStatus(503);
        }
    }

    public function hookResponseAfter(RequestInterface $request, ResponseInterface &$response): void
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $metaTitle       = $this->config->get('settings.meta_title', 'Soosyze CMS');
        $metaDescription = $this->config->get('settings.meta_description', '');
        $favicon         = $this->config->get('settings.favicon', '')
            ? $this->router->getBasePath() . $this->config->get('settings.favicon')
            : '';

        $logo        = $this->config->get('settings.logo', '');
        $maintenance = $this->config->get('settings.maintenance', false);

        $vendor = $this->core->getPath('modules', 'core/modules', false) . '/System/Assets';

        $html      = $response->getBlock('this');
        $siteDesc  = $html->getVar('description');
        $siteTitle = $html->getVar('title');
        $pageTitle = $response->getBlock('page')->getVar('title_main');

        $title = $metaTitle;
        if ($siteTitle) {
            $title = str_replace(
                [ ':site_description', ':site_title', ':page_title' ],
                [ $metaDescription, $metaTitle, $pageTitle ],
                $siteTitle
            );
        } elseif ($pageTitle) {
            $title = "$pageTitle | $title";
        }

        $description = $siteDesc
            ? str_replace(
                [ ':site_description', ':site_title', ':page_title' ],
                [ $metaDescription, $metaTitle, $pageTitle ],
                $siteDesc
            )
            : $metaDescription;

        $response->view('this', [
                'favicon'     => $favicon,
                'title'       => $title
            ])
            ->addScript('system', "$vendor/js/system.js")
            ->addStyle('system', "$vendor/css/system.css")
            ->addMetas([
                [
                    'name'    => 'keyboard',
                    'content' => $this->config->get('settings.meta_keyboard', '')
                ], [
                    'name'    => 'description',
                    'content' => $description
                ], [
                    'name'    => 'generator',
                    'content' => 'Soosyze CMS'
                ]
            ])
            ->view('page', [
                'logo'  => is_file(ROOT . $logo)
                    ? $request->getBasePath() . $logo
                    : $logo,
                'title' => $metaTitle
        ]);

        $granted = $this->core->callHook('app.granted', [ 'system.config.maintenance' ]);
        if ($maintenance && $granted) {
            $response->view('page.messages', [ 'infos' => [ t('Site under maintenance') ] ]);
        }
        if ($this->router->parseQueryFromRequest() === '/' &&
            (!$maintenance ||
            ($maintenance && $granted))) {
            $response->getBlock('page')->setNamesOverride([ 'page-front.php' ]);
        }
    }
}
