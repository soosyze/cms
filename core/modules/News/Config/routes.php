<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\News\Controller')->name('news.')->prefix('news')->group(function () {
    R::get('index', '', 'News@index');
    R::get('page', '/page/:id', 'News@page', [ ':id' => '[1-9]\d*' ]);
    R::get('years', '/:year:id', 'News@viewYears', [
        ':year' => '\d{4}',
        ':id'   => '(/page/[1-9]\d*)?'
    ]);
    R::get('years.page', '/:year/page/:id', 'News@viewYears', [
        ':year' => '\d{4}',
        ':id'   => '[1-9]\d*'
    ]);
    R::get('month', '/:year/:month:id', 'News@viewMonth', [
        ':year'  => '\d{4}',
        ':month' => '0[1-9]|1[0-2]',
        ':id'    => '(/page/[1-9]\d*)?'
    ]);
    R::get('month.page', '/:year/:month/page/:id', 'News@viewMonth', [
        ':year'  => '\d{4}',
        ':month' => '0[1-9]|1[0-2]',
        ':id'    => '[1-9]\d*'
    ]);
    R::get('day', '/:year/:month/:day:id', 'News@viewDay', [
        ':year'  => '\d{4}',
        ':month' => '0[1-9]|1[0-2]',
        ':day'   => '[0-2][1-9]|3[0-1]',
        ':id'    => '(/page/[1-9]\d*)?'
    ]);
    R::get('day.page', '/:year/:month/:day/page/:id', 'News@viewDay', [
        ':year'  => '\d{4}',
        ':month' => '0[1-9]|1[0-2]',
        ':day'   => '[0-2][1-9]|3[0-1]',
        ':id'    => '[1-9]\d*'
    ]);
    R::get('rss', '/feed/rss', 'News@viewRss');
});
