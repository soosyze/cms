<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Services;

use Composer\Semver\Semver;
use Soosyze\App;
use Soosyze\Components\Util\Util;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\System\ExtendModule;
use SoosyzeCore\System\ExtendTheme;

class Composer
{
    const TYPE_MODULE = 'soosyze-module';

    const TYPE_THEME = 'soosyze-theme';

    /**
     * @var App
     */
    private $core;

    /**
     * Les données du fichier du coeur du CMS.
     *
     * @var array
     */
    private $coreComposer = [];

    /**
     * @var Modules
     */
    private $module;

    /**
     * Les données des fichiers composer des modules.
     *
     * @var array
     */
    private $moduleComposers = [];

    /**
     * @var Semver
     */
    private $semver;

    /**
     * Les données des fichiers composer des themes.
     *
     * @var array
     */
    private $themeComposers = [];

    public function __construct(App $core, Modules $module, Semver $semver)
    {
        $this->core   = $core;
        $this->module = $module;
        $this->semver = $semver;
    }

    public function validComposer(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        $validator = (new Validator())
            ->setRules([
                'autoload'    => 'required|array',
                'description' => 'required|string|max:512',
                'name'        => 'required|string',
                'type'        => 'required|string|inarray:soosyze-module,soosyze-theme',
                'version'     => 'required|version'
            ])
            ->setInputs($data);

        $errors = [];

        if (!$validator->isValid()) {
            $errors += $validator->getKeyErrors();
        } elseif (!is_array($data[ 'autoload' ][ 'psr-4' ] ?? null)) {
            $errors[] = t('The namespace information for the :title module does not exist.', [
                ':title' => $title
            ]);
        } else {
            $extendClass = $this->getExtendClass($title, $composers);
            if (!class_exists($extendClass)) {
                $errors[] = t('The installation scripts for the :title module do not exist.', [
                    ':title' => $title
                ]);
            }
        }

        return $errors;
    }

    public function validComposerExtendModule(string $title, array $composers): array
    {
        $extendClass = $this->getExtendClass($title, $composers);
        if (new $extendClass() instanceof ExtendModule) {
            return [];
        }

        return [
            t('The :title install class does not implement the extend interface.', [
                ':title' => $extendClass
            ])
        ];
    }

    public function validComposerExtendTheme(string $title, array $composers): array
    {
        $extendClass = $this->getExtendClass($title, $composers);
        if (new $extendClass() instanceof ExtendTheme) {
            return [];
        }

        return [
            t('The :title install class does not implement the extend interface.', [
                ':title' => $extendClass
            ])
        ];
    }

    public function validRequirePhp(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        if (empty($data[ 'require' ][ 'php' ])) {
            return [];
        }

        $errors = [];

        try {
            if (!$this->semver->satisfies(PHP_VERSION, $data[ 'require' ][ 'php' ])) {
                $errors[] = t('The PHP :version version of your server does not allow the installation of module :title.', [
                    ':current_php_version' => PHP_VERSION,
                    ':require_php_version' => $data[ 'require' ][ 'php' ],
                    ':title'               => $title
                ]);
            }
        } catch (\Exception $ex) {
        }

        return $errors;
    }

    public function validRequireExtLib(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        if (empty($data[ 'require' ])) {
            return [];
        }

        $errors = [];

        foreach ($data[ 'require' ] as $moduleRequire => $version) {
            if (preg_match('{^ext-(.+)$}iD', $moduleRequire, $match) && !extension_loaded($match[ 1 ])) {
                $errors[] = t('Module :title requires PHP extension :ext_name', [
                    ':ext_name' => $match[ 1 ],
                    ':title'    => $title
                ]);
            } elseif (preg_match('{^lib-(.+)$}iD', $moduleRequire, $match)) {
                if (!extension_loaded($match[ 1 ])) {
                    $errors[] = t('Module :title requires PHP library :ext_name (:version_ext)', [
                        ':ext_name'    => $match[ 1 ],
                        ':title'       => $title,
                        ':version_ext' => $version
                    ]);
                } elseif (!$this->semver->satisfies(phpversion($moduleRequire), $version)) {
                    $errors[] = t('Le module :title nécessite le la bibliothèque PHP :ext_name (:version_ext) actuellement (:version_current_ext)', [
                        ':ext_name'            => $match[ 1 ],
                        ':title'               => $title,
                        ':version_ext'         => $version,
                        ':version_current_ext' => phpversion($moduleRequire)
                    ]);
                }
            }
        }

        return $errors;
    }

