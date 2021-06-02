<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Http\Stream;
use Soosyze\Components\Template\Template;
use Soosyze\Components\Util\Util;

class Install extends \Soosyze\Controller
{
    /**
     * Liste des modules à installer.
     *
     * @var array
     */
    private $modules = [
        'Config'      => 'SoosyzeCore\\Config\\',
        'Contact'     => 'SoosyzeCore\\Contact\\',
        'Dashboard'   => 'SoosyzeCore\\Dashboard\\',
        'Node'        => 'SoosyzeCore\\Node\\',
        'Menu'        => 'SoosyzeCore\\Menu\\',
        'System'      => 'SoosyzeCore\\System\\',
        'User'        => 'SoosyzeCore\\User\\',
        'Block'       => 'SoosyzeCore\\Block\\',
        'FileManager' => 'SoosyzeCore\\FileManager\\',
        'Trumbowyg'   => 'SoosyzeCore\\Trumbowyg\\'
    ];

    /**
     * Liste des thèmes à installer.
     *
     * @var array
     */
    private $themes = [
        'Fez'   => 'SoosyzeCore\\Theme\\Fez\\',
        'Admin' => 'SoosyzeCore\\Theme\\Admin\\'
    ];

    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services-install.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes-install.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/install/';
    }

    public function index(ServerRequestInterface $req): ResponseInterface
    {
        if (!($steps = $this->getSteps())) {
            return $this->get404($req);
        }
        $keys = array_keys($steps);
        if (!empty($steps[ $keys[ 0 ] ][ 'key' ])) {
            return $this->step($steps[ $keys[ 0 ] ][ 'key' ], $req);
        }

        return $this->get404($req);
    }

    public function step(string $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($steps = $this->getSteps()) || !isset($steps[ $id ])) {
            return $this->get404($req);
        }

        $messages = [
            'errors'   => [], 'warnings' => [],
            'infos'    => [], 'success'  => []
        ];
        if (isset($_SESSION[ 'messages' ][ $id ])) {
            $messages = array_merge($messages, $_SESSION[ 'messages' ][ $id ]);
            unset($_SESSION[ 'messages' ][ $id ]);
        }

        $blockPage     = $this->container->callHook("step.$id", [ $id ]);
        $blockMessages = (new Template('messages-install.php', $this->pathViews))
            ->addVars($messages);

        $blockHtml = (new Template('html-install.php', $this->pathViews))
                ->addBlock('page', $blockPage)
                ->addBlock('messages', $blockMessages)
                ->addVars([
                    'router'        => self::router(),
                    'steps'         => $steps,
                    'step_active'   => $id,
                    'style_install' => self::core()->getPath('modules', 'core/modules', false) . '/System/Assets/css/install.css',
                    'style_soosyze' => self::core()->getPath('modules', 'core/modules', false) . '/System/Assets/css/soosyze.css'
                ])
                ->render();

        return new Response(200, new Stream($blockHtml));
    }

    public function stepCheck(string $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($steps = $this->getSteps()) || !isset($steps[ $id ])) {
            return $this->get404($req);
        }

        /* Validation de l'étape. */
        $this->container->callHook("step.$id.check", [ $id, $req ]);

        $route = self::router()->getRoute('install.step', [ ':id' => $id ]);
        if (!empty($_SESSION[ 'inputs' ][ $id ]) && empty($_SESSION[ 'messages' ][ $id ])) {
            $this->position($steps, $id);
            if (($next = next($steps)) === false && key($steps) === null) {
                $this->installModule();
                $this->installThemes();

                return $this->installFinish();
            }

            $route = self::router()->getRoute('install.step', [ ':id' => $next[ 'key' ] ]);
        }

        return new Redirect($route);
    }

    private function getSteps(): array
    {
        $step = [];
        $this->container->callHook('step', [ &$step ]);
        uasort($step, static function ($a, $b) {
            if ($a[ 'weight' ] === $b[ 'weight' ]) {
                return 0;
            }

            return ($a[ 'weight' ] < $b[ 'weight' ])
                ? -1
                : 1;
        });

        return $step;
    }

    private function installModule(): void
    {
        $composer = [];
        $profil    = htmlspecialchars($_SESSION[ 'inputs' ][ 'profil' ][ 'profil' ]);

        $this->container->callHook("step.install.modules.$profil", [ &$this->modules ]);

        foreach ($this->modules as $title => $namespace) {
            $extendClass = $namespace . 'Extend';

            $extend = new $extendClass();

            $extend->boot();
            /* Lance les scripts d'installation (database, configuration...) */
            $extend->install($this->container);
            /* Lance les scripts de remplissages de la base de données. */
            $extend->seeders($this->container);

            $composer[ $title ] = Util::getJson($extend->getDir() . '/composer.json');

            $composer[ $title ] += [
                'dir'          => $extend->getDir(),
                'translations' => $extend->getTranslations()
            ];

            /* Charge le container des nouveaux services. */
            $this->loadContainer($composer[ $title ]);
        }

        self::module()->loadTranslations($composer);

        foreach ($this->modules as $title => $namespace) {
            /* Charge la version du coeur à ses modules. */
            $composer[$title]['version'] = $this->container->get('composer')->getVersionCore();

            /* Enregistre le module en base de données. */
            self::module()->create($composer[ $title ]);
            /* Install les scripts de migrations. */
            self::migration()->installMigration(
                $composer[ $title ][ 'dir' ] . DS . 'Migrations',
                $title
            );

            /* Hook d'installation pour les autres modules utilise le module actuel. */
            $this->container->callHook('install.' . $title, [ $this->container ]);
        }

        self::query()
            ->insertInto('module_require', [
                'title_module', 'title_required', 'version'
            ])
            ->values([ 'Core', 'System', '1.0.0' ])
            ->values([ 'Core', 'User', '1.0.0' ])
            ->execute();
    }

    private function installThemes(): void
    {
        $composer = [];

        foreach ($this->themes as $title => $namespace) {
            $extendClass = $namespace . 'Extend';

            $extend = new $extendClass();

            $extend->boot();

            $composer[ $title ] = Util::getJson($extend->getDir() . '/composer.json');

            $composer[ $title ] += [
                'dir'          => $extend->getDir(),
                'translations' => $extend->getTranslations()
            ];
        }

        self::module()->loadTranslations($composer);

        self::config()
            ->set('settings.theme', 'Fez')
            ->set('settings.theme_admin', 'Admin')
            ->set('settings.logo', '');
    }

    private function installMigration(string $dir, string $title): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        self::query()->insertInto('migration', [ 'migration', 'extension' ]);
        foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            self::query()->values([
                $fileInfo->getBasename('.php'), $title
            ]);
        }
        self::query()->execute();
    }

    private function installFinish(): ResponseInterface
    {
        $saveLanguage = $_SESSION[ 'inputs' ][ 'language' ];
        $save         = $_SESSION[ 'inputs' ][ 'user' ];

        $data     = [
            'username'         => $save[ 'username' ],
            'email'            => $save[ 'email' ],
            'password'         => password_hash($save[ 'password' ], PASSWORD_DEFAULT),
            'firstname'        => $save[ 'firstname' ],
            'name'             => $save[ 'name' ],
            'actived'          => true,
            'time_installed'   => (string) time(),
            'timezone'         => $saveLanguage['timezone'],
            'rgpd'             => true,
            'terms_of_service' => true
        ];

        self::query()
            ->insertInto('user', array_keys($data))
            ->values($data)
            ->execute();

        self::query()
            ->insertInto('user_role', [ 'user_id', 'role_id' ])
            ->values([ 1, 2 ])
            ->values([ 1, 3 ])
            ->execute();

        self::config()
            ->set('mailer.email', $data[ 'email' ])
            ->set('mailer.driver', 'mail')
            ->set('settings.time_installed', time())
            ->set('settings.lang', $saveLanguage['lang'])
            ->set('settings.timezone', $saveLanguage['timezone'])
            ->set('settings.key_cron', Util::strRandom(50))
            ->set('settings.rewrite_engine', false);

        $profil = htmlspecialchars($_SESSION[ 'inputs' ][ 'profil' ][ 'profil' ]);
        $this->container->callHook("step.install.finish.$profil", [ $this->container ]);

        $path = self::config()->getPath();
        chmod($path . 'database.json', 0444);

        session_destroy();
        $route = self::router()->getBasePath();

        return new Redirect($route);
    }

    private function loadContainer(array $composer): void
    {
        $obj  = new $composer[ 'extra' ][ 'soosyze' ][ 'controller' ]();
        if (!($path = $obj->getPathServices())) {
            return;
        }

        $this->container->addServices(include_once $path);
    }

    private function position(array &$array, string $position): void
    {
        reset($array);
        do {
            if (key($array) === $position) {
                break;
            }
        } while (next($array) !== false);
    }
}
