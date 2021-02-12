<?php

namespace SoosyzeCore\FileManager\Hook;

use SoosyzeCore\Template\Services\Templating;

class App
{
    /**
     * @var \Soosyze\App
     */
    private $core;

    public function __construct($core)
    {
        $this->core = $core;
    }

    public function hookResponseAfter($request, &$response)
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $vendor = $this->core->getPath('modules', 'modules/core', false) . '/FileManager/Assets';

        $response->addScript('filemanager', [
                'src' => "$vendor/js/filemanager.js"
            ])
            ->addStyle('filemanager', [
                'href' => "$vendor/css/filemanager.css",
                'rel'  => 'stylesheet'
        ]);
    }
}
