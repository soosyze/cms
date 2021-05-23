<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->alterTable('menu_link', function (TableBuilder $table) {
            $table->string('link_router')->nullable();
        });

        $links = $req->from('menu_link')->isNotNull('key')->fetchAll();

        foreach ($links as $link) {
            $alias = $req->from('system_alias_url')
                ->where('alias', '=', $link[ 'link' ])
                ->fetch();

            $linkRouter = empty($alias)
                ? $link[ 'link' ]
                : $alias[ 'source' ];

            $req->update('menu_link', [
                    'link_router' => $linkRouter
                ])
                ->where('link', '=', $link[ 'link' ])
                ->execute();
        }
    }
];
