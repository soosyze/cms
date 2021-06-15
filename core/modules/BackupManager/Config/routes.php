<?php

use Soosyze\Components\Router\Route as R;

define('FILE_PATTERN', '2[\d]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][\d]|3[01])T([01][\d]|2[0-3])-[0-5][\d]-[0-5][\d]');

R::useNamespace('SoosyzeCore\BackupManager\Controller')->name('backupmanager.')->prefix('admin/tool/backupmanager')->group(function () {
    R::get('admin', '/', 'BackupController@admin');
    R::get('dobackup', '/do', 'BackupController@doBackup');
    R::get('download', '/download/:file', 'BackupController@download', [ ':file' => FILE_PATTERN ]);
    R::get('restore', '/restore/:file', 'BackupController@restore', [ ':file' => FILE_PATTERN ]);
    R::get('delete', '/delete/:file', 'BackupController@delete', [ ':file' => FILE_PATTERN ]);
    R::get('delete.all', '/delete/all', 'BackupController@deleteAll');
});
