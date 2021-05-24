<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Form;

use Soosyze\Components\Form\FormGroupBuilder;
use Soosyze\Components\Router\Router;
use Soosyze\Config;
use SoosyzeCore\FileSystem\Services\File;

class FormUser extends \Soosyze\Components\Form\FormBuilder
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    private $values = [
        'username'         => '',
        'email'            => '',
        'picture'          => '',
        'bio'              => '',
        'name'             => '',
        'firstname'        => '',
        'actived'          => '',
        'rgpd'             => '',
        'terms_of_service' => '',
        'roles'            => []
    ];

    /**
     * @var File
     */
    private $file;

    /**
     * @var Config
     */
    private $config;

    public function __construct(array $attr, ?File $file = null, ?Config $config = null)
    {
        parent::__construct($attr);
        $this->file   = $file;
        $this->config = $config;
    }

    public function setValues(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function usernameGroup(FormGroupBuilder &$form): self
    {
        $form->group('username-group', 'div', function ($form) {
            $form->label('username-label', t('User name'))
                ->text('username', [
                    'class'     => 'form-control',
                    'maxlength' => 255,
                    'required'  => 1,
                    'value'     => $this->values[ 'username' ]
            ]);
        }, self::$attrGrp);

        return $this;
    }

    public function emailGroup(FormGroupBuilder &$form): self
    {
        $form->group('email-group', 'div', function ($form) {
            $form->label('email-label', t('E-mail'))
                ->email('email', [
                    'class'       => 'form-control',
                    'maxlength'   => 254,
                    'placeholder' => t('example@mail.com'),
                    'required'    => 1,
                    'value'       => $this->values[ 'email' ]
            ]);
        }, self::$attrGrp);

        return $this;
    }

    public function pictureGroup(FormGroupBuilder &$form): self
    {
        $form->group('picture-group', 'div', function ($form) {
            $form->label('picture-label', t('Picture'), [
                'for'          => 'picture',
                'data-tooltip' => t('200ko maximum. Allowed extensions: jpeg, jpg, png.')
            ]);
            $this->file->inputFile('picture', $form, $this->values[ 'picture' ]);
        }, self::$attrGrp);

        return $this;
    }

    public function bioGroup(FormGroupBuilder &$form): self
    {
        $form->group('bio-group', 'div', function ($form) {
            $form->label('bio-label', t('Biography'), [
                    'data-tooltip' => t('Describe yourself in 255 characters maximum.')
                ])
                ->textarea('bio', $this->values[ 'bio' ], [
                    'class'       => 'form-control',
                    'maxlength'   => 255,
                    'placeholder' => t('Describe yourself in 255 characters maximum.'),
                    'rows'        => 3
            ]);
        }, self::$attrGrp);

        return $this;
    }

    public function nameGroup(FormGroupBuilder &$form): self
    {
        $form->group('name-group', 'div', function ($form) {
            $form->label('name-label', t('Last name'))
                ->text('name', [
                    'class'     => 'form-control',
                    'maxlength' => 255,
                    'value'     => $this->values[ 'name' ]
            ]);
        }, self::$attrGrp);

        return $this;
    }

    public function firstnameGroup(FormGroupBuilder &$form): self
    {
        $form->group('firstname-group', 'div', function ($form) {
            $form->label('firstname-label', t('First name'))
                ->text('firstname', [
                    'class'     => 'form-control',
                    'maxlength' => 255,
                    'value'     => $this->values[ 'firstname' ]
            ]);
        }, self::$attrGrp);

        return $this;
    }

    public function eulaGroup(FormGroupBuilder &$form, Router $router): self
    {
        if (!$this->config) {
            return $this;
        }
        if ($this->config->get('settings.terms_of_service_show', false)) {
            $form->group('terms_of_service-group', 'div', function ($form) {
                $form->checkbox('terms_of_service', [ 'checked' => $this->values[ 'terms_of_service' ] ])
                    ->label('terms_of_service-label', '<span class="ui"></span> ' . t('I have read and accept your terms of service (Required)'), [
                        'for' => 'terms_of_service'
                    ]);
            }, self::$attrGrp)
                ->html('terms_of_service-info', '<p><a :attr>:content</a></p>', [
                    ':content' => t('Read the terms of service'),
                    'href'     => $router->makeRoute($this->config->get('settings.terms_of_service_page')),
                    'target'   => '_blank'
            ]);
        }
        if ($this->config->get('settings.rgpd_show', false)) {
            $form->group('rgpd-group', 'div', function ($form) {
                $form->checkbox('rgpd', [ 'checked' => $this->values[ 'rgpd' ] ])
                    ->label('rgpd-label', '<span class="ui"></span> ' . t('I have read and accept your privacy policy (Required)'), [
                        'for' => 'rgpd'
                    ]);
            }, self::$attrGrp)
                ->html('rgpd-info', '<p><a :attr>:content</a></p>', [
                    ':content' => t('Read the privacy policy'),
                    'href'     => $router->makeRoute($this->config->get('settings.rgpd_page')),
                    'target'   => '_blank'
            ]);
        }

        return $this;
    }

    public function passwordCurrentGroup(FormGroupBuilder &$form): self
    {
        $this->password($form, 'password', t('Password'));

        return $this;
    }

    public function passwordNewGroup(FormGroupBuilder &$form): self
    {
        $this->password(
            $form,
            'password_new',
            t('New Password'),
            $this->config->get('settings.password_show', true)
                ? [ 'onkeyup' => 'passwordPolicy(this)' ]
                : []
        );

        return $this;
    }

    public function passwordConfirmGroup(FormGroupBuilder &$form): self
    {
        $this->password($form, 'password_confirm', t('Confirmation of the new password'));

        return $this;
    }

    public function password(&$form, $id, $label, array $attr = []): void
    {
        $form->group("$id-group", 'div', function ($form) use ($id, $label, $attr) {
            $form->label("$id-label", $label, [ 'for' => $id ])
                ->group("$id-flex", 'div', function ($form) use ($id, $attr) {
                    $form->password($id, [ 'class' => 'form-control' ] + $attr);
                    if ($this->config && $this->config->get('settings.password_show', true)) {
                        $form->html("{$id}_show", '<button:attr>:content</button>', [
                            'class'        => 'btn btn-toogle-password',
                            'onclick'      => "togglePassword(this, '$id')",
                            'type'         => 'button',
                            ':content'     => '<i class="fa fa-eye eyeIcon" aria-hidden="true"></i>',
                            'data-tooltip' => t('Show/Hide password'),
                            'aria-label'   => t('Show/Hide password')
                        ]);
                    }
                }, [ 'class' => 'form-group-flex' ]);
        }, self::$attrGrp);
    }

    public function informationsCreateFieldset(): self
    {
        return $this->group('informations-fieldset', 'fieldset', function ($form) {
            $form->legend('informations-legend', t('Information'));
            $this->usernameGroup($form)
                    ->emailGroup($form);
        });
    }

    public function informationsFieldset(): self
    {
        return $this->group('informations-fieldset', 'fieldset', function ($form) {
            $form->legend('informations-legend', t('Information'));
            $this->usernameGroup($form)
                    ->emailGroup($form)
                    ->passwordCurrentGroup($form);
        });
    }

    public function profilFieldset(): self
    {
        return $this->group('profil-fieldset', 'fieldset', function ($form) {
            $form->legend('profil-legend', t('Profile'));
            $this->pictureGroup($form)
                    ->bioGroup($form)
                    ->nameGroup($form)
                    ->firstnameGroup($form);
        });
    }

    public function passwordFieldset(): self
    {
        return $this->group('password-fieldset', 'fieldset', function ($form) {
            $form->legend('password-legend', t('Password'));
            $this->passwordNewGroup($form)
                    ->passwordConfirmGroup($form)
                    ->passwordPolicy($form);
        });
    }

    public function passwordPolicy(FormGroupBuilder &$form): self
    {
        if (!$this->config || !$this->config->get('settings.password_policy', true)) {
            return $this;
        }

        $content = '';
        foreach ($this->getPasswordPolicies() as $key => $passwordPolicy) {
            [ $lenghtDefault, $pattern, $label ] = $passwordPolicy;

            if (($length = (int) $this->config->get("settings.$key", $lenghtDefault)) < $lenghtDefault) {
                $length = $lenghtDefault;
            }
            $content .= sprintf('<li data-pattern="(%s){%d}">%s : %d</li>', $pattern, $length, t($label), $length);
        }
        $form->html('password_policy', '<ul:attr>:content</ul>', [
            ':content' => $content,
        ]);

        return $this;
    }

    public function activedFieldset(): self
    {
        return $this->group('actived-fieldset', 'fieldset', function ($form) {
            $form->legend('actived-legend', t('Status'))
                    ->group('actived-group', 'div', function ($form) {
                        $form->checkbox('actived', [ 'checked' => $this->values[ 'actived' ] ])
                        ->label('actived-label', '<span class="ui"></span> ' . t('Active'), [
                            'for' => 'actived' ]);
                    }, self::$attrGrp);
        });
    }

    public function rolesFieldset(array $roles): self
    {
        return $roles
            ? $this->group('role-fieldset', 'fieldset', function ($form) use ($roles) {
                $form->legend('role-legend', t('User Roles'));
                foreach ($roles as $role) {
                    $attrRole = [
                        'checked'  => $role[ 'role_id' ] <= 2 || isset($this->values[ 'roles' ][ $role[ 'role_id' ] ]),
                        'disabled' => $role[ 'role_id' ] <= 2,
                        'id'       => "role_{$role[ 'role_id' ]}",
                        'value'    => $role[ 'role_label' ]
                    ];
                    $form->group('role_' . $role[ 'role_id' ] . '-group', 'div', function ($form) use ($role, $attrRole) {
                        $form->checkbox("roles[{$role[ 'role_id' ]}]", $attrRole)
                            ->label(
                                'role_' . $role[ 'role_id' ] . '-label',
                                '<span class="ui"></span>'
                                . '<span class="badge-role" style="background-color: ' . $role[ 'role_color' ] . '">'
                                . '<i class="' . $role[ 'role_icon' ] . '" aria-hidden="true"></i>'
                                . '</span> '
                                . t($role[ 'role_label' ]),
                                [ 'for' => "role_{$role[ 'role_id' ]}" ]
                            );
                    }, self::$attrGrp);
                }
            })
        : $this;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function submitForm(string $label = 'Save', bool $cancel = false): self
    {
        $this->token('token_user_form')
            ->submit('submit', t($label), [ 'class' => 'btn btn-success' ]);
        if ($cancel) {
            $this->html('cancel', '<button:attr>:content</button>', [
                ':content' => t('Cancel'),
                'class'    => 'btn btn-danger',
                'onclick'  => 'javascript:history.back();',
                'type'     => 'button'
            ]);
        }

        return $this;
    }

    private function getPasswordPolicies(): \Generator
    {
        yield 'password_length' => [ 8, '.', 'Minimum length' ];
        yield 'password_upper' => [ 1, '?=.*[A-Z]', 'Number of uppercase characters' ];
        yield 'password_digit' => [ 1, '?=.*\d', 'Number of numeric characters' ];
        yield 'password_special' => [ 1, '?=.*\W', 'Number of special characters' ];
    }
}
