<?php

use Soosyze\App;

class Core extends App
{
    public function loadModules()
    {
        if (!$this->get('config')->get('settings.time_installed')) {
            $modules[] = new SoosyzeCore\System\Controller\Install();

            return $modules;
        }

        $data = $this->get('query')->from('module_controller')->fetchAll();
        foreach ($data as $value) {
            $modules[] = new $value[ 'controller' ]();
        }

        return $modules;
    }

    public function loadServices()
    {
        return [
            'schema'   => [
                'class'     => 'SoosyzeCore\QueryBuilder\Services\Schema',
                'arguments' => [
                    '#database.host',
                    '#database.schema'
                ]
            ],
            'query'    => [
                'class'     => 'SoosyzeCore\QueryBuilder\\Services\Query',
                'arguments' => [
                    '@schema'
                ]
            ],
            'template' => [
                'class'     => 'SoosyzeCore\Template\Services\Templating',
                'arguments' => [
                    '@core',
                    '@config'
                ]
            ],
            'template.hook.user' => [
                'class' => 'SoosyzeCore\Template\Hook\User',
                'hooks' => [
                    'user.permission.module' => 'hookUserPermissionModule',
                    'install.user'           => 'hookInstallUser'
                ]
            ],
            'file'     => [
                'class'     => 'SoosyzeCore\FileSystem\Services\File',
                'arguments' => [
                    '@core'
                ]
            ],
            'translate'     => [
                'class'     => 'SoosyzeCore\Translate\Services\Translation',
                'arguments' => [
                    '@config',
                    __DIR__ . '/lang',
                    'en'
                ]
            ],
            'mailer'        => [
                'class'     => 'SoosyzeCore\Mailer\Services\Mailer',
                'arguments' => [
                    '#mailer'
                ]
            ],
            'xss' => [
                'class' => 'SoosyzeCore\Filter\Services\Xss'
            ]
        ];
    }
}
