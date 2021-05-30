<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;

class Tool extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin(): ResponseInterface
    {
        $tools = [];
        $this->container->callHook('tools.admin', [ &$tools ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        foreach ($tools as $key => &$tool) {
            if (!self::user()->isGrantedRequest($tool[ 'link' ])) {
                unset($tools[ $key ]);

                continue;
            }
            $tool[ 'link' ] = $tool[ 'link' ]->getUri();
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-tools" aria-hidden="true"></i>',
                    'title_main' => t('Tools')
                ])
                ->make('page.content', 'system/content-tools-admin.php', $this->pathViews, [
                    'is_granted_action' => self::user()->isGranted('system.tool.action'),
                    'link_cron'         => self::router()->getRoute('system.tool.cron'),
                    'link_trans'        => self::router()->getRoute('system.tool.trans'),
                    'tools'             => $tools
                ])
                ->view('page.messages', $messages);
    }

    public function cron(ServerRequestInterface $req): ResponseInterface
    {
        $this->container->callHook('app.cron', [ $req ]);

        $_SESSION[ 'messages' ][ 'success' ] = [ t('The cron task has been successfully executed') ];

        return new Redirect(self::router()->getRoute('system.tool.admin'), 302);
    }

    public function updateTranslations(): ResponseInterface
    {
        $extensions   = array_column(self::module()->listModuleActive(), 'title');
        $extensions[] = self::config()->get('settings.theme');
        $extensions[] = self::config()->get('settings.theme_admin');

        $composers = self::composer()->getModuleComposers() + self::composer()->getThemeComposers();

        $composersActive = [];
        foreach ($extensions as $title) {
            $extendClass = self::composer()->getExtendClass($title, $composers);
            $extend      = new $extendClass();

            $extend->boot();

            $composersActive[ $title ] = $composers[ $title ] + [
                'translations' => $extend->getTranslations()
            ];
        }

        self::module()->loadTranslations($composersActive);

        $_SESSION[ 'messages' ][ 'success' ] = [ t('The translation files have been updated') ];

        return new Redirect(self::router()->getRoute('system.tool.admin'), 302);
    }
}
