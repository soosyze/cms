<?php

namespace SoosyzeCore\System\Services;

class HookApp
{
    protected $router;

    protected $config;

    protected $tpl;

    protected $core;

    public function __construct($route, $config, $template, $core)
    {
        $this->router  = $route;
        $this->config = $config;
        $this->tpl    = $template;
        $this->core   = $core;
        $this->views  = dirname(__DIR__) . '/Views/';
    }

    public function hookSys(&$request, &$response)
    {
        $uri = $request->getUri();

        if ($uri->getQuery() == '' || $uri->getQuery() == 'q=/') {
            $path_index = $this->config->get('settings.path_index')
                ? 'q=' . $this->config->get('settings.path_index')
                : '404';
            $url        = $uri->withQuery($path_index);

            $request = $request->withUri($url)->withMethod('GET');
        }

        if (
            $this->config->get('settings.maintenance')
            && 'q=user/login' !== $uri->getQuery()
            && !$this->core->callHook('app.granted', [ 'system.config.maintenance' ])) {
            $response = $response->withStatus(503);
        }
    }

    public function hooks404($request, &$response)
    {
        if (($path = $this->config->get('settings.path_no_found')) != '') {
            $requestNoFound = $request
                ->withUri($request->getUri()->withQuery('q=' . $path))
                ->withMethod('GET');
            if ($route          = $this->router->parse($requestNoFound)) {
                $responseNoFound = $this->router->execute($route, $requestNoFound);
            }
        }

        /*
         * Si il n'y a aucune réponse ou que la réponse est déjà une page 404,
         * une réponse sera construite à partir d'une template,
         * sinon renvoie la réponse 404 de base.
         */
        $response = empty($responseNoFound) || $responseNoFound->getStatusCode() === 404
            ? $this->tpl
                ->view('page', [
                    'title_main' => 'Page Not Found'
                ])
                ->render('page.content', 'page-404.php', $this->views, [
                    'uri' => $request->getUri()
                ])
            : $responseNoFound;

        if (!$response instanceof \Soosyze\Components\Http\Redirect) {
            $response = $response->withStatus(404);
        }
    }

    public function hooks403($request, &$response)
    {
        if (($path = $this->config->get('settings.path_access_denied')) != '') {
            $requestDenied = $request
                ->withUri($request->getUri()->withQuery('q=' . $path))
                ->withMethod('GET');
            if ($route         = $this->router->parse($requestDenied)) {
                $responseDenied = $this->router->execute($route, $requestDenied);
            }
        }

        $response = empty($responseDenied) || $responseDenied->getStatusCode() === 404
            ? $this->tpl
                ->view('page', [
                    'title_main' => 'Page Forbidden'
                ])
                ->render('page.content', 'page-403.php', $this->views, [
                    'uri' => $request->getUri()
                ])
            : $responseDenied;

        if (!$response instanceof \Soosyze\Components\Http\Redirect) {
            $response = $response->withStatus(403);
        }
    }

    public function hooks503($request, &$response)
    {
        $response = $this->tpl->render('page', 'page-maintenance.php', $this->views, [
                'title_main' => '<i class="fa fa-cog" aria-hidden="true"></i> Site en maintenance'
            ])
            ->withStatus(503);
    }

    public function hookResponseAfter($request, &$response)
    {
        if ($response instanceof \SoosyzeCore\Template\Services\Templating) {
            $data = $this->config->get('settings');
            $response->view('this', [
                'title'       => $data[ 'meta_title' ],
                'description' => $data[ 'meta_description' ],
                'keyboard'    => $data[ 'meta_keyboard' ],
                'favicon'     => $data[ 'favicon' ]
            ])->view('page', [
                'title' => $data[ 'title' ],
                'logo'  => $data[ 'logo' ]
            ]);
            $vendor = $this->router->getBasePath() . $this->core->getSetting('modules', 'modules/core') . 'System/Assets/js/script.js';
            $script = $response->getBlock('this')->getVar('scripts');
            $script .= '<script src="' . $vendor . '"></script>';
            $response->view('this', [ 'scripts' => $script ]);

            $granted = $this->core->callHook('app.granted', [ 'system.config.maintenance' ]);
            if ($data[ 'maintenance' ] && $granted) {
                $response->view('page.messages', [ 'infos' => [ 'Le site est en maintenance.' ] ]);
            }
            if (!in_array($request->getUri()->getQuery(), [ '', '/' ])) {
                return;
            }
            if (!$data[ 'maintenance' ] || ($data[ 'maintenance' ] && $granted)) {
                $response->override('page', [ 'page-front.php' ]);
            }
        }
    }
}
