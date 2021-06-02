<?php

declare(strict_types=1);

namespace SoosyzeCore\Trumbowyg\Hook;

use Core;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soosyze\Components\Router\Router;
use Soosyze\Config;
use SoosyzeCore\Template\Services\Templating;

class App
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Core $core, Config $config, Router $router)
    {
        $this->core   = $core;
        $this->config = $config;
        $this->router = $router;
    }

    public function getEditor(RequestInterface $request, ResponseInterface &$response): void
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $assets = $this->core->getPath('modules', 'core/modules', false) . '/Trumbowyg/Assets';
        $vendor = $this->core->getPath('modules', 'core/modules', false) . '/Trumbowyg/vendor/trumbowyg/dist';
        $lang   = $this->config->get('settings.lang', 'en');

        $response
            ->addConfigJs('trumbowyg', [
                'lang'       => $lang,
                'serverPath' => $this->router->getRoute('trumbowyg.upload'),
                'vendor'     => $vendor
            ])
            /* Scripts */
            ->addScript('trumbowy.editor', "$assets/js/trumbowyg.js")
            ->addScript('trumbowyg', "$vendor/trumbowyg.min.js")
            ->addScript('trumbowyg.upload', "$vendor/plugins/upload/trumbowyg.upload.min.js")
            ->addScript('trumbowyg.noembed', "$vendor/plugins/noembed/trumbowyg.noembed.min.js")
            ->addScript('trumbowyg.preformatted', "$vendor/plugins/preformatted/trumbowyg.preformatted.min.js")
            ->addScript('trumbowyg.emoji', "$vendor/plugins/emoji/trumbowyg.emoji.min.js")
            ->addScript('trumbowyg.table', "$vendor/plugins/table/trumbowyg.table.min.js")
            /* Styles */
            ->addStyle('trumbowyg', "$vendor/ui/trumbowyg.min.css")
            ->addStyle('trumbowyg.emoji', "$vendor/plugins/emoji/ui/trumbowyg.emoji.min.css")
            ->addStyle('trumbowyg.table', "$vendor/plugins/table/ui/trumbowyg.table.min.css")
            ->addStyle('trumbowy.editor', "$assets/css/trumbowyg.css");

        if ($lang !== 'en') {
            $response->addScript('trumbowyg.lang', "$vendor/langs/$lang.min.js");
        }
    }
}
