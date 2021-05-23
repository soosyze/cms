<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $req->update('profil_file', [
                'folder_show' => '/download'
            ])
            ->where('folder_show', '=', '/dowload')
            ->execute();
    }
];
