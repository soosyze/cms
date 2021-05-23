<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $req->update('node_type', [
                'node_type_icon' => 'fas fa-newspaper'
            ])
            ->where('node_type', '=', 'article')
            ->execute();
    }
];
