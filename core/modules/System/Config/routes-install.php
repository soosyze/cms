<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\System\Controller');

R::useNamespace('SoosyzeCore\System\Controller')->name('install.')->prefix('install')->group(function () {
    R::get('index', '/', 'Install@index');
    R::get('step', '/step/:id', 'Install@step', [ ':id' => '\w+' ]);
    R::post('step.check', '/step/:id', 'Install@stepCheck', [ ':id' => '\w+' ]);
    R::post('language', '/language/:id', 'Install@language', [ ':id' => '\w+' ]);
});
