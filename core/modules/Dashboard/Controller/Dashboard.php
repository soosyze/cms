<?php

declare(strict_types=1);

namespace SoosyzeCore\Dashboard\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dashboard extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function index(): ResponseInterface
    {
        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fas fa-tachometer-alt" aria-hidden="true"></i>',
                    'title_main' => t('Dashboard')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'dashboard/content-dashboard-dashboard.php', $this->pathViews, [
                    'link_info'    => self::router()->getRoute('dashboard.info'),
                    'size_backup'  => self::dashboard()->getSizeBackups(),
                    'size_data'    => self::dashboard()->getSizeDatabase(),
                    'size_file'    => self::dashboard()->getSizeFiles(),
                    'version_core' => self::composer()->getVersionCore()
        ]);
    }

    public function info(ServerRequestInterface $req): ResponseInterface
    {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();

        return self::template()
                ->getTheme('theme_admin')
                ->make('page.content', 'dashboard/content-dashboard-info.php', $this->pathViews, [
                    'info' => str_replace(
                        'module_Zend Optimizer',
                        'module_Zend_Optimizer',
                        preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info)
                    )
        ]);
    }
}
