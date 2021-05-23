<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $req->update('role_permission', [
                'permission_id' => 'filemanager.permission.admin'
            ])
            ->where('permission_id', '=', 'filemanager.profil.admin')
            ->execute();
    }
];
