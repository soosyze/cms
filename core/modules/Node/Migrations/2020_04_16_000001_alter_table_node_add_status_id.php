<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->alterTable('node', function (TableBuilder $table) {
            $table
                    ->integer('node_status_id')->valueDefault(3);
        });

        $nodes = $req->from('node')->fetchAll();
        foreach ($nodes as $node) {
            if ($node[ 'published' ]) {
                $req->update('node', [
                        'node_status_id' => 1
                    ])
                    ->where('id', '=', $node[ 'id' ])
                    ->execute();
            }
        }
    }
];
