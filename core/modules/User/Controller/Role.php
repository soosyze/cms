<?php

namespace SoosyzeCore\User\Controller;

use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\User\Form\FormUserRole;

class Role extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews  = dirname(__DIR__) . '/Views/';
    }

    public function admin($req)
    {
        $roles = self::query()->from('role')->orderBy('role_weight')->fetchAll();
        foreach ($roles as &$role) {
            $role[ 'link_edit' ] = self::router()->getRoute('user.role.edit', [
                ':id' => $role[ 'role_id' ]
            ]);
            if ($role[ 'role_id' ] > 3) {
                $role[ 'link_remove' ] = self::router()->getRoute('user.role.remove', [
                    ':id' => $role[ 'role_id' ]
                ]);
            }
        }

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Administer roles')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'page-role.php', $this->pathViews, [
                    'roles'    => $roles,
                    'link_add' => self::router()->getRoute('user.role.create')
        ]);
    }

    public function create($req)
    {
        $data = [];
        $this->container->callHook('role.create.form.data', [ &$data ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormUserRole([
            'method' => 'post',
            'action' => self::router()->getRoute('user.role.store')
            ]))->generate();

        $this->container->callHook('role.create.form', [ &$form, $data ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'style' => 'border-color:#a94442;' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Creating a role')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'form-role.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function store($req)
    {
        $validator = (new Validator())
            ->setRules([
                'role_label'        => 'required|string|max:255|htmlsc',
                'role_description'  => '!required|string|max:255|htmlsc',
                'role_weight'       => '!required|int|between:1,50',
                'role_color'        => '!required|colorhex',
                'role_icon'         => '!required|max:255|fontawesome:solid,brands',
                'token_role_submit' => 'required|token'
            ])
            ->setLabel([
                'role_label'       => t('Name'),
                'role_description' => t('Description'),
                'role_weight'      => t('Weight'),
                'role_color'       => t('Color'),
                'role_icon'        => t('Icon')
            ])
            ->setInputs($req->getParsedBody());

        $this->container->callHook('role.store.validator', [ &$validator ]);
        if ($validator->isValid()) {
            $role_weight = $validator->getInput('role_weight');
            $role_color  = $validator->getInput('role_color');
            $role_icon   = $validator->getInput('role_icon');
            $value = [
                'role_label'       => $validator->getInput('role_label'),
                'role_description' => $validator->getInput('role_description'),
                'role_weight'      => !empty($role_weight)
                ? $role_weight
                : 1,
                'role_color'       => !empty($role_color)
                ? strtolower($role_color)
                : '#e6e7f4',
                'role_icon'        => !empty($role_icon)
                    ? strtolower($role_icon)
                    : 'fa fa-user',
            ];

            $this->container->callHook('role.store.before', [ &$validator, &$value ]);
            self::query()->insertInto('role', array_keys($value))->values($value)->execute();
            $this->container->callHook('role.store.after', [ $validator ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
            $route                               = self::router()->getRoute('user.role.admin');
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
            $route                              = self::router()->getRoute('user.role.create');
        }

        return new Redirect($route);
    }

    public function edit($id, $req)
    {
        if (!($data = self::query()->from('role')->where('role_id', '==', $id)->fetch())) {
            return $this->get404($req);
        }

        $this->container->callHook('role.edit.form.data', [ &$data, $id ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormUserRole([
            'method' => 'post',
            'action' => self::router()->getRoute('user.role.update', [ ':id' => $id ])
            ]))->content($data)->generate();

        $this->container->callHook('role.edit.form', [ &$form, $data, $id ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'style' => 'border-color:#a94442;' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Editing a role')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'form-role.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function update($id, $req)
    {
        if (!self::query()->from('role')->where('role_id', '==', $id)->fetch()) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'role_label'        => 'required|string|max:255|htmlsc',
                'role_description'  => '!required|string|max:255|htmlsc',
                'role_weight'       => 'required|int|between:1,50',
                'role_color'        => '!required|colorhex',
                'role_icon'         => '!required|max:255|fontawesome:solid,brands',
                'token_role_submit' => 'required|token'
            ])
            ->setLabel([
                'role_label'       => t('Name'),
                'role_description' => t('Description'),
                'role_weight'      => t('Weight'),
                'role_color'       => t('Color'),
                'role_icon'        => t('Icon')
            ])
            ->setInputs($req->getParsedBody());

        $this->container->callHook('role.update.validator', [ &$validator ]);
        if ($validator->isValid()) {
            $value = [
                'role_label'       => $validator->getInput('role_label'),
                'role_description' => $validator->getInput('role_description'),
                'role_weight'      => $validator->getInput('role_weight'),
                'role_color'       => $validator->getInput('role_color'),
                'role_icon'        => $validator->getInput('role_icon')
            ];

            $this->container->callHook('role.udpate.before', [ &$validator, &$value, $id ]);
            self::query()->update('role', $value)->where('role_id', '==', $id)->execute();
            $this->container->callHook('role.udpate.after', [ $validator, $value, $id ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
            $route = self::router()->getRoute('user.role.admin');
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
            $route = self::router()->getRoute('user.role.edit', [ ':id' => $id ]);
        }

        return new Redirect($route);
    }

    public function remove($id, $req)
    {
        if (!($data = self::query()->from('role')->where('role_id', '==', $id)->fetch())) {
            return $this->get404($req);
        }

        $this->container->callHook('role.remove.form.data', [ &$data, $id ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormUserRole([
            'method' => 'post',
            'action' => self::router()->getRoute('user.role.delete', [ ':id' => $id ])
            ]))->generateDelete();

        $this->container->callHook('role.remove.form', [ &$form, $data, $id ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'style' => 'border-color:#a94442;' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Deleting the :name role', [':name' => $data[ 'role_label' ]])
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'form-role.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function delete($id, $req)
    {
        if (!self::query()->from('role')->where('role_id', '==', $id)->fetch()) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'id'                => 'required|int|!inarray:1,2,3',
                'token_role_delete' => 'required|token'
            ])
            ->setInputs($req->getParsedBody())
            ->addInput('id', $id);

        $this->container->callHook('role.delete.validator', [ &$validator, $id ]);

        if ($validator->isValid()) {
            $this->container->callHook('role.delete.before', [ $validator, $id ]);
            self::query()->from('user_role')->where('role_id', '==', $id)->delete()->execute();
            self::query()->from('role')->where('role_id', '==', $id)->delete()->execute();
            $this->container->callHook('role.delete.after', [ $validator, $id ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
            $route = self::router()->getRoute('user.role.admin');
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
            $route = self::router()->getRoute('user.role.remove', [ ':id' => $id ]);
        }

        return new Redirect($route);
    }
}
