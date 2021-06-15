<?php

use Soosyze\Components\Router\Route as R;

define('FILEMANAGER_FILE_WITH', [
    ':path' => '(/[-\w]+){0,255}',
    ':name' => '/[-\w ]{1,255}',
    ':ext' => '\.[a-zA-Z0-9]{1,10}'
]);
define('FILEMANAGER_PATH_WITH', [ ':path' => '(/[-\w]+){0,255}' ]);

R::useNamespace('SoosyzeCore\FileManager\Controller');

/* Affichage du filemanager complet. */
R::get('filemanager.admin', 'admin/filemanager/show', 'Manager@admin');
R::get('filemanager.public', 'filemanager/public:path', 'Manager@showPublic', FILEMANAGER_PATH_WITH);
R::get('filemanager.show', 'filemanager/show:path', 'Manager@show', FILEMANAGER_PATH_WITH);
R::get('filemanager.filter', 'filemanager/filter:path', 'Manager@filter', FILEMANAGER_PATH_WITH);

R::useNamespace('SoosyzeCore\FileManager\Controller')->name('filemanager.file.')->prefix('filemanager')->group(function () {
    R::get('show', '/file:path:name:ext', 'File@show', FILEMANAGER_FILE_WITH);
    R::get('create', '/file:path', 'File@create', FILEMANAGER_PATH_WITH);
    R::post('store', '/file:path', 'File@store', FILEMANAGER_PATH_WITH);
    R::get('edit', '/file:path:name:ext/edit', 'File@edit', FILEMANAGER_FILE_WITH);
    R::post('update', '/file:path:name:ext/edit', 'File@update', FILEMANAGER_FILE_WITH);
    R::get('remove', '/file:path:name:ext/delete', 'File@remove', FILEMANAGER_FILE_WITH);
    R::post('delete', '/file:path:name:ext/delete', 'File@delete', FILEMANAGER_FILE_WITH);
    R::get('download', '/download:path:name:ext', 'File@download', FILEMANAGER_FILE_WITH);
});
/* Affichage du filemanager uniquement les répertoires. */
R::useNamespace('SoosyzeCore\FileManager\Controller')->name('filemanager.copy.')->prefix('filemanager')->group(function () {
    R::get('admin', '/copy:path:name:ext', 'FileCopy@admin', FILEMANAGER_FILE_WITH);
    R::post('update', '/copy:path:name:ext', 'FileCopy@update', FILEMANAGER_FILE_WITH);
    R::get('show', '/copy:path', 'FileCopy@show', FILEMANAGER_PATH_WITH);
});
R::useNamespace('SoosyzeCore\FileManager\Controller')->name('filemanager.folder.')->prefix('filemanager/folder:path')->group(function () {
    R::get('create', '/create', 'Folder@create', FILEMANAGER_PATH_WITH);
    R::post('store', '/store', 'Folder@store', FILEMANAGER_PATH_WITH);
    R::get('edit', '/edit', 'Folder@edit', [ ':path' => '(/[-\w]+){1,255}' ]);
    R::post('update', '/edit', 'Folder@update', [ ':path' => '(/[-\w]+){1,255}' ]);
    R::get('remove', '/delete', 'Folder@remove', [ ':path' => '(/[-\w]+){1,255}' ]);
    R::post('delete', '/delete', 'Folder@delete', [ ':path' => '(/[-\w]+){1,255}' ]);
});
R::useNamespace('SoosyzeCore\FileManager\Controller')->name('filemanager.permission.')->prefix('admin/user/permission/filemanager')->group(function () {
    R::get('admin', '/', 'FilePermissionManager@admin');
    R::post('admin.check', '', 'FilePermissionManager@adminCheck');
    R::get('create', '/create', 'FilePermission@create');
    R::post('store', '/store', 'FilePermission@store');
    R::get('edit', '/:id/edit', 'FilePermission@edit', [ ':id' => '\d+' ]);
    R::post('update', '/:id/edit', 'FilePermission@update', [ ':id' => '\d+' ]);
    R::get('remove', '/:id/delete', 'FilePermission@remove', [ ':id' => '\d+' ]);
    R::post('delete', '/:id/delete', 'FilePermission@delete', [ ':id' => '\d+' ]);
});
