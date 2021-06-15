<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Block\Controller');

R::get('block.section.admin', 'admin/theme/:theme/section', 'Section@admin', [ ':theme' => 'public|admin' ]);
R::post('block.section.update', 'admin/section/:id/edit', 'Section@update', [ ':id' => '\d+' ]);

R::useNamespace('SoosyzeCore\Block\Controller')->name('block.')->prefix('block')->group(function () {
    R::get('show', '/:id', 'Block@show', [ ':id' => '\d+' ]);
    R::get('create', '/:theme/:section', 'Block@create', [ ':theme' => 'public|admin', ':section' => '[\-a-z_]+' ]);
    R::post('store', '/:theme/:section', 'Block@store', [ ':theme' => 'public|admin', ':section' => '[\-a-z_]+' ]);
    R::get('edit', '/:id/edit', 'Block@edit', [ ':id' => '\d+' ]);
    R::post('update', '/:id', 'Block@update', [ ':id' => '\d+' ]);
    R::delete('delete', '/:id/delete', 'Block@delete', [ ':id' => '\d+' ]);
});
