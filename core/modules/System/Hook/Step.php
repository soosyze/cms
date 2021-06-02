<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Hook;

use Core;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Router\Router;
use Soosyze\Components\Template\Template;
use Soosyze\Components\Validator\Validator;
use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\QueryBuilder\Services\Schema;
use SoosyzeCore\Translate\Services\Translation;

class Step
{
    /**
     * @var string[]
     */
    private static $columnsMenu = [
        'key', 'icon', 'title_link', 'link', 'link_router', 'menu', 'weight', 'parent'
    ];

    /**
     * @var string[]
     */
    private static $columnsNode = [
        'entity_id', 'type', 'date_created', 'date_changed', 'node_status_id',
        'title', 'user_id'
    ];

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Translation
     */
    private $translate;

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var string
     */
    private $pathContent;

    public function __construct(
        Core $core,
        Config $config,
        Query $query,
        Router $router,
        Schema $schema,
        Translation $translate
    ) {
        $this->core      = $core;
        $this->config    = $config;
        $this->query     = $query;
        $this->router    = $router;
        $this->schema    = $schema;
        $this->translate = $translate;

        $this->pathViews   = dirname(__DIR__) . '/Views/install/';
        $this->pathContent = dirname(__DIR__) . '/Views/install/content/';
    }

    public function hookStep(array &$step): void
    {
        $step[ 'language' ] = [
            'weight' => 1,
            'title'  => 'Choose language',
            'key'    => 'language'
        ];
        $step[ 'profil' ] = [
            'weight' => 2,
            'title'  => 'Installation profile',
            'key'    => 'profil'
        ];
        $step[ 'user' ]   = [
            'weight' => 3,
            'title'  => 'User profile',
            'key'    => 'user'
        ];
    }

    public function getProfils(): array
    {
        $assets = $this->core->getPath('modules', 'core/modules', false) . '/System/Assets/img/';
        $profil = [
            'site'      => [
                'key'         => 'site',
                'title'       => 'Website',
                'img'         => $assets . 'site.svg',
                'description' => 'Create a standard site with all the basic features.'
            ],
            'blog'      => [
                'key'         => 'blog',
                'title'       => 'Blog',
                'img'         => $assets . 'blog.svg',
                'description' => 'Periodically publish your tickets, articles, news...'
            ],
            'portfolio' => [
                'key'         => 'portfolio',
                'title'       => 'Portfolio',
                'img'         => $assets . 'portfolio.svg',
                'description' => 'Highlight your skills, experiences and training.'
            ],
            'one_page'  => [
                'key'         => 'one_page',
                'title'       => 'One page',
                'img'         => $assets . 'one_page.svg',
                'description' => 'Present your activity on a single webpage.'
            ]
        ];
        $this->core->callHook('app.step.profil', [ &$profil ]);

        return $profil;
    }

    public function hookProfil(string $id): Template
    {
        $content = [ 'profil' => '' ];

        if (isset($_SESSION[ 'inputs' ][ $id ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ][ $id ]);
        }

        $profils = $this->getProfils();
        $form    = new FormBuilder([
            'action' => $this->router->getRoute('install.step.check', [ ':id' => $id ]),
            'method' => 'post'
        ]);

        foreach (array_keys($profils) as $key) {
            $form->group("profil_$key-group", 'div', function ($form) use ($key, $content) {
                $form->radio('profil', [
                    'id'      => "profil_$key",
                    'checked' => $key === $content[ 'profil' ],
                    'value'   => $key
                ])->label("$key-label", t('Select'), [
                    'for' => "profil_$key"
                ]);
            }, [ 'class' => 'radio-button' ]);
        }
        $form->token('token_step_install')
            ->submit('submit', t('Next →'), [ 'class' => 'btn btn-primary' ]);

        return (new Template('content-install-form_profil.php', $this->pathViews))
                ->addVars([
                    'form'    => $form,
                    'profils' => $profils
        ]);
    }

