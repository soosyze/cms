<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Queryflatfile\TableBuilder;

return [
    'up' => function (Schema $sch, Request $req) {
        $sch->alterTable('user', function (TableBuilder $table) {
            $table->dropColumn('salt')
                    ->text('token_connected')->nullable();
        });
        $req->update('user', [
                'password' => password_hash('Soosyze2020&', PASSWORD_DEFAULT)
            ])
            ->where('user_id', '=', 1)
            ->execute();
    }
];
