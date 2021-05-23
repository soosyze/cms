<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;

class ConfigMailer implements \SoosyzeCore\Config\ConfigInterface
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    public function defaultValues(): array
    {
        return [
            'driver'          => '',
            'email'           => '',
            'smtp_encryption' => '',
            'smtp_host'       => '',
            'smtp_password'   => '',
            'smtp_port'       => '',
            'smtp_username'   => ''
        ];
    }

    public function menu(array &$menu): void
    {
        $menu[ 'mailer' ] = [
            'config'     => 'mailer',
            'title_link' => 'Email'
        ];
    }

    public function form(FormBuilder &$form, array $data, ServerRequestInterface $req): void
    {
        $form->group('information-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('information-legend', t('Information'))
                ->group('email-group', 'div', function ($form) use ($data) {
                    $form->label('email-label', t('E-mail of the site'), [
                        'data-tooltip' => t('E-mail used for the general configuration, for your contacts, the recovery of your password ...')
                    ])
                    ->email('email', [
                        'class'       => 'form-control',
                        'required'    => 1,
                        'placeholder' => t('E-mail'),
                        'value'       => $data[ 'email' ]
                    ]);
                }, self::$attrGrp);
        })
            ->group('driver-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('driver-legend', t('Sélectionner un driver'))
                ->group('driver-group', 'div', function ($form) use ($data) {
                    $form->label('driver-label', t('Driver'))
                    ->select('driver', self::getOptionsDriver(), [
                        ':selected'   => $data[ 'driver' ],
                        'class'       => 'form-control',
                        'data-toogle' => 'select',
                        'required'    => 1
                    ]);
                }, self::$attrGrp);
            })
            ->group('smtp-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('smtp-legend', t('SMTP Configuration'))
                ->group('smtp_host-group', 'div', function ($form) use ($data) {
                    $form->label('smtp_host-label', t('Host Address'))
                    ->text('smtp_host', [
                        'class'       => 'form-control',
                        'maxlength'   => 255,
                        'placeholder' => 'smtp1.example.com',
                        'value'       => $data[ 'smtp_host' ]
                    ]);
                }, self::$attrGrp)
                ->group('smtp_port-group', 'div', function ($form) use ($data) {
                    $form->label('smtp_port-label', t('Host Port'))
                    ->text('smtp_port', [
                        'class'       => 'form-control',
                        'maxlength'   => 5,
                        'placeholder' => 465,
                        'value'       => $data[ 'smtp_port' ]
                    ]);
                }, self::$attrGrp)
                ->group('smtp_encryption-group', 'div', function ($form) use ($data) {
                    $form->label('smtp_encryption-label', t('Encryption Protocol'))
                    ->select('smtp_encryption', self::getOptionsEncryption(), [
                        ':selected' => $data[ 'smtp_encryption' ],
                        'class'     => 'form-control'
                    ]);
                }, self::$attrGrp)
                ->group('smtp_username-group', 'div', function ($form) use ($data) {
                    $form->label('smtp_username-label', t('Serveur Username'))
                    ->text('smtp_username', [
                        'class'       => 'form-control',
                        'maxlength'   => 255,
                        'placeholder' => 'user@example.com',
                        'value'       => $data[ 'smtp_username' ]
                    ]);
                }, self::$attrGrp)
                ->group('smtp_password-group', 'div', function ($form) use ($data) {
                    $form->label('smtp_password-label', t('Server Password'))
                    ->password('smtp_password', [
                        'class'     => 'form-control',
                        'maxlength' => 255,
                        'value'     => $data[ 'smtp_password' ]
                    ]);
                }, self::$attrGrp);
            }, [ 'class' => 'select-pane' . ($data[ 'driver' ] === 'smtp' ? ' active' : ''), 'id' => 'smtp' ]);
    }

    public function validator(Validator &$validator): void
    {
        $rules  = [
            'driver' => 'required|inarray:mail,smtp',
            'email'  => 'required|email|max:254'
        ];
        $labels = [
            'driver' => t('Driver'),
            'email'  => t('E-mail of the site')
        ];

        if ($validator->getInput('driver') === 'smtp') {
            $rules  += [
                'smtp_encryption' => 'required|inarray:tls,ssl',
                'smtp_host'       => 'required|url',
                'smtp_password'   => 'required|string',
                'smtp_port'       => 'required|numeric|between_numeric:0,65535',
                'smtp_username'   => 'required|email'
            ];
            $labels += [
                'smtp_encryption' => t('Encryption Protocol'),
                'smtp_password'   => t('Server Password'),
                'smtp_host'       => t('Host Address'),
                'smtp_port'       => t('Host Port'),
                'smtp_username'   => t('Serveur Username')
            ];
        }

        $validator->setRules($rules)
            ->setLabels($labels);
    }

    public function before(Validator &$validator, array &$data, string $id): void
    {
        $data = [
            'driver'          => $validator->getInput('driver'),
            'email'           => $validator->getInput('email'),
            'smtp_encryption' => $validator->getInput('smtp_encryption'),
            'smtp_host'       => $validator->getInput('smtp_host'),
            'smtp_password'   => $validator->getInput('smtp_password'),
            'smtp_port'       => (int) $validator->getInput('smtp_port'),
            'smtp_username'   => $validator->getInput('smtp_username')
        ];
    }

    public function files(array &$inputFiles): void
    {
    }

    public function after(Validator &$validator, array $data, string $id): void
    {
    }

    private static function getOptionsEncryption(): array
    {
        return [
            [ 'label' => 'none', 'value' => 'none' ],
            [ 'label' => 'SSL (Secure Sockets Layer)', 'value' => 'ssl' ],
            [ 'label' => 'TLS (Transport Layer Security)', 'value' => 'tls' ]
        ];
    }

    private static function getOptionsDriver(): array
    {
        return [
            [ 'label' => 'mail', 'value' => 'mail' ],
            [ 'label' => 'smtp', 'value' => 'smtp' ]
        ];
    }
}
