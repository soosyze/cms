<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        $req->update('node_type', [
                'node_type_icon' => 'fa fa-file'
            ])
            ->where('node_type', '=', 'page')
            ->execute();

        $req->update('node_type', [
                'node_type_icon'        => 'fa fa-question',
                'node_type_description' => 'Create your question and answer page.'
            ])
            ->where('node_type', '=', 'page_faq')
            ->execute();

        $req->update('node_type', [
                'node_type_icon'        => 'fa fa-images',
                'node_type_description' => 'Create an image gallery.'
            ])
            ->where('node_type', '=', 'page_gallery')
            ->execute();
    }
];
