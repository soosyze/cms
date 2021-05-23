<?php

use Queryflatfile\Request;
use Queryflatfile\Schema;
use Soosyze\Config;

return [
    'up' => function (Schema $sch, Request $req) {
        $search  = 'app/files';
        $replace = 'public/files';

        if ($sch->hasTable('entity_article')) {
            $articles = $req->from('entity_article')->fetchAll();
            foreach ($articles as $article) {
                $req
                    ->update('entity_article', [
                        'body'    => str_replace($search, $replace, $article[ 'body' ]),
                        'summary' => str_replace($search, $replace, $article[ 'summary' ]),
                        'image'   => str_replace($search, $replace, $article[ 'image' ])
                    ])
                    ->where('article_id', '=', $article[ 'article_id' ])
                    ->execute();
            }
        }

        $pages = $req->from('entity_page')->fetchAll();
        foreach ($pages as $page) {
            $req
                ->update('entity_page', [
                    'body'    => str_replace($search, $replace, $page[ 'body' ])
                ])
                ->where('page_id', '=', $page[ 'page_id' ])
                ->execute();
        }

        $users = $req->from('user')->fetchAll();
        foreach ($users as $user) {
            if (empty($user[ 'picture' ])) {
                continue;
            }
            $req
                ->update('user', [
                    'picture' => str_replace($search, $replace, $user[ 'picture' ])
                ])
                ->where('user_id', '=', $user[ 'user_id' ])
                ->execute();
        }

        if ($sch->hasTable('block')) {
            $blocks = $req->from('block')->fetchAll();
            foreach ($blocks as $block) {
                $req
                    ->update('block', [
                        'content' => str_replace($search, $replace, $block[ 'content' ])
                    ])
                    ->where('block_id', '=', $block[ 'block_id' ])
                    ->execute();
            }
        }
    },
    'up_config' => function (Config $config) {
        $search  = 'app/files';
        $replace = 'public/files';

        $logo     = $config->get('settings.logo');
        $favicon  = $config->get('settings.favicon');
        $imgaeNew = $config->get('settings.new_default_image');

        $config
            ->set('settings.logo', str_replace($search, $replace, $logo))
            ->set('settings.favicon', str_replace($search, $replace, $favicon))
            ->set('settings.new_default_image', str_replace($search, $replace, $imgaeNew));
    }
];
