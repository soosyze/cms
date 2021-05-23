<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->alterTable('profil_file', function (TableBuilder $table) {
            $table->boolean('file_copy')->valueDefault(true);
        });

        $profils = $req->from('profil_file_role')->where('role_id', '=', 1)->fetchAll();

        foreach ($profils as $profil) {
            $req->update('profil_file', [
                    'file_copy' => false
                ])
                ->where('profil_file_id', '=', $profil[ 'profil_file_id' ])
                ->execute();
        }
    }
];
