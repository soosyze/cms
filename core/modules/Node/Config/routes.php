<?php

use Soosyze\Components\Router\Route as R;

define('ENTITY_STORE_WITH', [
    ':id_node' => '\d+',
    ':entity'  => '[_a-z]+'
]);
define('ENTITY_EDIT_WITH', [
    ':id_node'   => '\d+',
    ':entity'    => '[_a-z]+',
    ':id_entity' => '\d+'
]);

R::useNamespace('SoosyzeCore\Node\Controller')->name('node.')->prefix('node')->group(function () {
    R::get('show', '/:id_node', 'Node@show', [ ':id_node' => '\d+' ]);
    R::get('status.search', '/status/search', 'NodeStatus@search');
    R::get('type.search', '/type/search', 'NodeType@search');
    R::get('filter', '/filter', 'NodeManager@filter');
    R::get('filter.page', '/filter/:id', 'NodeManager@filterPage', [ ':id' => '[1-9]\d*' ]);
});
R::useNamespace('SoosyzeCore\Node\Controller')->name('node.api.')->prefix('api/node/:id_node')->group(function () {
    R::get('remove', '/remove', 'NodeApi@remove', [ ':id_node' => '\d+' ]);
    R::post('delete', '/delete', 'NodeApi@delete', [ ':id_node' => '\d+' ]);
});
R::useNamespace('SoosyzeCore\Node\Controller')->name('node.')->prefix('admin/node')->group(function () {
    R::get('admin', '/', 'NodeManager@admin');
    R::get('add', '/add', 'Node@add');
    R::get('create', '/:node/create', 'Node@create', [ ':node' => '[_a-z]+' ]);
    R::post('store', '/:node/create', 'Node@store', [ ':node' => '[_a-z]+' ]);
    R::get('edit', '/:id_node/edit', 'Node@edit', [ ':id_node' => '\d+' ]);
    R::get('clone', '/:id_node/clone', 'Node@cloneNode', [ ':id_node' => '\d+' ]);
    R::post('update', '/:id_node/edit', 'Node@update', [ ':id_node' => '\d+' ]);
    R::get('remove', '/:id_node/remove', 'Node@remove', [ ':id_node' => '\d+' ]);
    R::post('delete', '/:id_node/delete', 'Node@delete', [ ':id_node' => '\d+' ]);
});
R::useNamespace('SoosyzeCore\Node\Controller')->name('entity.')->prefix('admin/node/:id_node/:entity')->group(function () {
    R::get('create', '/', 'Entity@create', ENTITY_STORE_WITH);
    R::post('store', '/', 'Entity@store', ENTITY_STORE_WITH);
    R::get('edit', '/:id_entity/edit', 'Entity@edit', ENTITY_EDIT_WITH);
    R::post('update', '/:id_entity/edit', 'Entity@update', ENTITY_EDIT_WITH);
    R::get('delete', '/:id_entity/delete', 'Entity@delete', ENTITY_EDIT_WITH);
});
