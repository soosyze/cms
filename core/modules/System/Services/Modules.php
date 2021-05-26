<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Services;

use Soosyze\Components\Util\Util;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\Translate\Services\Translation;

class Modules
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var Translation
     */
    private $transalte;

    public function __construct(Query $query, Translation $translate)
    {
        $this->query     = $query;
        $this->transalte = $translate;
    }

    /**
     * Si le module est installé.
     *
     * @param string $title Titre du module.
     *
     * @return array
     */
    public function has(string $title): array
    {
        return $this->query
                ->from('module_active')
                ->where('title', '=', $title)
                ->fetch();
    }

    /**
     * Si le module est requis par le module virtuel "core".
     *
     * @param string $title Nom du module.
     *
     * @return array
     */
    public function isRequiredCore(string $title): array
    {
        return $this->query
                ->from('module_require')
                ->where('title_module', '=', $title)
                ->where('title_required', '=', 'Core')
                ->lists('title_required');
    }

    /**
     * Si le module est requis par un autre module installé.
     *
     * @param string $title Nom du module.
     *
     * @return array
     */
    public function isRequiredForModule(string $title): array
    {
        $output = $this->query
            ->from('module_active')
            ->leftJoin('module_require', 'title', '=', 'module_require.title_required')
            ->where('title', '=', $title)
            ->isNotNull('title_module')
            ->lists('title_module');

        return array_unique($output);
    }

    public function listModuleActive(): array
    {
        return $this->query
            ->from('module_active')
            ->fetchAll();
    }

    public function listModuleActiveNotRequire(): array
    {
        return $this->query
                ->from('module_active')
                ->leftJoin('module_require', 'title', '=', 'module_require.title_required')
                ->isNull('title_module')
                ->lists('title');
    }

    /**
     * Désinstalle un module.
     *
     * @param string $title Nom du module.
     */
    public function uninstallModule(string $title): void
    {
        $this->query
            ->from('module_active')
            ->delete()
            ->where('title', '=', $title)
            ->execute();

        $this->query
            ->from('module_require')
            ->delete()
            ->where('title_module', '=', $title)
            ->execute();

        $this->query
            ->from('module_controller')
            ->delete()
            ->where('title', '=', $title)
            ->execute();
    }

    /**
     * Créer un module à partir du contenu de son fichier composer.json
     *
     * @param array $composer Données du module.
     */
    public function create(array $composer): void
    {
        $module = $composer[ 'extra' ][ 'soosyze' ];
        /* Enregistrement du module. */
        $this->query
            ->insertInto('module_active', [ 'title', 'version' ])
            ->values([ $module[ 'title' ], $composer[ 'version' ] ])
            ->execute();

        /* Enregistrement des contrôleurs. */
        $this->query
            ->insertInto('module_controller', [ 'title', 'controller' ])
            ->values([ $module[ 'title' ], $module[ 'controller' ] ])
            ->execute();

        if (isset($module[ 'require' ])) {
            /* Enregistrement des dépendances. */
            $this->query->insertInto('module_require', [
                'title_module', 'title_required', 'version'
            ]);

            foreach ($module[ 'require' ] as $require => $version) {
                $this->query->values([ $module[ 'title' ], $require, $version ]);
            }

            $this->query->execute();
        }
    }

    /**
     * Installe les fichiers de traduction.
     *
     * @param array $composers Liste de tous les fichiers composer
     * @param bool  $crushed   Si la mise à jour de la traduction ne prend pas en compte existante.
     */
    public function loadTranslations(array $composers, bool $crushed = true): void
    {
        $path = $this->transalte->getPath();

        $strTranslations = [];

        /* Réuni tous les fichiers de traductions par langues */
        foreach ($composers as $composer) {
            foreach ($composer[ 'translations' ] as $lang => $translations) {
                foreach ($translations as $translation) {
                    if (isset($strTranslations[ $lang ])) {
                        $strTranslations[ $lang ] += Util::getJson($translation);
                    } else {
                        $strTranslations[ $lang ] = Util::getJson($translation);
                    }
                }
            }
        }

        /* Enregistre les fichiers de traductions */
        foreach ($strTranslations as $lang => $translation) {
            if (file_exists("$path/$lang.json")) {
                $current = $crushed
                    ? Util::getJson("$path/$lang.json")
                    : [];

                Util::saveJson($path, $lang, $current + $translation);
            } else {
                Util::createJson($path, $lang, $translation);
            }
        }
    }
}
