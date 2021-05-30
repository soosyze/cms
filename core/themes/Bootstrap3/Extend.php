<?php

namespace SoosyzeCore\Theme\Bootstrap3;

class Extend extends \SoosyzeCore\System\ExtendTheme
{
    public function getDir(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        $this->loadTranslation('fr', __DIR__ . '/Lang/fr/main.json');
    }
}
