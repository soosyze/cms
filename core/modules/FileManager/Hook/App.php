<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Hook;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soosyze\App as SoosyzeApp;
use SoosyzeCore\Template\Services\Templating;

class App
{
    /**
     * @var SoosyzeApp
     */
    private $core;

    public function __construct(SoosyzeApp $core)
    {
        $this->core = $core;
    }

    public function hookResponseAfter(RequestInterface $request, ResponseInterface &$response): void
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $vendor = $this->core->getPath('modules', 'modules/core', false) . '/FileManager/Assets';

        $response->addScript('filemanager', "$vendor/js/filemanager.js")
            ->addStyle('filemanager', "$vendor/css/filemanager.css");
    }
}
