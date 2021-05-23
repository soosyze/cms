<?php

declare(strict_types=1);

namespace SoosyzeCore\BackupManager\Hook;

use Soosyze\Components\Router\Router;

class Tool implements \SoosyzeCore\System\ToolInterface
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function hookToolAdmin(array &$tools): void
    {
        $tools[ 'backupmanager' ] = [
            'description' => 'Backup of the CMS',
            'icon'        => [
                'name'             => 'fa fa-file-archive',
                'background-color' => '#ac1b1b',
                'color'            => '#fff'
            ],
            'link'        => $this->router->getRequestByRoute('backupmanager.admin'),
            'title'       => 'BackupManager'
        ];
    }
}
