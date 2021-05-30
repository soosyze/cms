<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Menu\Form\FormLink;

class Link extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function create(string $nameMenu, ServerRequestInterface $req): ResponseInterface
    {
        if (!self::menu()->getMenu($nameMenu)->fetch()) {
            return $this->get404($req);
        }
        $values = [];
        $this->container->callHook('menu.link.create.form.data', [ &$values ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $values += $_SESSION[ 'inputs' ];
            unset($_SESSION[ 'inputs' ]);
        }

        $action = self::router()->getRoute('menu.link.store', [ ':menu' => $nameMenu ]);

        $form = (new FormLink([ 'action' => $action, 'method' => 'post' ], self::router()))
            ->setValues($values)
            ->makeFields();

        $this->container->callHook('menu.link.create.form', [ &$form, $values ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-link" aria-hidden="true"></i>',
                    'title_main' => t('Add a link')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'menu/content-link-form.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function store(string $nameMenu, ServerRequestInterface $req): ResponseInterface
    {
        if (!self::menu()->getMenu($nameMenu)->fetch()) {
            return $this->get404($req);
        }

        $validator = $this->getValidator($req);

        $this->container->callHook('menu.link.store.validator', [ &$validator ]);

        $infoUrlOrRoute = self::menu()->getInfo($validator->getInput('link'), $req);

        if ($validator->isValid()) {
            $data = [
                'active'      => true,
                'fragment'    => $infoUrlOrRoute[ 'fragment' ],
                'icon'        => $validator->getInput('icon'),
                'key'         => $infoUrlOrRoute[ 'key' ],
                'link'        => $infoUrlOrRoute[ 'link' ],
                'link_router' => $infoUrlOrRoute[ 'link_router' ],
                'menu'        => $nameMenu,
                'parent'      => -1,
                'target_link' => (bool) $validator->getInput('target_link'),
                'title_link'  => $validator->getInput('title_link'),
                'weight'      => 1
            ];

            $this->container->callHook('menu.link.store.before', [ $validator, &$data ]);
            self::query()
                ->insertInto('menu_link', array_keys($data))
                ->values($data)
                ->execute();
            $this->container->callHook('menu.link.store.after', [ $validator ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];

            return new Redirect(self::router()->getRoute('menu.show', [
                ':menu' => $nameMenu
            ]));
        }

        $_SESSION[ 'inputs' ]               = $validator->getInputs();
        $_SESSION[ 'inputs' ][ 'link' ]     = $infoUrlOrRoute[ 'link' ];
        $_SESSION[ 'inputs' ][ 'fragment' ] = $infoUrlOrRoute[ 'fragment' ];
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect(self::router()->getRoute('menu.link.create', [
            ':menu' => $nameMenu
        ]));
    }

    public function edit(string $name, int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($values = self::menu()->find($id))) {
            return $this->get404($req);
        }

        $this->container->callHook('menu.link.edit.form.data', [ &$values ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $values += $_SESSION[ 'inputs' ];
            unset($_SESSION[ 'inputs' ]);
        }

        $action = self::router()->getRoute('menu.link.update', [
            ':menu' => $name, ':id' => $id
        ]);

        $form = (new FormLink([ 'action' => $action, 'method' => 'post' ], self::router()))
            ->setValues($values)
            ->setRewrite(self::router()->isRewrite())
            ->makeFields();

        $this->container->callHook('menu.link.edit.form', [ &$form, $values ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-link" aria-hidden="true"></i>',
                    'title_main' => t('Edit a link')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'menu/content-link-form.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function update(string $nameMenu, int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!self::menu()->find($id)) {
            return $this->get404($req);
        }

        $validator = $this->getValidator($req);

        $this->container->callHook('menu.link.update.validator', [ &$validator ]);

        $infoUrlOrRoute = self::menu()->getInfo($validator->getInput('link'), $req);

        if ($validator->isValid()) {
            $data = [
                'fragment'    => $infoUrlOrRoute[ 'fragment' ],
                'icon'        => $validator->getInput('icon'),
                'key'         => $infoUrlOrRoute[ 'key' ],
                'link'        => $infoUrlOrRoute[ 'link' ],
                'link_router' => $infoUrlOrRoute[ 'link_router' ],
                'query'       => $infoUrlOrRoute[ 'query' ],
                'target_link' => (bool) $validator->getInput('target_link'),
                'title_link'  => $validator->getInput('title_link')
            ];

            $this->container->callHook('menu.link.update.before', [ $validator, &$data ]);
            self::query()
                ->update('menu_link', $data)
                ->where('id', '==', $id)
                ->execute();
            $this->container->callHook('menu.link.update.after', [ $validator ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];

            return new Redirect(self::router()->getRoute('menu.show', [
                ':menu' => $nameMenu
            ]));
        }

        $_SESSION[ 'inputs' ]               = $validator->getInputs();
        $_SESSION[ 'inputs' ][ 'link' ]     = $infoUrlOrRoute[ 'link' ];
        $_SESSION[ 'inputs' ][ 'query' ]    = $infoUrlOrRoute[ 'query' ];
        $_SESSION[ 'inputs' ][ 'fragment' ] = $infoUrlOrRoute[ 'fragment' ];
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect(self::router()->getRoute('menu.link.edit', [
            ':menu' => $nameMenu,
            ':id'   => $id
        ]));
    }

    public function delete(string $nameMenu, int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!($linkMenu = self::menu()->find($id))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'id'   => 'required|int',
                'name' => 'required|string|max:255'
            ])
            ->setInputs([ 'name' => $nameMenu, 'id' => $id ]);

        $this->container->callHook('menu.link.delete.validator', [ &$validator, $id ]);

        if ($validator->isValid()) {
            $this->container->callHook('menu.link.delete.before', [ $validator, $id ]);

            self::menu()->deleteLinks(static function () use ($linkMenu) {
                return [ $linkMenu ];
            });

            $this->container->callHook('menu.link.delete.after', [ $validator, $id ]);
        }

        $route = self::router()->getRoute('menu.show', [ ':menu' => $nameMenu ]);

        return new Redirect($route);
    }

    private function getValidator(ServerRequestInterface $req): Validator
    {
        return (new Validator())
                ->setRules([
                    'icon'            => '!required|max:255|fontawesome:solid,brands',
                    'link'            => 'required|route_or_url',
                    'target_link'     => 'bool',
                    'title_link'      => 'required|string|max:255',
                    'token_link_form' => 'required|token'
                ])
                ->setLabels([
                    'icon'        => t('Icon'),
                    'link'        => t('Link'),
                    'target_link' => t('Target'),
                    'title_link'  => t('Link title')
                ])
                ->setInputs($req->getParsedBody());
    }
}
