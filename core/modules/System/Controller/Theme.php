<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\System\Form\FormThemeAdmin;
use SoosyzeCore\System\Form\FormThemePublic;

class Theme extends \Soosyze\Controller
{
    const TYPE_ADMIN = 'admin';

    const TYPE_PUBLIC = 'public';

    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function index(): ResponseInterface
    {
        return $this->admin(self::TYPE_PUBLIC);
    }

    public function admin(string $type): ResponseInterface
    {
        $composers = self::composer()->getThemeComposers();

        $activeThemeName = $type === self::TYPE_ADMIN
            ? self::config()->get('settings.theme_admin')
            : self::config()->get('settings.theme');

        $activeTheme = $composers[ $activeThemeName ];

        unset($composers[ $activeThemeName ]);

        $themes = [];

        $params[ 'token' ] = $this->getToken();

        foreach ($composers as $key => $composer) {
            $theme = $composer[ 'extra' ][ 'soosyze' ];

            $addTheme = ($type === self::TYPE_PUBLIC && empty($theme[ 'options' ][ 'admin' ])) ||
                ($type === self::TYPE_ADMIN && !empty($theme[ 'options' ][ 'admin' ]));

            if ($addTheme) {
                $themes[ $key ] = $composer;

                $uriActivate = self::router()
                    ->getRequestByRoute('system.theme.active', [
                        ':type' => $type,
                        ':name' => $theme[ 'title' ]
                    ])
                    ->getUri();

                $themes[ $key ][ 'link_activate' ] = $uriActivate->withQuery(
                    http_build_query($params)
                );
            }
        }

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-paint-brush" aria-hidden="true"></i>',
                    'title_main' => t('Themes')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', $this->getListThemeSubmenu($type))
                ->make('page.content', 'system/content-themes-admin.php', $this->pathViews, [
                    'active_theme' => $activeTheme,
                    'link_edit'    => self::module()->has('Block')
                        ? self::router()->getRoute('block.section.admin', [ ':theme' => $type ])
                        : null,
                    'link_setting' => self::router()->getRoute('system.theme.edit', [
                        ':type' => $type
                    ]),
                    'themes'       => $themes
        ]);
    }

    public function active(string $type, string $name, ServerRequestInterface $req): ResponseInterface
    {
        $themes = $this->getThemes($type);

        $route     = self::router()->getRoute('system.theme.admin', [
            ':type' => $type
        ]);
        $validator = (new Validator())
            ->setRules([
                'token' => 'token',
                'name'  => 'inarray:' . implode(',', $themes)
            ])
            ->setInputs($req->getQueryParams() + [ 'name' => $name ]);

        if (!$validator->isValid()) {
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();

            return new Redirect($route, 302);
        }

        $outInstall = $this->installTheme($type, $name);

        if (empty($outInstall)) {
            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'messages' ][ 'errors' ] = $outInstall;
        }

