<?php

namespace SoosyzeCore\User\Controller;

use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;

class RoleManager extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin($req)
    {
        $values = self::query()->from('role')->orderBy('role_weight')->fetchAll();

        $this->container->callHook('user.role.admin.form.data', [ &$values ]);

        $form = new FormBuilder([
            'action' => self::router()->getRoute('user.role.admin.check'),
            'method' => 'post'
        ]);

        foreach ($values as &$role) {
            $role[ 'link_edit' ] = self::router()->getRoute('user.role.edit', [
                ':id' => $role[ 'role_id' ]
            ]);
            if ($role[ 'role_id' ] > 3) {
                $role[ 'link_remove' ] = self::router()->getRoute('user.role.remove', [
                    ':id' => $role[ 'role_id' ]
                ]);
            }
            $form->group("role_{$role[ 'role_id' ]}-group", 'div', function ($form) use ($role) {
                $form->group('role_weight-flex', 'div', function ($form) use ($role) {
                    $form->number("role_weight-{$role[ 'role_id' ]}", [
                        ':actions' => 1,
                        'class'    => 'form-control',
                        'max'      => 50,
                        'min'      => 1,
                        'value'    => $role[ 'role_weight' ]
                    ]);
                }, [ 'class' => 'form-group-flex' ]);
            });
        }
        unset($role);

        $form->token('token_role_form')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ]);

        $this->container->callHook('user.role.admin.form', [ &$form, $values ]);

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
                    'icon'       => '<i class="fa fa-user-tag" aria-hidden="true"></i>',
                    'title_main' => t('Administer roles')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', self::user()->getUserManagerSubmenu('user.role.admin'))
                ->make('page.content', 'user/content-role_manager-admin.php', $this->pathViews, [
                    'form'     => $form,
                    'link_add' => self::router()->getRoute('user.role.create'),
                    'roles'    => $values
                ]);
    }

    public function adminCheck($req)
    {
        $roles = self::query()->from('role')->fetchAll();

        $validator = (new Validator())
            ->addRule('token_role_form', 'token')
            ->setInputs($req->getParsedBody());

        foreach ($roles as $role) {
            $validator
                ->addRule("role_weight-{$role[ 'role_id' ]}", 'required|between_numeric:1,50')
                ->addLabel("role_weight-{$role[ 'role_id' ]}", t($role[ 'role_label' ]));
        }

        $this->container->callHook('user.role.admin.check.validator', [ &$validator ]);

        if ($validator->isValid()) {
            foreach ($roles as $role) {
                $data = [
                    'role_weight' => (int) $validator->getInput("role_weight-{$role[ 'role_id' ]}")
                ];

                $this->container->callHook('user.role.admin.check.before', [
                    &$validator, &$data
                ]);

                self::query()
                    ->update('role', $data)
                    ->where('role_id', '=', $role[ 'role_id' ])
                    ->execute();

                $this->container->callHook('user.role.admin.check.after', [ &$validator ]);
            }

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        }

        return new Redirect(self::router()->getRoute('user.role.admin'));
    }
}
