<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $profils = $req->from('profil_file')->fetchAll();
        foreach ($profils as $profil) {
            if (strpos($profil[ 'folder_show' ], '%uid') === false) {
                continue;
            }
            $req->update('profil_file', [
                    'folder_show' => str_replace('%uid', ':user_id', $profil[ 'folder_show' ])
                ])
                ->where('profil_file_id', '=', $profil[ 'profil_file_id' ])
                ->execute();
        }
    }
];
