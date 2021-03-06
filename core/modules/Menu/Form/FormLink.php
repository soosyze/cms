<?php

namespace SoosyzeCore\Menu\Form;

class FormLink extends \Soosyze\Components\Form\FormBuilder
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    private $isRewrite = false;

    /**
     * @var \Soosyze\Components\Router\Router
     */
    private $router;

    private $values = [
        'title_link'  => '',
        'link'        => '',
        'query'       => '',
        'fragment'    => '',
        'icon'        => '',
        'target_link' => false
    ];

    public function __construct(array $attr, $router)
    {
        parent::__construct($attr);
        $this->router = $router;
    }

    public function setValues(array $values)
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function setRewrite($isRewrite)
    {
        $this->isRewrite = $isRewrite
            ? '?'
            : '&';

        return $this;
    }

    public function makeFields()
    {
        $this->group('link-fieldset', 'fieldset', function ($form) {
            $form->legend('link-legend', t('Add a link in the menu'))
                ->group('title_link-group', 'div', function ($form) {
                    $form->label('title_link-label', t('Link title'))
                    ->text('title_link', [
                        'class'       => 'form-control',
                        'maxlength'   => 255,
                        'placeholder' => t('Example: Home'),
                        'required'    => 1,
                        'value'       => $this->values[ 'title_link' ]
                    ]);
                }, self::$attrGrp)
                ->group('link-group', 'div', function ($form) {
                    $form->label('link-label', t('Link'))
                    ->text('link', [
                        'class'       => 'form-control api_route',
                        'data-link'   => $this->router->getRoute('api.route'),
                        'placeholder' => t('Example: node/1 or http://foo.com'),
                        'required'    => 1,
                        'value'       => $this->getLinkValue()
                    ]);
                }, self::$attrGrp)
                ->group('icon-group', 'div', function ($form) {
                    $form->label('icon-label', t('Icon'), [
                        'data-tooltip' => t('Icons are created from the CSS class of FontAwesome'),
                        'for'          => 'icon'
                    ])
                    ->group('icon-flex', 'div', function ($form) {
                        $form->text('icon', [
                            'class'       => 'form-control text_icon',
                            'maxlength'   => 255,
                            'placeholder' => 'fa fa-bars, fa fa-home...',
                            'value'       => $this->values[ 'icon' ],
                        ])
                        ->html('icon-btn', '<button:attr>:content</button>', [
                            ':content'     => '<i class="' . $this->values[ 'icon' ] . '" aria-hidden="true"></i>',
                            'aria-label'   => t('Rendering'),
                            'class'        => 'btn render_icon',
                            'type'         => 'button',
                            'data-tooltip' => t('Rendering')
                        ]);
                    }, [ 'class' => 'form-group-flex' ]);
                }, self::$attrGrp)
                ->group('target_link-group', 'div', function ($form) {
                    $form->checkbox('target_link', [
                        'id'      => 'target_link',
                        'checked' => $this->values[ 'target_link' ]
                    ])
                    ->label('target_link-label', '<span class="ui"></span>' . t('Open in a new window'), [
                        'for' => 'target_link'
                    ]);
                }, self::$attrGrp);
        })
            ->token('token_link_form')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ])
            ->html('cancel', '<button:attr>:content</button>', [
                ':content' => t('Cancel'),
                'class'    => 'btn btn-danger',
                'onclick'  => 'javascript:history.back();',
                'type'     => 'button'
            ]);

        return $this;
    }

    private function getLinkValue()
    {
        $query = empty($this->values[ 'query' ])
            ? ''
            : $this->isRewrite . $this->values[ 'query' ];

        $fragment = empty($this->values[ 'fragment' ])
            ? ''
            : '#' . $this->values[ 'fragment' ];

        return $this->values[ 'link' ] . $query . $fragment;
    }
}
