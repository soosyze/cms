<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->createTableIfNotExists('entity_article', function (TableBuilder $table) {
            $table->increments('article_id')
                    ->string('image')
                    ->text('summary')
                    ->text('body')
                    ->integer('reading_time')->comment('In minute');
        });

        $idImage   = $req->from('field')->where('field_name', '=', 'image')->fetch()[ 'field_id' ];
        $idSummary = $req->from('field')->where('field_name', '=', 'summary')->fetch()[ 'field_id' ];
        $idBody    = $req->from('field')->where('field_name', '=', 'body')->fetch()[ 'field_id' ];
        $idReading = $req->from('field')->where('field_name', '=', 'reading_time')->fetch()[ 'field_id' ];

        $req->insertInto('node_type_field', [
                'node_type', 'field_id', 'field_weight', 'field_label', 'field_rules',
                'field_description', 'field_show_form'
            ])
            ->values([
                'article', $idImage, 1, 'Picture',
                'required_without:file-name-image|!required|image|max:800kb',
                'The weight of the image must be less than or equal to 800ko',
                true
            ])
            ->values([
                'article', $idSummary, 2, 'Summary',
                'required|string|max:512',
                'Briefly summarize your article in less than 512 characters',
                true
            ])
            ->values([
                'article', $idBody, 3, 'Body',
                'string',
                '',
                true
            ])
            ->values([
                'article', $idReading, 4, 'Reading time',
                'number|min:1',
                '',
                false
            ])
            ->execute();

        $nodes = $req->from('node')->where('type', 'article')->fetchAll();
        foreach ($nodes as $i => $node) {
            $field = unserialize($node[ 'field' ]);
            $req->insertInto('entity_article', [
                    'article_id', 'image', 'summary', 'body', 'reading_time'
                ])
                ->values([ $i,
                    empty($field[ 'image' ])
                    ? ''
                    : $field[ 'image' ],
                    empty($field[ 'summary' ])
                    ? ''
                    : $field[ 'summary' ],
                    empty($field[ 'body' ])
                    ? ''
                    : $field[ 'body' ],
                    1
                ])
                ->execute();

            $req->update('node', [ 'entity_id' => $i ])
                ->where('id', '=', $node[ 'id' ])
                ->execute();
        }
    }
];
