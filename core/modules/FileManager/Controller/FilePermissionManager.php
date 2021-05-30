<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;

class FilePermissionManager extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin(): ResponseInterface
    {
        $values = self::query()->from('profil_file')->orderBy('profil_weight')->fetchAll();

        $this->container->callHook('filemanager.permission.admin.form.data', [ &$values ]);

        $form = new FormBuilder([
            'action' => self::router()->getRoute('filemanager.permission.admin.check'),
            'method' => 'post'
        ]);

        foreach ($values as &$profil) {
            $profil[ 'roles' ] = self::fileprofil()->getRolesUserByProfil($profil[ 'profil_file_id' ]);

            $form->group("profil_{$profil[ 'profil_file_id' ]}-group", 'div', function ($form) use ($profil) {
                $form->group('profil_weight-flex', 'div', function ($form) use ($profil) {
                    $form->number("profil_weight-{$profil[ 'profil_file_id' ]}", [
                        ':actions' => 1,
                        'class'    => 'form-control',
                        'max'      => 50,
                        'min'      => 1,
                        'value'    => $profil[ 'profil_weight' ]
                    ]);
                }, [ 'class' => 'form-group-flex' ]);
            });
        }
        unset($profil);
        $form->token('token_profil_form')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ]);

        $this->container->callHook('filemanager.permission.admin.form', [
            &$form, $values
        ]);

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
                    'icon'       => '<i class="fa fa-folder" aria-hidden="true"></i>',
                    'title_main' => t('Administer file permissions')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', self::user()->getUserManagerSubmenu('filemanager.permission.admin'))
                ->make('page.content', 'filemanager/content-file_permission_manager-admin.php', $this->pathViews, [
                    'form'     => $form,
                    'link_add' => self::router()->getRoute('filemanager.permission.create'),
                    'profils'  => $values,
                    'router'   => self::router()
                ]);
    }

    public function adminCheck(ServerRequestInterface $req): ResponseInterface
    {
        $profils = self::query()->from('profil_file')->fetchAll();

        $validator = (new Validator())
            ->addRule('token_profil_form', 'token')
            ->setInputs($req->getParsedBody());

        foreach ($profils as $profil) {
            $validator
                ->addRule("profil_weight-{$profil[ 'profil_file_id' ]}", 'required|numeric|between:1,50');
        }

        $this->container->callHook('filemanager.permission.admin.check.validator', [
            &$validator
        ]);

        if ($validator->isValid()) {
            foreach ($profils as $profil) {
                $data = [
                    'profil_weight' => (int) $validator->getInput("profil_weight-{$profil[ 'profil_file_id' ]}")
                ];

                $this->container->callHook('filemanager.permission.admin.check.before', [
                    &$validator, &$data
                ]);

                self::query()
                    ->update('profil_file', $data)
                    ->where('profil_file_id', '=', $profil[ 'profil_file_id' ])
                    ->execute();

                $this->container->callHook('filemanager.permission.admin.check.after', [
                    &$validator
                ]);
            }

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        }

        return new Redirect(self::router()->getRoute('filemanager.permission.admin'));
    }
}
