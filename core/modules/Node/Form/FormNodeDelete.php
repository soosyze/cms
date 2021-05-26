<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Form;

use Soosyze\Components\Router\Router;

class FormNodeDelete extends \Soosyze\Components\Form\FormBuilder
{
    /**
     * @var array
     */
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    private $values = [
        'current_path' => '',
        'files'        => 1,
        'path'         => ''
    ];

    /**
     * @var array|null
     */
    private $useInPath = null;

    public function __construct(array $attr, Router $router)
    {
        parent::__construct($attr);
        $this->router = $router;
    }

    public function setValues(array $values, ?array $useInPath): self
    {
        $this->values    = array_merge($this->values, $values);
        $this->useInPath = $useInPath;

        return $this;
    }

    public function makeFields(): self
    {
        return $this->group('node-fieldset', 'fieldset', function ($form) {
            $form->legend('node-legend', t('Node deletion'))
                    ->group('info-group', 'div', function ($form) {
                        $form->html('info', '<p:attr>:content</p>', [
                            ':content' => t('Warning ! The deletion of the node is final.')
                        ]);

                        if ($this->useInPath) {
                            $form->html('info_path', '<p:attr>:content</p>', [
                                ':content' => t('This content is used in the configuration as') . ' : <b>' . t($this->useInPath[ 'title' ]) . '</b>'
                            ]);
                        }
                    }, [ 'class' => 'alert alert-warning' ]);

            if ($this->useInPath) {
                $form->group('path-group', 'div', function ($form) {
                    $form->label('path-label', t('New path for') . ' ' . t($this->useInPath[ 'title' ]), [
                                'for'      => 'path',
                                'required' => !empty($this->useInPath[ 'required' ])
                            ])
                            ->group('path-flex', 'div', function ($form) {
                                $form->html('base_path', '<span:attr>:content</span>', [
                                    ':content' => $this->router->makeRoute(''),
                                    'id'       => ''
                                ])
                                ->text('path', [
                                    'class'        => 'form-control api_route',
                                    'data-exclude' => $this->values[ 'current_path' ],
                                    'data-link'    => $this->router->getRoute('api.route'),
                                    'maxlength'    => 512,
                                    'placeholder'  => t('Example: node/1'),
                                    'required'     => !empty($this->useInPath[ 'required' ]),
                                    'value'        => $this->values[ 'path' ]
                                ]);
                            }, [ 'class' => 'form-group-flex api_route' ])
                            ->html('result', '<ul:attr></ul>', [
                                'class'       => 'api_route-list hidden',
                                'data-target' => '#path'
                            ]);
                }, self::$attrGrp);
            }

            $form->group('files-group', 'div', function ($form) {
                $form->checkbox('files', [
                            'checked' => $this->values[ 'files' ],
                            'id'      => 'files'
                        ])
                        ->label(
                            'files-label',
                            '<span class="ui"></span> ' . t('Delete files with their contents'),
                            [ 'for' => 'files' ]
                        );
            }, self::$attrGrp);
        })
                ->token('token_node_remove')
                ->submit('submit', t('Delete'), [ 'class' => 'btn btn-danger' ]);
    }
}
