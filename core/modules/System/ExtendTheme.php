<?php

namespace SoosyzeCore\System;

abstract class ExtendTheme
{
    /**
     * @var array
     */
    private $translations = [];

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function loadTranslation(string $lang, string $file): void
    {
        $this->translations[ $lang ][] = $file;
    }

    abstract public function getDir(): string;

    /**
     * Chargement des Assets du thème.
     */
    abstract public function boot(): void;
}
