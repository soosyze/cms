<?php

use Soosyze\Components\Router\Route as R;

R::useNamespace('SoosyzeCore\Trumbowyg\Controller');

R::post('trumbowyg.upload', '/trumbowyg/upload', 'Trumbowyg@upload');
