<?php

namespace SoosyzeCore\FileManager\Hook;

final class Config implements \SoosyzeCore\Config\ConfigInterface
{
    const REPLACE_WITH = 1;

    const KEEP_RENAME = 2;

    const KEEP_REFUSE = 3;

    const COPY_ABSOLUTE = 1;

    const COPY_RELATIVE = 2;

    /**
     * @var \Soosyze\App
     */
    private $core;

    public function __construct($core)
    {
        $this->core = $core;
    }

    public function defaultValues()
    {
        return [
            'replace_file'   => self::REPLACE_WITH,
            'copy_link_file' => self::COPY_ABSOLUTE
        ];
    }

    public function menu(array &$menu)
    {
        $menu[ 'filemanager' ] = [
            'title_link' => 'File'
        ];
    }

    public function form(&$form, array $data, $req)
    {
        $form->group('replace_file-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('file-legend', t('File transfer behavior'))
                ->group('replace_file_1-group', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === self::REPLACE_WITH,
                        'id'       => 'replace_file_1',
                        'required' => 1,
                        'value'    => self::REPLACE_WITH
                    ])->label('replace_file-label', t('Replace the file with the new one'), [
                        'for' => 'replace_file_1'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('replace_file_2-group', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === self::KEEP_RENAME,
                        'id'       => 'replace_file_2',
                        'required' => 1,
                        'value'    => self::KEEP_RENAME
                    ])->label('replace_file-label', t('Keep the file by renaming the new'), [
                        'for' => 'replace_file_2'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('replace_file_3-group', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === self::KEEP_REFUSE,
                        'id'       => 'replace_file_3',
                        'required' => 1,
                        'value'    => self::KEEP_REFUSE
                    ])->label('replace_file-label', t('Keep the file by refusing the new one'), [
                        'for' => 'replace_file_3'
                    ]);
                }, [ 'class' => 'form-group' ]);
        })
            ->group('copy_link_file-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('copy_link_file-legend', t('File link copy behavior'))
                ->group('copy_link_file_1-group', 'div', function ($form) use ($data) {
                    $form->radio('copy_link_file', [
                        'checked'  => $data[ 'copy_link_file' ] === self::COPY_ABSOLUTE,
                        'id'       => 'copy_link_file_1',
                        'required' => 1,
                        'value'    => self::COPY_ABSOLUTE
                    ])->label('copy_link_file-label', $this->getLabelCopyLinkFileFull(), [
                        'for' => 'copy_link_file_1'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('copy_link_file_2-group', 'div', function ($form) use ($data) {
                    $form->radio('copy_link_file', [
                        'checked'  => $data[ 'copy_link_file' ] === self::COPY_RELATIVE,
                        'id'       => 'copy_link_file_2',
                        'required' => 1,
                        'value'    => self::COPY_RELATIVE
                    ])->label('copy_link_file-label', $this->getLabelCopyLinkFileBase(), [
                        'for' => 'copy_link_file_2'
                    ]);
                }, [ 'class' => 'form-group' ]);
            });
    }

    public function validator(&$validator)
    {
        $validator->setRules([
            'replace_file'   => 'required|between_numeric:1,3',
            'copy_link_file' => 'required|between_numeric:1,2'
        ]);
    }

    public function before(&$validator, array &$data, $id)
    {
        $data = [
            'replace_file'   => (int) $validator->getInput('replace_file'),
            'copy_link_file' => (int) $validator->getInput('copy_link_file')
        ];
    }

    public function after(&$validator, array $data, $id)
    {
    }

    public function files(array &$inputsFile)
    {
    }

    private function getLabelCopyLinkFileFull()
    {
        return t('Absolute path')
            . " <code>{$this->core->getPath('files_public', 'public/files')}/exemple.jpg</code>";
    }

    private function getLabelCopyLinkFileBase()
    {
        return t('Relative path')
            . " <code>/{$this->core->getSetting('files_public', 'public/files')}/exemple.jpg</code>";
    }
}
