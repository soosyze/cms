<?php

return [
    'news.extend' => [
        'class' => 'SoosyzeCore\News\Extend',
        'hooks' => [
            'install.menu' => 'hookInstallMenu',
            'install.user' => 'hookInstallUser'
        ]
    ],
    'news.hook.api.route' => [
        'class' => 'SoosyzeCore\News\Hook\ApiRoute',
        'hooks' => [
            'api.route' => 'apiRoute'
        ]
    ],
    'news.hook.user' => [
        'class' => 'SoosyzeCore\News\Hook\User',
        'hooks' => [
            'route.news.index' => 'hookNewShow',
            'route.news.page' => 'hookNewShow',
            'route.news.years' => 'hookNewShow',
            'route.news.page.years' => 'hookNewShow',
            'route.news.month' => 'hookNewShow',
            'route.news.page.month' => 'hookNewShow',
            'route.news.day' => 'hookNewShow',
            'route.news.page.day' => 'hookNewShow',
            'route.news.rss' => 'hookNewShow'
        ]
    ],
    'news.hook.config' => [
        'class' => 'SoosyzeCore\News\Hook\Config',
        'hooks' => [
            'config.edit.menu' => 'menu'
        ]
    ],
    'news.hook.block' => [
        'class' => 'SoosyzeCore\News\Hook\Block',
        'hooks' => [
            'block.create.form.data' => 'hookBlockCreateFormData',
            'block.news.archive.select' => 'hookBlockNewsArchiveSelect',
            'block.news.archive' => 'hookBlockNewsArchive',
            'block.news.archive.edit.form' => 'hookBlockNewsArchiveEditForm',
            'block.news.archive.update.validator' => 'hookNewsBlockArchiveUpdateValidator',
            'block.news.archive.update.before' => 'hookNewsArchiveUpdateBefore',
            'block.news.last' => 'hookBlockNewsLast',
            'block.news.last.edit.form' => 'hookBlockNewsLastEditForm',
            'block.news.last.update.validator' => 'hookBlockNewsLastUpdateValidator',
            'block.news.last.update.before' => 'hookNewsLastUpdateBefore'
        ]
    ],
    'news.hook.node' => [
        'class' => 'SoosyzeCore\News\Hook\Node',
        'hooks' => [
            'node.makefields' => 'hookNodeMakefields',
            'node.create.form.data' => 'hookNodeFormData',
            'node.edit.form.data' => 'hookNodeFormData',
            'node.entity.store.before' => 'hookNodeStoreBefore',
            'node.entity.update.before' => 'hookNodeUpdateBefore',
            'node.show.tpl' => 'hookNodeShowTpl'
        ]
    ]
];