    public function validRequireModule(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        if (empty($data[ 'extra' ][ 'soosyze' ][ 'require' ])) {
            return [];
        }

        $errors = [];

        foreach ($data[ 'extra' ][ 'soosyze' ][ 'require' ] as $requiredModuleTitle => $requiredModuleVersion) {
            /* Si les sources du module requis n'existe pas. */
            if (!isset($composers[ $requiredModuleTitle ])) {
                $errors[] = t('The :title module source files (:version) do not exist.', [
                    ':title'   => $requiredModuleTitle,
                    ':version' => $requiredModuleVersion
                ]);

                continue;
            }
            /* Si le module requis n'est pas installé. */
            if (!($require = $this->module->has($requiredModuleTitle))) {
                $errors[] = t('The :title1 (:version) module required by :title2 is not installed.', [
                    ':title1'  => $requiredModuleTitle,
                    ':title2'  => $title,
                    ':version' => $requiredModuleVersion
                ]);
            } elseif (!$this->semver->satisfies($require[ 'version' ], $requiredModuleVersion)) {
                /* Sinon si le module requis n'est pas dans la version attendue. */
                $errors[] = t('The :title1 module require the :title2 (:version) module, currently (:version_current).', [
                    ':title1'          => $title,
                    ':title2'          => $requiredModuleTitle,
                    ':version'         => $requiredModuleVersion,
                    ':version_current' => $require[ 'version' ]
                ]);
            }
        }

        return $errors;
    }

    public function getVersionCore(): string
    {
        $coreComposer = $this->getComposerCore();

        return $coreComposer[ 'version' ];
    }

    public function getComposerCore(): array
    {
        if (!$this->coreComposer) {
            $this->coreComposer = Util::getJson(ROOT . '/composer.json');
        }

        return $this->coreComposer;
    }

    public function getThemeComposers(bool $reload = false): array
    {
        if (!empty($this->themeComposers) || $reload) {
            return $this->themeComposers;
        }

        $themes = $this->core->getSetting('themes_path', []);

        foreach ($themes as $theme) {
            $this->themeComposers += $this->getComposer($theme, self::TYPE_THEME);
        }

        return $this->themeComposers;
    }

    public function getModuleComposer(string $title): ?array
    {
        $this->getModuleComposers();

        return $this->moduleComposers[ $title ] ?? null;
    }

    public function getModuleComposers(bool $reload = false): array
    {
        if (!empty($this->moduleComposers) || $reload) {
            return $this->moduleComposers;
        }

        $moduleCore = $this->core->getDir('modules', 'core/modules', false);
        $moduleApp  = $this->core->getDir('modules_contributed', 'app/modules', false);

        $this->moduleComposers = $this->getComposer($moduleApp) + $this->getComposer($moduleCore);

        return $this->moduleComposers;
    }

    public function getExtendClass(string $title, array $composers): string
    {
        return array_keys($composers[ $title ][ 'autoload' ][ 'psr-4' ])[ 0 ] . 'Extend';
    }

    /**
     * Vérifie la conformité des données du module de son fichier composer.
     *
     * @param string $title Nom du module
     *
     * @return array La liste des erreurs.
     */
    public function validComposerExtraModule(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        if (!is_array($data[ 'extra' ][ 'soosyze' ] ?? null)) {
            return [ t('The :name module information does not exist.', [ ':name' => $title ]) ];
        }

        $validator = (new Validator())
            ->setRules([
                'controller' => 'required|class_exists:1',
                'icon'       => '!required|array',
                'package'    => 'required|string|max:128',
                'require'    => '!required|array',
                'title'      => 'required|string|max:128',
            ])
            ->setMessages([
                'controller' => [
                    'class_exists' => [
                        'must' => t('The :label of the :name module is not found by the autoloader.', [
                            ':name' => $title
                        ])
                    ],
                    'required'     => [
                        'must' => t('The information on the controllers of the :name module does not exist.', [
                            ':name' => $title
                        ])
                    ]
                ]
            ])
            ->setInputs($data[ 'extra' ][ 'soosyze' ]);

        $validator->isValid();

        return $validator->getKeyErrors();
    }

    public function validComposerExtraTheme(string $title, array $composers): array
    {
        $data = $composers[ $title ];

        if (!is_array($data[ 'extra' ][ 'soosyze' ] ?? null)) {
            return [ t('The :name theme information does not exist.', [ ':name' => $title ]) ];
        }

        $validator = (new Validator())
            ->setRules([
                'require'  => '!required|array',
                'sections' => 'required|array',
                'title'    => 'required|string|max:128'
            ])
            ->setInputs($data[ 'extra' ][ 'soosyze' ]);

        $validator->isValid();

        return $validator->getKeyErrors();
    }

    private function getComposer(string $dir, string $type = 'soosyze-module'): array
    {
        $out = [];

        foreach (new \DirectoryIterator($dir) as $splFile) {
            if (!$splFile->isDir() || $splFile->isDot()) {
                continue;
            }
            $file = $splFile->getRealPath() . '/composer.json';
            if (!file_exists($file)) {
                continue;
            }

            $composer = Util::getJson($file);

            if (empty($composer[ 'type' ]) || $composer[ 'type' ] !== $type || empty($composer[ 'extra' ][ 'soosyze' ][ 'title' ])) {
                continue;
            }

            $out[ $composer[ 'extra' ][ 'soosyze' ][ 'title' ] ] = $composer;
        }

        return $out;
    }
}
