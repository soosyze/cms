<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Contact\Controller')->name('contact')->prefix('contact.')->group(function () {
    R::get('form', '/', 'Contact@form');
    R::post('check', '/', 'Contact@formCheck');
});