        return new Redirect($route, 302);
    }

    public function edit(string $type): ResponseInterface
    {
        $values = self::config()->get('settings');

        $attr = [
            'action'  => self::router()->getRoute('system.theme.update', [
                ':type' => $type
            ]),
            'enctype' => 'multipart/form-data',
            'method'  => 'post'
        ];
        $form = $type === self::TYPE_ADMIN
            ? (new FormThemeAdmin($attr))
            : (new FormThemePublic($attr, self::file()));

        $form->setValues($values)
            ->makeFields()
            ->token('setting_theme')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ])
            ->html('cancel', '<button:attr>:content</button>', [
                ':content' => t('Cancel'),
                'class'    => 'btn btn-danger',
                'onclick'  => 'javascript:history.back();',
                'type'     => 'button'
        ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-paint-brush" aria-hidden="true"></i>',
                    'title_main' => t('Theme settings')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', $this->getListThemeSubmenu($type))
                ->make('page.content', 'system/content-form.php', $this->pathViews, [
                    'form'      => $form,
                    'link_edit' => self::router()->getRoute('system.theme.admin', [
                        ':type' => $type
                    ])
                ]);
    }

    public function update(string $type, ServerRequestInterface $req): ResponseInterface
    {
        $validator = (new Validator())
            ->setRules(
                self::TYPE_ADMIN === $type
                ? [
                'theme_admin_dark' => 'bool'
                ]
                : [
                'favicon' => '!required|image:png,ico|image_dimensions_height:16,310|image_dimensions_width:16,310|max:100Kb',
                'logo'    => '!required|image|max:200Kb'
                ]
            )
            ->setLabels([
                'favicon' => t('Favicon'),
                'logo'    => t('Logo')
            ])
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles());

        $inputsFile = self::TYPE_ADMIN === $type
            ? []
            : [ 'favicon', 'logo' ];

        if ($validator->isValid()) {
            $data = self::TYPE_ADMIN === $type
                ? [
                'theme_admin_dark' => (bool) $validator->getInput('theme_admin_dark')
                ]
                : [];

            foreach ($data as $key => $value) {
                self::config()->set('settings.' . $key, $value);
            }

            foreach ($inputsFile as $file) {
                $this->saveFile($file, $validator);
            }

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputsWithout($inputsFile);
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        }

        return new Redirect(
            self::router()->getRoute('system.theme.admin', [
                ':type' => $type
            ])
        );
    }

    public function getListThemeSubmenu(string $keyRoute): array
    {
        $menus = [
            [
                'key'        => self::TYPE_PUBLIC,
                'link'       => self::router()->getRoute('system.theme.admin', [
                    ':type' => self::TYPE_PUBLIC
                ]),
                'title_link' => t('Public theme')
            ], [
                'key'        => self::TYPE_ADMIN,
                'link'       => self::router()->getRoute('system.theme.admin', [
                    ':type' => self::TYPE_ADMIN
                ]),
                'title_link' => t('Admin theme')
            ]
        ];

        return [ 'key_route' => $keyRoute, 'menu' => $menus ];
    }

    private function installTheme(string $type, string $title): array
    {
        $errors = [];

        $composers = self::composer()->getThemeComposers();

        if (!isset($composers[ $title ])) {
            $errors[] = t('The :title module does not exist.', [ ':title' => $title ]);
        } elseif ($out = self::composer()->validComposer($title, $composers)) {
            $errors += $out;
        } elseif ($out = self::composer()->validComposerExtendTheme($title, $composers)) {
            $errors += $out;
        } elseif ($out = self::composer()->validComposerExtraTheme($title, $composers)) {
            $errors += $out;
        } elseif ($out = self::composer()->validRequirePhp($title, $composers)) {
            $errors += $out;
        } elseif ($out = self::composer()->validRequireExtLib($title, $composers)) {
            $errors += $out;
        }

        if (!empty($errors)) {
            return $errors;
        }

        /* Installation */
        $extendClass = self::composer()->getExtendClass($title, $composers);

        $extend = new $extendClass();

        $extend->boot();

        $composers[ $title ] += [
            'dir'          => $extend->getDir(),
            'translations' => $extend->getTranslations()
        ];

        self::module()->loadTranslations([ $composers[ $title ] ]);

        self::config()->set(
            $type === self::TYPE_ADMIN
                ? 'settings.theme_admin'
                : 'settings.theme',
            $title
        );

        return [];
    }

    private function getThemes(string $type): array
    {
        $out = [];

        $composers = self::composer()->getThemeComposers();

        foreach ($composers as $key => $composer) {
            $theme = $composer[ 'extra' ][ 'soosyze' ];

            $addTheme = ($type === self::TYPE_PUBLIC && empty($theme[ 'options' ][ 'admin' ])) ||
                ($type === self::TYPE_ADMIN && !empty($theme[ 'options' ][ 'admin' ]));

            if ($addTheme) {
                $out[] = $key;
            }
        }

        return $out;
    }

    private function getToken(): string
    {
        $token = uniqid((string) rand(), true);

        $_SESSION[ 'token' ][ 'token' ] = $token;

        $_SESSION[ 'token_time' ][ 'token' ] = time();

        return $token;
    }

    private function saveFile(string $key, Validator $validator): void
    {
        self::file()
            ->add($validator->getInput($key), $validator->getInput("file-$key-name"))
            ->setName($key)
            ->setPath('/config')
            ->isResolvePath()
            ->callGet(function ($key, $name) {
                return self::config()->get("settings.$key");
            })
            ->callMove(function ($key, $name, $move) {
                self::config()->set("settings.$key", $move);
            })
            ->callDelete(function ($key, $name) {
                self::config()->set("settings.$key", '');
            })
            ->save();
    }
}
