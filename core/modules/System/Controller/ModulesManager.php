<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;

class ModulesManager extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function edit(): ResponseInterface
    {
        /* Récupère les modules en base de données. */
        $data = array_column(self::module()->listModuleActive(), 'title');

        /* Récupère tous les fichiers de configuration. */
        $composer = self::composer()->getModuleComposers();

        $form = new FormBuilder([
            'action' => self::router()->getRoute('system.module.update'),
            'method' => 'post'
        ]);

        $packages = [];
        foreach ($composer as $values) {
            $module = $values[ 'extra' ][ 'soosyze' ];
            $title  = htmlspecialchars($module[ 'title' ]);

            $attr = [];
            /* Si le module est présent en base de données alors il est installé. */
            $attr[ 'checked' ] = in_array($title, $data);

            /* Si le module est activé est qu'il est requis. */
            if ($isRequiredForModule = $this->isRequiredForModule($title)) {
                $attr[ 'disabled' ] = 'disabled';
            }

            /* Si un module requis est non conforme. */
            if ($isRequired = $this->isRequired($module, $composer, $data)) {
                $attr[ 'disabled' ] = 'disabled';
            }

            $form->checkbox("modules[$title]", $attr)
                ->label($title, '<span class="ui"></span> ' . $title, [
                    'for' => "modules[$title]"
            ]);

            $packages[ htmlspecialchars($module[ 'package' ]) ][ $title ] = [
                'icon'                => [
                    'name'             => empty($module[ 'icon' ][ 'name' ])
                        ? 'fas fa-puzzle-piece'
                        : htmlspecialchars($module[ 'icon' ][ 'name' ]),
                    'background-color' => empty($module[ 'icon' ][ 'background-color' ])
                        ? '#ddd'
                        : htmlspecialchars($module[ 'icon' ][ 'background-color' ]),
                    'color'            => empty($module[ 'icon' ][ 'color' ])
                        ? '#666'
                        : htmlspecialchars($module[ 'icon' ][ 'color' ])
                ],
                'title'               => $title,
                'description'         => empty($values[ 'description' ])
                    ? null
                    : htmlspecialchars($values[ 'description' ]),
                'isRequired'          => $isRequired,
                'isRequiredForModule' => $isRequiredForModule,
                'version'             => empty($values[ 'version' ])
                    ? null
                    : htmlspecialchars($values[ 'version' ]),
                'support'             => empty($values[ 'support' ][ 'docs' ])
                    ? null
                    : htmlspecialchars($values[ 'support' ][ 'docs' ])
            ];
        }
        $form->token('token_module_edit')
            ->submit('submit', t('Save'));

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        ksort($packages);

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-th-large" aria-hidden="true"></i>',
                    'title_main' => t('Modules')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'system/content-modules_manager-admin.php', $this->pathViews, [
                    'module_update'      => self::config()->get('settings.module_update'),
                    'link_module_check'  => self::router()->getRoute('system.migration.check'),
                    'link_module_update' => self::router()->getRoute('system.migration.update'),
                    'count'             => count($composer),
                    'form'              => $form,
                    'packages'          => $packages
        ]);
    }

    public function update(ServerRequestInterface $req): ResponseInterface
    {
        $route     = self::router()->getRoute('system.module.edit');
        $validator = (new Validator())
            ->setRules([
                'modules'           => '!required|array',
                'token_module_edit' => 'token'
            ])
            ->setInputs($req->getParsedBody());

        if (!$validator->isValid()) {
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();

            return new Redirect($route);
        }

        $data = $validator->getInput('modules', []);

        $moduleActive = array_flip(self::module()->listModuleActiveNotRequire());

        $outUninstall = $this->uninstallModule($moduleActive, $data);
        $outInstall   = $this->installModule($moduleActive, $data);

        if (empty($outInstall) && empty($outUninstall)) {
            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'messages' ][ 'errors' ] = $outInstall + $outUninstall;
        }

        return new Redirect($route);
    }

    private function installModule(array $moduleActive, array $data): array
    {
        /* S'il n'y a pas de modules à installer. */
        if (!($diff = array_diff_key($data, $moduleActive))) {
            return [];
        }

        $composers = self::composer()->getModuleComposers();
        $modules  = array_keys($diff);

        $errors   = [];

        foreach ($modules as $title) {
            if (!isset($composers[ $title ])) {
                $errors[] = t('The :title module does not exist.', [ ':title' => $title ]);
            } elseif ($out = self::composer()->validComposer($title, $composers)) {
                $errors += $out;
            } elseif ($out = self::composer()->validComposerExtendModule($title, $composers)) {
                $errors += $out;
            } elseif ($out = self::composer()->validComposerExtraModule($title, $composers)) {
                $errors += $out;
            } elseif ($out = self::composer()->validRequirePhp($title, $composers)) {
                $errors += $out;
            } elseif ($out = self::composer()->validRequireExtLib($title, $composers)) {
                $errors += $out;
            } elseif ($out = self::composer()->validRequireModule($title, $composers)) {
                $errors += $out;
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        /* Installation */
        $composerInstall = [];
        foreach ($modules as $title) {
            $extendClass = self::composer()->getExtendClass($title, $composers);
            $extend      = new $extendClass();

            $extend->boot();
            /* Lance les scripts d'installation (database, configuration...) */
            $extend->install($this->container);
            /* Lance les scripts de remplissages de la base de données. */
            $extend->seeders($this->container);
            /* Lance l'installation des hooks déjà présents. */
            $extend->hookInstall($this->container);
            /* Charge le container de nouveaux services. */
            $this->loadContainer($composers[ $title ]);

            $composerInstall[ $title ] = $composers[ $title ];
            $composerInstall[ $title ] += [
                'dir'          => $extend->getDir(),
                'translations' => $extend->getTranslations()
            ];
        }

        self::module()->loadTranslations($composerInstall);

        /* Lance l'installation des hooks présents dans les modules nouvellement installés. */
        foreach ($composerInstall as $title => $composer) {
            /* Enregistre le module en base de données. */
            self::module()->create($composer);
            /* Install les scripts de migrations. */
            self::migration()->installMigration(
                $composer[ 'dir' ] . DS . 'Migrations',
                $title
            );

            $this->container->callHook('install.' . $title, [ $this->container ]);
        }

        return [];
    }

    private function uninstallModule(array $moduleActive, array $data): array
    {
        /* S'il n'y a pas des modules à désinstaller. */
        if (!($diff = array_diff_key($moduleActive, $data))) {
            return [];
        }

        $composers = self::composer()->getModuleComposers();
        $errors   = [];
        $modules  = array_keys($diff);

        foreach ($modules as $title) {
            /* Vérifie que le fichier composer n'est pas corrompu. */
            if ($out = self::composer()->validComposer($title, $composers)) {
                $errors += $out;
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        $instances = [];
        foreach ($modules as $title) {
            $extendClass = self::composer()->getExtendClass($title, $composers);

            $extend = new $extendClass();

            $instances[ $title ] = $extend;
            /* Supprime le module à partir de son nom. */
            self::module()->uninstallModule($title);
            /* Lance les scripts de dé-installation (database, configuration...). */
            $extend->uninstall($this->container);
        }

        foreach ($instances as $title => $extend) {
            $extend->hookUninstall($this->container);
            self::migration()->uninstallMigration($title);
            $this->container->callHook('uninstall.' . $title, [ $this->container ]);
        }

        return [];
    }

    /**
     * @param array $module
     *
     * @return array
     */
    private function isRequired(array $module, array $composer, array $data): array
    {
        if (empty($module[ 'require' ])) {
            return [];
        }

        $isRequired = [];
        foreach ($module[ 'require' ] as $require => $version) {
            /* Si le module requis n'existe pas. */
            if (empty($composer[ $require ])) {
                $isRequired[] = '<span class="module-is_required_danger">' . htmlspecialchars($require) . '</span>';
            }
            /* Si le module requis existe, mais n'est pas installé */
            elseif (!in_array($require, $data)) {
                $isRequired[] = "<a href='#{$require}' class=\"module-is_required_info\">" . htmlspecialchars($require) . '</a>';
            }
            /* Si le module requis est installé, mais n'est pas de la bonne version. */
            elseif (!self::semver()->satisfies($composer[ $require ][ 'version' ], $version)) {
                $isRequired[] = "<a href='#{$require}' class=\"module-is_required_warning\">" . htmlspecialchars(
                    "{$require} ($version)"
                ) . '</a>';
            }
        }

        return $isRequired;
    }

    /**
     * Si un module installé est requis par d'autre module.
     *
     * @param string $title Titre du module à désactiver.
     *
     * @return array
     */
    private function isRequiredForModule(string $title): array
    {
        /* Si le module est requis par le core. */
        if ($isRequiredForModule = self::module()->isRequiredCore($title)) {
            return $isRequiredForModule;
        }

        /* Si le module est activé est qu'il est requis. */
        if ($isRequiredForModule = self::module()->isRequiredForModule($title)) {
            return $isRequiredForModule;
        }

        return [];
    }

    private function loadContainer(array $composer): void
    {
        $obj  = new $composer[ 'extra' ][ 'soosyze' ][ 'controller' ]();
        if (!($path = $obj->getPathServices())) {
            return;
        }

        $this->container->addServices(include_once $path);
    }
}
