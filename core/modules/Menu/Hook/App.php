<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Hook;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soosyze\App as SoosyzeApp;
use SoosyzeCore\Menu\Services\Menu;
use SoosyzeCore\Template\Services\Templating;

class App
{
    /**
     * @var SoosyzeApp
     */
    private $core;

    /**
     * @var Menu
     */
    private $menu;

    public function __construct(SoosyzeApp $core, Menu $menu)
    {
        $this->core = $core;
        $this->menu = $menu;
    }

    public function hookResponseAfter(RequestInterface $request, ResponseInterface &$response): void
    {
        if (!($response instanceof Templating)) {
            return;
        }
        $nameMenu = $response->isTheme('theme')
            ? 'menu-main'
            : 'menu-admin';

        $response
            ->addBlock('page.main_menu', $this->menu->renderMenu($nameMenu))
            ->addBlock('page.second_menu', $this->menu->renderMenu('menu-user'));
    }

    public function hookMenuShowResponseAfter(RequestInterface $request, ResponseInterface &$response): void
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $vendor = $this->core->getPath('modules', 'modules/core', false);

        $response->addScript('menu', "$vendor/Menu/Assets/js/menu.js");
    }
}
