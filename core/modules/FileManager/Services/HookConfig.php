<?php

namespace SoosyzeCore\FileManager\Services;

class HookConfig
{
    public function menu(&$menu)
    {
        $menu[ 'filemanager' ] = [
            'title_link' => 'FileManager'
        ];
    }

    public function form(&$form, $data)
    {
        $form->group('file-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('file-legend', t('Behavior of file transfers'))
                ->group('replace_file_1', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === 1,
                        'id'       => 'replace_file_1',
                        'required' => 1,
                        'value'    => 1
                    ])->label('visibility_pages-label', t('Replace the file with the new one'), [
                        'for' => 'replace_file_1'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('replace_file_2', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === 2,
                        'id'       => 'replace_file_2',
                        'required' => 1,
                        'value'    => 2
                    ])->label('visibility_pages-label', t('Keep the file by renaming the new'), [
                        'for' => 'replace_file_2'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('replace_file_3', 'div', function ($form) use ($data) {
                    $form->radio('replace_file', [
                        'checked'  => $data[ 'replace_file' ] === 3,
                        'id'       => 'replace_file_3',
                        'required' => 1,
                        'value'    => 3
                    ])->label('visibility_pages-label', t('Keep the file by refusing the new one'), [
                        'for' => 'replace_file_3'
                    ]);
                }, [ 'class' => 'form-group' ]);
        });
    }

    public function validator(&$validator)
    {
        $validator->setRules([
            'replace_file' => 'required|int|between:1,3'
        ]);
    }

    public function before(&$validator, &$data)
    {
        $data = [
            'replace_file' => (int) $validator->getInput('replace_file')
        ];
    }
}
