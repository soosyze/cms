<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Form;

use Soosyze\Components\Util\Util;
use SoosyzeCore\FileManager\Services\FileManager;

class FormPermission extends \Soosyze\Components\Form\FormBuilder
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    private static $attrGrpFlex = [ 'class' => 'form-group-flex' ];

    private $values = [
        'folder_show'         => '/',
        'folder_show_sub'     => true,
        'profil_weight'       => 1,
        'roles'               => [],
        'folder_store'        => true,
        'folder_update'       => false,
        'folder_delete'       => false,
        'folder_size'         => 10,
        'file_store'          => true,
        'file_update'         => true,
        'file_delete'         => false,
        'file_download'       => true,
        'file_clipboard'      => true,
        'file_copy'           => true,
        'file_size'           => 1,
        'file_extensions_all' => false,
        'file_extensions'     => []
    ];

    private $roles = [];

    public function setValues(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_merge($this->roles, $roles);

        return $this;
    }

    public function makeFields(): self
    {
        $this->group('folder_show-fieldset', 'fieldset', function ($form) {
            $form->legend('folder_show-legend', t('Directory'))
                ->group('folder_show-group', 'div', function ($form) {
                    $form->label('folder_show-label', t('Directory path'), [
                        'for'      => 'folder_show',
                        'required' => 1
                    ])
                    ->group('profil_weight-flex', 'div', function ($form) {
                        $form->html('cancel', '<span:attr>:content</span>', [
                            ':content' => t('<root>')
                        ])
                        ->text('folder_show', [
                            'class'    => 'form-control',
                            'required' => 1,
                            'value'    => $this->values[ 'folder_show' ]
                        ]);
                    }, self::$attrGrpFlex)
                    ->html('cancel', '<p>:content</p>', [
                        ':content' => t('Variables allowed') . ' <code>:user_id</code>'
                    ]);
                }, self::$attrGrp)
                ->group('folder_show_sub-group', 'div', function ($form) {
                    $form->checkbox('folder_show_sub', [ 'checked' => $this->values[ 'folder_show_sub' ] ])
                    ->label('folder_show_sub-label', '<i class="ui"></i><i class="fa fa-sitemap" aria-hidden="true"></i> ' . t('Apply permission to subdirectories'), [
                        'for' => 'folder_show_sub'
                    ]);
                }, self::$attrGrp)
                ->group('profil_weight-group', 'div', function ($form) {
                    $form->label('profil_weight-label', t('Weight'), [
                        'for'      => 'profil_weight',
                        'required' => 1
                    ])
                    ->group('profil_weight-flex', 'div', function ($form) {
                        $form->number('profil_weight', [
                            ':actions' => 1,
                            'class'    => 'form-control',
                            'max'      => 50,
                            'min'      => 1,
                            'required' => 1,
                            'value'    => $this->values[ 'profil_weight' ]
                        ]);
                    }, self::$attrGrpFlex);
                }, self::$attrGrp);
        })
            ->group('roles-fieldset', 'fieldset', function ($form) {
                $form->legend('roles-legend', t('User Roles'));
                foreach ($this->roles as $role) {
                    $form->group('roles-' . $role[ 'role_id' ] . '-group', 'div', function ($form) use ($role) {
                        $form->checkbox("roles[{$role[ 'role_id' ]}]", [
                            'checked' => in_array($role[ 'role_id' ], $this->values[ 'roles' ]),
                            'id'      => "role-{$role[ 'role_id' ]}",
                            'value'   => $role[ 'role_id' ]
                        ])
                        ->label(
                            'role-' . $role[ 'role_id' ] . '-label',
                            '<span class="ui"></span>'
                            . '<span class="badge-role" style="background-color: ' . $role[ 'role_color' ] . '">'
                            . '<i class="' . $role[ 'role_icon' ] . '" aria-hidden="true"></i>'
                            . '</span> '
                            . t($role[ 'role_label' ]),
                            [ 'for' => "role-{$role[ 'role_id' ]}" ]
                        );
                    }, self::$attrGrp);
                }
            })
            ->group('folder-fieldset', 'fieldset', function ($form) {
                $form->legend('folder-legend', t('Directory permissions'))
                ->group('folder_store-group', 'div', function ($form) {
                    $form->checkbox('folder_store', [ 'checked' => $this->values[ 'folder_store' ] ])
                    ->label('folder_store-label', '<i class="ui"></i><i class="fa fa-plus" aria-hidden="true"></i> ' . t('Create'), [
                        'for' => 'folder_store'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('folder_update-group', 'div', function ($form) {
                    $form->checkbox('folder_update', [ 'checked' => $this->values[ 'folder_update' ] ])
                    ->label('folder_update-label', '<i class="ui"></i><i class="fa fa-edit" aria-hidden="true"></i> ' . t('Edit'), [
                        'for' => 'folder_update'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('folder_delete-group', 'div', function ($form) {
                    $form->checkbox('folder_delete', [ 'checked' => $this->values[ 'folder_delete' ] ])
                    ->label('folder_delete-label', '<i class="ui"></i><i class="fa fa-times" aria-hidden="true"></i> ' . t('Delete'), [
                        'for' => 'folder_delete'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('folder_size-group', 'div', function ($form) {
                    $form->label('folder_size-label', t('Maximum data quota per directory'), [
                        'for'          => 'folder_size',
                        'data-tooltip' => t('Leave at 0 to apply an unlimited data quota per directory')
                    ])
                    ->group('folder_size-flex', 'div', function ($form) {
                        $form->number('folder_size', [
                            'class' => 'form-control',
                            'min'   => 0,
                            'value' => $this->values[ 'folder_size' ]
                        ])->html('folder_size-unit', '<span:attr>:content</span>', [
                            ':content'     => 'Mo',
                            'data-tooltip' => 'Mega octet'
                        ]);
                    }, self::$attrGrpFlex);
                }, [ 'class' => 'form-group col-sm-12' ]);
            })
            ->group('file-fieldset', 'fieldset', function ($form) {
                $form->legend('file-legend', t('Files permissions'))
                ->group('file_store-group', 'div', function ($form) {
                    $form->checkbox('file_store', [ 'checked' => $this->values[ 'file_store' ] ])
                    ->label('file_store-label', '<i class="ui"></i><i class="fa fa-plus" aria-hidden="true"></i> ' . t('Create'), [
                        'for' => 'file_store'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_update-group', 'div', function ($form) {
                    $form->checkbox('file_update', [ 'checked' => $this->values[ 'file_update' ] ])
                    ->label('file_update-label', '<i class="ui"></i><i class="fa fa-edit" aria-hidden="true"></i> ' . t('Edit'), [
                        'for' => 'file_update'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_delete-group', 'div', function ($form) {
                    $form->checkbox('file_delete', [ 'checked' => $this->values[ 'file_delete' ] ])
                    ->label('file_delete-label', '<i class="ui"></i><i class="fa fa-times" aria-hidden="true"></i> ' . t('Delete'), [
                        'for' => 'file_delete'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_download-group', 'div', function ($form) {
                    $form->checkbox('file_download', [ 'checked' => $this->values[ 'file_download' ] ])
                    ->label('file_download-label', '<i class="ui"></i><i class="fa fa-download" aria-hidden="true"></i> ' . t('Download'), [
                        'for' => 'file_download'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_clipboard-group', 'div', function ($form) {
                    $form->checkbox('file_clipboard', [ 'checked' => $this->values[ 'file_clipboard' ] ])
                    ->label('file_clipboard-label', '<i class="ui"></i><i class="fa fa-copy" aria-hidden="true"></i> ' . t('Copy link'), [
                        'for' => 'file_clipboard'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_copy-group', 'div', function ($form) {
                    $form->checkbox('file_copy', [ 'checked' => $this->values[ 'file_copy' ] ])
                    ->label('file_copy-label', '<i class="ui"></i><i class="fa fa-copy" aria-hidden="true"></i> ' . t('Deplace or copy'), [
                        'for' => 'file_copy'
                    ]);
                }, [ 'class' => 'form-group col-sm-4' ])
                ->group('file_size-group', 'div', function ($form) {
                    $form->label('file_size-label', t('Size limit per file unload'), [
                        'data-tooltip' => t('Leave at 0 to apply the file upload size limit authorized by your server'),
                        'for'          => 'file_size'
                    ])
                    ->group('file_size-flex', 'div', function ($form) {
                        $form->number('file_size', [
                            'class' => 'form-control',
                            'min'   => 0,
                            'value' => $this->values[ 'file_size' ]
                        ])->html('file_size-unit', '<span:attr>:content</span>', [
                            ':content'     => 'Mo',
                            'data-tooltip' => 'Mega octet'
                        ]);
                    }, self::$attrGrpFlex)
                    ->html('cancel', '<p>:content</p>', [
                        ':content' => t('Maximum size per file upload allowed by your server: :size', [
                            ':size' => Util::strFileSizeFormatted(Util::getOctetUploadLimit())
                        ])
                    ]);
                }, [ 'class' => 'form-group col-sm-12' ]);
            })
            ->group('extensions-fieldset', 'fieldset', function ($form) {
                $form->legend('extensions-legend', t('File extensions'))
                ->group('file_extensions_all-group', 'div', function ($form) {
                    $form->checkbox('file_extensions_all', [
                        'checked' => $this->values[ 'file_extensions_all' ]
                    ])
                    ->label('all_extensions-label', '<i class="ui"></i>' . t('All extensions'), [
                        'for' => 'file_extensions_all'
                    ]);
                }, [ 'class' => 'form-group col-md-12' ]);
                foreach (FileManager::getExtAllowed() as $extension) {
                    $form->group("$extension-group", 'div', function ($form) use ($extension) {
                        $form->checkbox("file_extensions[$extension]", [
                            'class'   => 'ext',
                            'checked' => in_array($extension, $this->values[ 'file_extensions' ]),
                            'value'   => $extension
                        ])
                        ->label("$extension-label", '<i class="ui"></i>' . $extension, [
                            'for' => "file_extensions[$extension]"
                        ]);
                    }, [ 'class' => 'form-group col-sm-3' ]);
                }
            })
            ->token('token_file_permission')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ])
            ->html('cancel', '<button:attr>:content</button>', [
                ':content' => t('Cancel'),
                'class'    => 'btn btn-danger',
                'onclick'  => 'javascript:history.back();',
                'type'     => 'button'
        ]);

        return $this;
    }
}
