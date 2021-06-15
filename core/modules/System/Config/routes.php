<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\System\Controller');

R::get('api.route', 'api/route', 'RouteApi@index');

R::useNamespace('SoosyzeCore\System\Controller')->name('system.module.')->prefix('admin/module')->group(function () {
    R::get('edit', '/', 'ModulesManager@edit');
    R::post('update', '/', 'ModulesManager@update');
});
R::useNamespace('SoosyzeCore\System\Controller')->name('system.migration.')->prefix('admin/migration')->group(function () {
    R::get('check', '/check', 'ModulesMigration@check');
    R::get('update', '/update', 'ModulesMigration@update');
});
R::useNamespace('SoosyzeCore\System\Controller')->name('system.theme.')->prefix('admin/theme')->group(function () {
    R::get('index', '/', 'Theme@index');
    R::get('admin', '/:type', 'Theme@admin', [ ':type' => 'admin|public' ]);
    R::get('active', '/:type/active/:name', 'Theme@active', [ ':type' => 'admin|public', ':name' => '\w+' ]);
    R::get('edit', '/:type/edit', 'Theme@edit', [ ':type' => 'admin|public' ]);
    R::post('update', '/:type/edit', 'Theme@update', [ ':type' => 'admin|public' ]);
});
R::useNamespace('SoosyzeCore\System\Controller')->name('system.tool.')->prefix('admin/tool')->group(function () {
    R::get('admin', '/', 'Tool@admin');
    R::get('cron', '/cron', 'Tool@cron');
    R::get('trans', '/trans', 'Tool@updateTranslations');
});
