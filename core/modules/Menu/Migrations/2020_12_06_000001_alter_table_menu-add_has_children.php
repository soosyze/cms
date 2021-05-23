<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->alterTable('menu_link', function (TableBuilder $table) {
            $table->boolean('has_children')->valueDefault(false);
        });

        $links = $req->from('menu_link')->fetchAll();

        foreach ($links as $link) {
            if ($link[ 'parent' ] !== -1) {
                $req->update('menu_link', [
                        'has_children' => true
                    ])
                    ->where('id', '=', $link[ 'parent' ])
                    ->execute();
            }
        }
    }
];
