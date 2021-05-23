<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;

return [
    'up' => function (Schema $sch, Request $req) {
        if (!$sch->hasTable('menu')) {
            return;
        }
        $req->update('menu_link', [
                'key'        => 'system.theme.index',
                'icon'       => 'fa fa-paint-brush',
                'link'       => 'admin/theme',
                'title_link' => 'Themes'
            ])
            ->where('link', '=', 'admin/section/theme')
            ->execute();

        $req->insertInto('role_permission', [ 'role_id', 'permission_id' ])
            ->values([ 3, 'system.theme.manage' ])
            ->execute();
    }
];
