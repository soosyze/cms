<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Menu\Controller');

R::get('menu.api.show', 'admin/api/menu/:menu', 'MenuApi@show', [ ':menu' => '[a-z\d-]+' ]);

R::useNamespace('SoosyzeCore\Menu\Controller')->name('menu.')->prefix('admin/menu')->group(function () {
    R::get('admin', '/', 'Menu@admin');
    R::get('create', '/create', 'Menu@create');
    R::post('store', '/create', 'Menu@store');
    R::get('show', '/:menu', 'Menu@show', [ ':menu' => '[a-z\d-]+' ]);
    R::post('check', '/:menu', 'Menu@check', [ ':menu' => '[a-z\d-]+' ]);
    R::get('edit', '/:menu/edit', 'Menu@edit', [ ':menu' => '[a-z\d-]+' ]);
    R::post('update', '/:menu/edit', 'Menu@update', [ ':menu' => '[a-z\d-]+' ]);
    R::get('remove', '/:menu/delete', 'Menu@remove', [ ':menu' => '[a-z\d-]+' ]);
    R::post('delete', '/:menu/delete', 'Menu@delete', [ ':menu' => '[a-z\d-]+' ]);
});
R::useNamespace('SoosyzeCore\Menu\Controller')->name('menu.link.')->prefix('admin/menu/:menu/link')->group(function () {
    R::get('create', '/', 'Link@create', [ ':menu' => '[a-z\d-]+' ]);
    R::post('store', '/', 'Link@store', [ ':menu' => '[a-z\d-]+' ]);
    R::get('edit', '/:id/edit', 'Link@edit', [ ':menu' => '[a-z\d-]+', ':id' => '\d+' ]);
    R::post('update', '/:id/edit', 'Link@update', [ ':menu' => '[a-z\d-]+', ':id' => '\d+' ]);
    R::get('delete', '/:id/delete', 'Link@delete', [ ':menu' => '[a-z\d-]+', ':id' => '\d+' ]);
});
