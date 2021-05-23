<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->createTableIfNotExists('entity_page', function (TableBuilder $table) {
            $table->increments('page_id')
                    ->text('body');
        });

        $nodes = $req->from('node')->where('type', '=', 'page')->fetchAll();
        foreach ($nodes as $i => $node) {
            $field = unserialize($node[ 'field' ]);

            $req->insertInto('entity_page', [ 'page_id', 'body' ])
                ->values([ $i,
                    empty($field[ 'body' ])
                    ? 'content'
                    : $field[ 'body' ] ])
                ->execute();

            $req->update('node', [
                    'entity_id' => $i
                ])
                ->where('id', '=', $node[ 'id' ])
                ->execute();
        }
    }
];
