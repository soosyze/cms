<?php

declare(strict_types=1);

namespace SoosyzeCore\News\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\FileSystem\Services\File;

class Config implements \SoosyzeCore\Config\ConfigInterface
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var File
     */
    private $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function defaultValues(): array
    {
        return [
            'new_default_icon'  => '',
            'new_default_image' => '',
            'news_pagination'   => 6,
            'new_title'         => 'Articles'
        ];
    }

    public function menu(array &$menu): void
    {
        $menu[ 'news' ] = [
            'title_link' => 'News'
        ];
    }

    public function form(FormBuilder &$form, array $data, ServerRequestInterface $req): void
    {
        $form->group('news_settings-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('news_settings-legend', t('Settings'))
                ->group('new_title-group', 'div', function ($form) use ($data) {
                    $form->label('new_title-label', t('Blog title'))
                    ->text('new_title', [
                        'class' => 'form-control',
                        'value' => $data[ 'new_title' ]
                    ]);
                }, self::$attrGrp)
                ->group('news_pagination-group', 'div', function ($form) use ($data) {
                    $form->label('news_pagination-label', t('Number of articles per page'), [
                        'for'      => 'news_pagination',
                        'required' => 1
                    ])
                    ->group('news_pagination-flex', 'div', function ($form) use ($data) {
                        $form->number('news_pagination', [
                            ':actions' => 1,
                            'class'    => 'form-control',
                            'max'      => 50,
                            'min'      => 1,
                            'required' => 1,
                            'value'    => $data[ 'news_pagination' ]
                        ]);
                    }, [ 'class' => 'form-group-flex' ]);
                }, self::$attrGrp);
        })
            ->group('new_default_image-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('new_default_image-legend', t('Default image'))
                ->group('new_default_image-group', 'div', function ($form) use ($data) {
                    $form->label('new_default_image-label', t('Default image'), [
                        'data-tooltip' => '200ko maximum.',
                        'for'          => 'new_default_image'
                    ]);
                    $this->file->inputFile('new_default_image', $form, $data[ 'new_default_image' ]);
                }, self::$attrGrp)
                ->group('new_default_icon-group', 'div', function ($form) use ($data) {
                    $form->label('new_default_icon-label', t('Default icon'), [
                        'data-tooltip' => t('Icon Font Awesome if there is no default image'),
                        'for'          => 'new_default_icon',
                        'required'     => 1,
                    ])
                    ->group('new_default_icon-flex', 'div', function ($form) use ($data) {
                        $form->text('new_default_icon', [
                            'class'    => 'form-control',
                            'required' => 1,
                            'value'    => $data[ 'new_default_icon' ]
                        ])
                        ->html('new_default_icon-btn', '<button:attr>:content</button>', [
                            ':content'     => '<i class="' . $data[ 'new_default_icon' ] . '" aria-hidden="true"></i>',
                            'aria-label'   => t('Rendering'),
                            'class'        => 'btn render_icon',
                            'type'         => 'button',
                            'data-tooltip' => t('Rendering')
                        ]);
                    }, [ 'class' => 'form-group-flex' ]);
                }, self::$attrGrp);
            });
    }

    public function validator(Validator &$validator): void
    {
        $validator->setRules([
            'new_default_icon'  => 'required|string|fontawesome:solid,brands',
            'new_default_image' => '!required|image|max:200Kb',
            'news_pagination'   => 'required|between_numeric:1,50',
            'new_title'         => 'required|string|max:255'
        ])->setLabels([
            'new_default_icon'  => t('Icone par défaut'),
            'new_default_image' => t('Image par défaut'),
            'news_pagination'   => t('Number of articles per page'),
            'news_title'        => t('Title blog')
        ]);
    }

    public function before(Validator &$validator, array &$data, string $id): void
    {
        $data = [
            'new_default_icon' => $validator->getInput('new_default_icon'),
            'news_pagination'  => (int) $validator->getInput('news_pagination'),
            'new_title'        => $validator->getInput('new_title')
        ];
    }

    public function after(Validator &$validator, array $data, string $id): void
    {
    }

    public function files(array &$inputsFile): void
    {
        $inputsFile = [ 'new_default_image' ];
    }
}
