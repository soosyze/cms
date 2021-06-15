<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Config\Controller')->name('config.')->prefix('admin/config')->group(function () {
    R::get('admin', '', 'Config@admin');
    R::get('edit', '/:id', 'Config@edit', [ ':id' => '\w+' ]);
    R::post('update', '/:id', 'Config@update', [ ':id' => '\w+' ]);
});