    public function hookProfilCheck(string $id, ServerRequestInterface $req): void
    {
        $profils   = array_keys($this->getProfils());
        $validator = (new Validator())
            ->setRules([
                'profil'             => 'required|inarray:' . implode(',', $profils),
                'token_step_install' => 'token'
            ])
            ->setInputs($req->getParsedBody());

        if ($validator->isValid()) {
            $_SESSION[ 'inputs' ][ $id ] = [ 'profil' => $validator->getInput('profil') ];
        } else {
            $_SESSION[ 'inputs' ][ $id ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ $id ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ][ $id ]          = $validator->getKeyInputErrors();
        }
    }

    public function hookLanguage(string $id): Template
    {
        $optionLang         =  $this->translate->getLang();
        $optionLang[ 'en' ] = [ 'value' => 'en', 'label' => 'English' ];

        $optionTimezone = [];
        foreach (timezone_identifiers_list() as $value) {
            $optionTimezone[] = [ 'value' => $value, 'label' => $value ];
        }

        $values = [
            'lang'     => 'en',
            'timezone' => date_default_timezone_get()
            ? date_default_timezone_get()
            : 'Europe/Paris'
        ];

        if (isset($_SESSION[ 'inputs' ][ $id ])) {
            $values = array_merge($values, $_SESSION[ 'inputs' ][ $id ]);
        }

        $form = (new FormBuilder([
                'action' => $this->router->getRoute('install.step.check', [ ':id' => $id ]),
                'id'     => 'form_lang',
                'method' => 'post'
                ])
            )
            ->group('fieldset', 'fieldset', function ($form) use ($values, $optionLang, $optionTimezone) {
                $form->legend('legend', t('Choose language'))
                ->group('lang-group', 'div', function ($form) use ($values, $optionLang) {
                    $form->label('lang-label', t('Language'))
                    ->select('lang', $optionLang, [
                        'class'     => 'form-control',
                        ':selected' => $values[ 'lang' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('timezone-group', 'div', function ($form) use ($values, $optionTimezone) {
                    $form->label('timezone-label', t('Timezone'))
                    ->select('timezone', $optionTimezone, [
                        'class'     => 'form-control',
                        ':selected' => $values[ 'timezone' ]
                    ]);
                }, [ 'class' => 'form-group' ]);
            })
            ->token('token_step_install')
            ->submit('submit', t('Next →'), [ 'class' => 'btn btn-primary' ]);

        if (isset($_SESSION[ 'errors_keys' ][ $id ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ][ $id ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ][ $id ]);
        }

        return (new Template('page-install.php', $this->pathViews))
                ->addVar('form', $form);
    }

    public function hookLanguageCheck(string $id, ServerRequestInterface $req): void
    {
        $langs     = implode(',', array_keys($this->translate->getLang())) . ',en';
        $validator = (new Validator())
            ->setRules([
                'lang'     => 'required|inarray:' . $langs,
                'timezone' => 'required|timezone'
            ])
            ->setInputs($req->getParsedBody());

        if ($validator->isValid()) {
            $_SESSION[ 'lang' ]          = $validator->getInput('lang');
            $_SESSION[ 'inputs' ][ $id ] = [
                'lang'     => $validator->getInput('lang'),
                'timezone' => $validator->getInput('timezone')
            ];
        } else {
            $_SESSION[ 'inputs' ][ $id ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ $id ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ][ $id ]          = $validator->getKeyInputErrors();
        }
    }

    public function hookUser(string $id): Template
    {
        $values = [
            'username'         => '',
            'email'            => '',
            'name'             => '',
            'firstname'        => '',
            'password'         => '',
            'password_confirm' => ''
        ];

        if (isset($_SESSION[ 'inputs' ][ $id ])) {
            $values = array_merge($values, $_SESSION[ 'inputs' ][ $id ]);
        }

        $form = (new FormBuilder([
            'action' => $this->router->getRoute('install.step.check', [ ':id' => $id ]),
            'method' => 'post'
            ]))
            ->group('fieldset', 'fieldset', function ($form) use ($values) {
                $form->legend('legend', t('User profile'))
                ->group('username-group', 'div', function ($form) use ($values) {
                    $form->label('username-label', t('User name'))
                    ->text('username', [
                        'class'     => 'form-control',
                        'maxlength' => 255,
                        'required'  => 1,
                        'value'     => $values[ 'username' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('email-group', 'div', function ($form) use ($values) {
                    $form->label('email-label', t('E-mail'))
                    ->email('email', [
                        'class'       => 'form-control',
                        'maxlength'   => 254,
                        'placeholder' => t('example@mail.com'),
                        'required'    => 1,
                        'value'       => $values[ 'email' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('name-group', 'div', function ($form) use ($values) {
                    $form->label('name-label', t('Last name'))
                    ->text('name', [
                        'class'     => 'form-control',
                        'maxlength' => 255,
                        'value'     => $values[ 'name' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('firstname-group', 'div', function ($form) use ($values) {
                    $form->label('firstname-label', t('First name'))
                    ->text('firstname', [
                        'class'     => 'form-control',
                        'maxlength' => 255,
                        'value'     => $values[ 'firstname' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('password-group', 'div', function ($form) use ($values) {
                    $form->label('password-label', t('Password'))
                    ->password('password', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => $values[ 'password' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('password_confirm-group', 'div', function ($form) use ($values) {
                    $form->label('password_confirm-label', t('Confirmation of the new password'))
                    ->password('password_confirm', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => $values[ 'password_confirm' ]
                    ]);
                }, [ 'class' => 'form-group' ]);
            })
            ->token('token_step_install')
            ->submit('submit', t('Install'), [ 'class' => 'btn btn-success' ]);

        if (isset($_SESSION[ 'errors_keys' ][ $id ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ][ $id ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ][ $id ]);
        }

        return (new Template('page-install.php', $this->pathViews))
                ->addVar('form', $form);
    }

    public function hookUserCheck(string $id, ServerRequestInterface $req): void
    {
        $validator = (new Validator())
            ->setRules([
                'username'         => 'required|string|max:255',
                'email'            => 'required|string|email',
                'name'             => '!required|string|max:255',
                'firstname'        => '!required|string|max:255',
                'password'         => 'required|string',
                'password_confirm' => 'required|string|equal:@password'
            ])
            ->setInputs($req->getParsedBody());

        if ($validator->isValid()) {
            $_SESSION[ 'inputs' ][ $id ] = [
                'username'  => $validator->getInput('username'),
                'email'     => $validator->getInput('email'),
                'name'      => $validator->getInput('name'),
                'firstname' => $validator->getInput('firstname'),
                'password'  => $validator->getInput('password')
            ];
        } else {
            $_SESSION[ 'inputs' ][ $id ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ $id ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ][ $id ]          = $validator->getKeyInputErrors();
        }
    }

    public function hookModules(array &$modules): void
    {
        $modules[ 'News' ] = 'SoosyzeCore\\News\\';
    }

    public function hookSite(ContainerInterface $ci): void
    {
        $this->config
            ->set('settings.path_index', 'home')
            ->set('settings.path_no_found', 'node/8')
            ->set('settings.path_maintenance', 'node/9')
            ->set('settings.meta_title', 'Soosyze site')
            ->set('settings.logo', 'https://picsum.photos/id/30/200/200');

        $this->query
            ->insertInto('entity_page', [ 'body' ])
            ->values([ (new Template('block-features.php', $this->pathContent))->render() ])
            ->values([ (new Template('block-text.php', $this->pathContent))->render() ])
            ->values([ (new Template('block-text.php', $this->pathContent))->render() ])
            ->values([ (new Template('block-text.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-not_found.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-maintenance.php', $this->pathContent))->render() ])
            ->execute();

        $time = (string) time();
        $this->query
            ->insertInto('node', self::$columnsNode)
            ->values([ 1, 'page', $time, $time, 1, t('Home'), 1 ]) // id = 3
            ->values([ 2, 'page', $time, $time, 1, t('Basic'), 1 ])
            ->values([ 3, 'page', $time, $time, 1, t('Standard'), 1 ])
            ->values([ 4, 'page', $time, $time, 1, t('Premium'), 1 ])
            ->values([ 5, 'page', $time, $time, 1, t('About'), 1 ])
            ->values([ 6, 'page', $time, $time, 1, t('Not Found'), 1 ])
            ->values([ 7, 'page', $time, $time, 1, t('Maintenance'), 1 ])
            ->execute();

        $this->query
            ->insertInto('system_alias_url', [ 'source', 'alias' ])
            ->values([ 'node/3', 'home' ])
            ->values([ 'node/4', 'page/basic' ])
            ->values([ 'node/5', 'page/standard' ])
            ->values([ 'node/6', 'page/premium' ])
            ->values([ 'node/7', 'page/about' ])
            ->values([ 'node/8', 'maintenance' ])
            ->execute();

        $idMenuBasic    = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', 'fa fa-bolt', 'Basic', 'page/basic', 'node/4', 'menu-main', 1, 7
        ]);
        $idMenuStandard = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', 'fa fa-anchor', 'Standard', 'page/standard', 'node/5', 'menu-main',
            2, 7
        ]);
        $idMenuPremium  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', 'fa fa-gem', 'Premium', 'page/premium', 'node/6', 'menu-main', 3, 7
        ]);
        $this->lastInsertId('menu_link', self::$columnsMenu, [
            'news.index', '', 'Blog', 'news', null, 'menu-main', 2, -1
        ]);
        $idMenuAbout    = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'About', 'page/about', 'node/7', 'menu-main', 3, -1
        ]);

        /* Add children Home */
        $this->query->update('menu_link', [ 'has_children' => true ])->where('id', '=', 7)->execute();

        $this->query->insertInto('node_menu_link', [ 'node_id', 'menu_link_id' ])
            ->values([ 4, $idMenuBasic ])
            ->values([ 5, $idMenuStandard ])
            ->values([ 6, $idMenuPremium ])
            ->values([ 7, $idMenuAbout ])
            ->execute();

        /* Block content */
        $this->query
            ->insertInto('block', [
                'section', 'title',
                'weight',
                'visibility_pages', 'pages',
                'content'
            ])
            ->values([
                'header', '',
                1,
                true, '/',
                (new Template('block-learn_more.php', $this->pathContent))->render()
            ])
            ->values([
                'content_header', '<span id="text">' . t('Introduction') . '</span>',
                1,
                true, '/',
                (new Template('block-text.php', $this->pathContent))->render()
            ])
            ->values([
                'sidebar', '',
                1,
                true, '/',
                (new Template('block-about.php', $this->pathContent))->render()
            ])
            ->values([
                'footer_first', t('To join us'),
                1,
                true, 'contact',
                (new Template('block-contact.php', $this->pathContent))->render()
            ])
            ->values([
                'footer_second', t('Access map'),
                1,
                true, 'contact',
                (new Template('block-map.php', $this->pathContent))->render()
            ])
            ->execute();

        /* Block hook. */
        $this->query
            ->insertInto('block', [
                'section', 'title',
                'weight',
                'hook', 'key_block',
                'options',
                'visibility_pages', 'pages'
            ])
            ->values([
                'content_footer', t('Last News'),
                1,
                'news.last', 'news.last',
                json_encode([ 'limit' => 3, 'offset' => 0, 'more' => true ]),
                true, '/'
            ])
            ->values([
                'content_footer', '',
                2,
                'node.next_previous', 'node.next_previous',
                json_encode([
                    'display'       => 'meta-title',
                    'next_text'     => 'Next :node_type_name',
                    'previous_text' => 'Previous :node_type_name',
                    'type'          => 'article'
                ]),
                true, 'news/%/%/%/%'
            ])
            ->values([
                'sidebar', '',
                1,
                'social', 'social',
                '',
                true, '/' . PHP_EOL . 'news'
            ])
            ->values([
                'footer', '',
                1,
                'social', 'social',
                '',
                false, '/' . PHP_EOL . 'news' . PHP_EOL . 'admin/%' . PHP_EOL . 'user/%'
            ])
            ->values([
                'sidebar', t('Archives'),
                2,
                'news.archive', 'news.archive',
                json_encode([ 'expand' => false ]),
                true, '/' . PHP_EOL . 'news%'
            ])
            ->execute();
    }

    public function hookBlog(ContainerInterface $ci): void
    {
        $this->config
            ->set('settings.path_index', 'news')
            ->set('settings.path_no_found', '')
            ->set('settings.path_maintenance', 'node/4')
            ->set('settings.meta_title', 'Soosyze blog')
            ->set('settings.logo', 'https://picsum.photos/id/30/200/200');

        $this->query
            ->insertInto('block', [ 'section', 'title', 'weight', 'content' ])
            ->values([
                'footer_second', 'Lorem ipsum dolor', 1,
                (new Template('block-text.php', $this->pathContent))->render()
            ])
            ->execute();

        $this->query
            ->insertInto('block', [
                'section', 'title',
                'weight',
                'hook', 'key_block',
                'options',
                'visibility_pages', 'pages'
            ])
            ->values([
                'content_footer', '',
                2,
                'node.next_previous', 'node.next_previous',
                json_encode([
                    'display'       => 'meta-title',
                    'next_text'     => 'Next :node_type_name',
                    'previous_text' => 'Previous :node_type_name',
                    'type'          => 'article'
                ]),
                true, 'news/%/%/%/%'
            ])
            ->values([
                'sidebar', t('Archives'),
                1,
                'news.archive', 'news.archive',
                json_encode([ 'expand' => false ]),
                false, 'admin/%' . PHP_EOL . 'user/%'
            ])
            ->values([
                'footer_first', t('Follow us'),
                1,
                'social', 'social',
                '[]',
                false, 'admin/%' . PHP_EOL . 'user/%'
            ])
            ->execute();

        $this->query
            ->insertInto('entity_page', [ 'body' ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-not_found.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-maintenance.php', $this->pathContent))->render() ])
            ->execute();

        $time = (string) time();

        $idNodeAbout = $this->lastInsertId(
            'node',
            self::$columnsNode,
            [ 1, 'page', $time, $time, 1, t('About'), 1 ]
        );

        $idNodeNotFound = $this->lastInsertId(
            'node',
            self::$columnsNode,
            [ 2, 'page', $time, $time, 1, t('Not Found'), 1 ]
        );

        $idNodeMaintenance = $this->lastInsertId(
            'node',
            self::$columnsNode,
            [ 3, 'page', $time, $time, 1, t('Maintenance'), 1 ]
        );

        $this->query
            ->insertInto('system_alias_url', [ 'source', 'alias' ])
            ->values([ 'node/' . $idNodeAbout, 'page/about' ])
            ->values([ 'node/' . $idNodeNotFound, 'page/about' ])
            ->values([ 'node/' . $idNodeMaintenance, 'maintenance' ])
            ->execute();

        $idMenuAbout = $this->lastInsertId(
            'menu_link',
            self::$columnsMenu,
            [ 'node.show', '', 'About', 'page/about', 'node/' . $idNodeAbout, 'menu-main', 3, -1 ]
        );

        $this->query->insertInto('node_menu_link', [ 'node_id', 'menu_link_id' ])
            ->values([ $idNodeAbout, $idMenuAbout ])
            ->execute();
    }

    public function hookPortfolio(ContainerInterface $ci): void
    {
        $this->query
            ->insertInto('entity_page', [ 'body' ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-education.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-project.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-about.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-not_found.php', $this->pathContent))->render() ])
            ->values([ (new Template('page-maintenance.php', $this->pathContent))->render() ])
            ->values([ t('Not Found') ])
            ->execute();

        $time = (string) time();

        $idNodeSite      = $this->lastInsertId('node', self::$columnsNode, [
            1, 'page', $time, $time, 1, t('Home'), 1
        ]);
        $idNodeEducation = $this->lastInsertId('node', self::$columnsNode, [
            2, 'page', $time, $time, 1, t('Education'), 1
        ]);
        $idNodeProjects  = $this->lastInsertId('node', self::$columnsNode, [
            3, 'page', $time, $time, 1, t('Projects'), 1
        ]);
        $idNodeProject1  = $this->lastInsertId('node', self::$columnsNode, [
            4, 'page', $time, $time, 1, t('Project 1'), 1
        ]);
        $idNodeProject2  = $this->lastInsertId('node', self::$columnsNode, [
            5, 'page', $time, $time, 1, t('Project 2'), 1
        ]);
        $idNodeProject3  = $this->lastInsertId('node', self::$columnsNode, [
            6, 'page', $time, $time, 1, t('Project 3'), 1
        ]);
        $idNodeProject4  = $this->lastInsertId('node', self::$columnsNode, [
            7, 'page', $time, $time, 1, t('Project 4'), 1
        ]);

        $idNodeNotFound    = $this->lastInsertId('node', self::$columnsNode, [
            8, 'page', $time, $time, 1, t('Not Found'), 1
        ]);
        $idNodeMaintenance = $this->lastInsertId('node', self::$columnsNode, [
            9, 'page', $time, $time, 1, t('Maintenance'), 1
        ]);

        /* Add children Project */
        $this->query->update('menu_link', [ 'has_children' => true ])->where('id', '=', $idNodeProjects)->execute();

        $this->query
            ->insertInto('system_alias_url', [ 'source', 'alias' ])
            ->values([ 'node/' . $idNodeSite, 'home' ])
            ->values([ 'node/' . $idNodeEducation, 'education' ])
            ->values([ 'node/' . $idNodeProjects, 'project' ])
            ->values([ 'node/' . $idNodeProject1, 'project/1' ])
            ->values([ 'node/' . $idNodeProject2, 'project/2' ])
            ->values([ 'node/' . $idNodeProject3, 'project/3' ])
            ->values([ 'node/' . $idNodeProject4, 'project/4' ])
            ->execute();

        $idMenuEducation = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Education', 'education', 'node/' . $idNodeEducation, 'menu-main', 3, -1
        ]);
        $idMenuProjects  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Projects', 'project', 'node/' . $idNodeProjects, 'menu-main', 3, -1
        ]);
        $idMenuProject1  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Project 1', 'project/1', 'node/' . $idNodeProject1, 'menu-main', 4, $idMenuProjects
        ]);
        $idMenuProject2  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Project 2', 'project/2', 'node/' . $idNodeProject2, 'menu-main', 5, $idMenuProjects
        ]);
        $idMenuProject3  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Project 3', 'project/3', 'node/' . $idNodeProject3, 'menu-main', 6, $idMenuProjects
        ]);
        $idMenuProject4  = $this->lastInsertId('menu_link', self::$columnsMenu, [
            'node.show', '', 'Project 4', 'project/4', 'node/' . $idNodeProject4, 'menu-main', 7, $idMenuProjects
        ]);

        $this->query->update('menu_link', [ 'has_children' => true ])->where('id', '=', $idMenuProjects)->execute();

        $this->query->insertInto('node_menu_link', [ 'node_id', 'menu_link_id' ])
            ->values([ $idNodeEducation, $idMenuEducation ])
            ->values([ $idNodeProjects, $idMenuProjects ])
            ->values([ $idNodeProject1, $idMenuProject1 ])
            ->values([ $idNodeProject2, $idMenuProject2 ])
            ->values([ $idNodeProject3, $idMenuProject3 ])
            ->values([ $idNodeProject4, $idMenuProject4 ])
            ->execute();

        $this->query
            ->insertInto('block', [ 'section', 'title', 'weight', 'content' ])
            ->values([
                'sidebar', '', 1,
                (new Template('block-about.php', $this->pathContent))->render()
            ])
            ->execute();

        $this->query
            ->insertInto('block', [
                'section', 'title', 'weight', 'content', 'hook', 'key_block'
            ])
            ->values([
                'sidebar', '', 1, '', 'social', 'social'
            ])
            ->execute();

        $this->config
            ->set('settings.path_index', 'node/1')
            ->set('settings.path_no_found', 'node/' . $idNodeNotFound)
            ->set('settings.path_maintenance', 'node/' . $idNodeMaintenance)
            ->set('settings.meta_title', 'Soosyze portfolio')
            ->set('settings.logo', 'https://picsum.photos/id/30/200/200');
    }

    public function hookOnePage(ContainerInterface $ci): void
    {
        $this->config
            ->set('settings.path_index', 'node/1')
            ->set('settings.path_no_found', '')
            ->set('settings.meta_title', 'Soosyze One page')
            ->set('settings.logo', 'https://picsum.photos/id/30/200/200');

        $this->query
            ->insertInto('entity_page', [ 'body' ])
            ->values([ (new Template('block-features.php', $this->pathContent))->render() ])
            ->execute();

        $time = (string) time();

        $idNodeSite = $this->lastInsertId('node', self::$columnsNode, [
            1, 'page', $time, $time, 1, 'Ipsum sed adipiscing', 1
        ]);

        $this->query
            ->insertInto('system_alias_url', [ 'source', 'alias' ])
            ->values([ 'node/' . $idNodeSite, 'index' ])
            ->execute();

        $this->query
            ->insertInto('menu_link', [
                'key', 'menu', 'link', 'link_router', 'weight', 'parent', 'title_link', 'fragment'
            ])
            ->values([ 'node.show', 'menu-main', '/', 'node/' . $idNodeSite, 2, -1, 'Introduction', 'text' ])
            ->values([ 'node.show', 'menu-main', '/', 'node/' . $idNodeSite, 3, -1, 'Features', 'features' ])
            ->values([ 'node.show', 'menu-main', '/', 'node/' . $idNodeSite, 5, -1, 'About', 'img' ])
            ->values([ 'node.show', 'menu-main', '/', 'node/' . $idNodeSite, 6, -1, 'Social media', 'social' ])
            ->execute();

        $this->query
            ->insertInto('block', [
                'section', 'title', 'weight', 'visibility_pages', 'pages', 'content'
            ])
            ->values([
                'header', '', 1, true, '/',
                (new Template('block-learn_more.php', $this->pathContent))->render()
            ])
            ->values([
                'content_header', '<span id="text">' . t('Introduction') . '</span>',
                1, true,
                '/',
                (new Template('block-text.php', $this->pathContent))->render()
            ])
            ->values([
                'content_footer', '<span id="img">' . t('About') . '</span>', 1,
                true, '/',
                (new Template('block-img.php', $this->pathContent))->render()
            ])
            ->values([
                'footer_first', 'Lorem ipsum', 1, false, 'admin/%' . PHP_EOL . 'user/%',
                (new Template('block-text.php', $this->pathContent))->render()
            ])
            ->execute();

        $this->query
            ->insertInto('block', [
                'section', 'title', 'weight', 'content', 'hook', 'key_block'
            ])
            ->values([
                'footer_second',
                '<span id="social">' . t('Follow us') . '</span>',
                1,
                '',
                'social',
                'social'
            ])
            ->execute();
    }

    private function lastInsertId(string $table, array $columns, array $values): int
    {
        $this->query
            ->insertInto($table, $columns)
            ->values($values)
            ->execute();

        return $this->schema->getIncrement($table);
    }
}
