<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Dashboard\Controller')->name('dashboard.')->prefix('admin/dashboard')->group(function () {
    R::get('index', '/', 'Dashboard@index');
    R::get('info', '/info', 'Dashboard@info');
});
