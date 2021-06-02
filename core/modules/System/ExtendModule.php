<?php

declare(strict_types=1);

namespace SoosyzeCore\System;

use Psr\Container\ContainerInterface;

abstract class ExtendModule
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
     * Chargement des Assets du module.
     */
    abstract public function boot(): void;

    /**
     * Script d'installation du module.
     */
    abstract public function install(ContainerInterface $ci): void;

    /**
     * Ajoute des données de demo.
     */
    abstract public function seeders(ContainerInterface $ci): void;

    /**
     * Script de dés-installation.
     */
    abstract public function uninstall(ContainerInterface $ci): void;

    /**
     * Install ou créer des données si d'autre modules sont présent.
     */
    abstract public function hookInstall(ContainerInterface $ci): void;

    /**
     * Désinstall ou supprime des données si d'autre modules se déhinstall.
     */
    abstract public function hookUninstall(ContainerInterface $ci): void;
}
