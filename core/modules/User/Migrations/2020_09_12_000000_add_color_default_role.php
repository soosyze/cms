<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $roles = [
            [
                'id'    => 1,
                'color' => '#e5941f'
            ],
            [
                'id'    => 2,
                'color' => '#fe4341'
            ],
            [
                'id'    => 3,
                'color' => '#858eec'
            ]
        ];

        foreach ($roles as $role) {
            $roleCurrent = $req->from('role')->where('role_id', '=', $role[ 'id' ])->fetch();
            if ($roleCurrent[ 'role_color' ] !== '#e6e7f4') {
                continue;
            }

            $req->update('role', [
                    'role_color' => $role[ 'color' ]
                ])
                ->where('role_id', '==', $role[ 'id' ])
                ->execute();
        }
    }
];
