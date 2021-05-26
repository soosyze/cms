<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Form;

class FormMenu extends \Soosyze\Components\Form\FormBuilder
{
    /**
     * @var array
     */
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var array
     */
    private $values = [
        'title'       => '',
        'description' => ''
    ];

    public function setValues(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function makeFields(): self
    {
        $this->group('menu-fieldset', 'fieldset', function ($form) {
            $form->legend('menu-legend', t('Fill in the following fields'))
                ->group('title-group', 'div', function ($form) {
                    $form->label('title-label', t('Menu title'))
                    ->text('title', [
                        'class'       => 'form-control',
                        'maxlength'   => 255,
                        'placeholder' => t('Menu perso'),
                        'required'    => 1,
                        'value'       => $this->values[ 'title' ]
                    ]);
                }, self::$attrGrp)
                ->group('description-group', 'div', function ($form) {
                    $form->label('description-label', t('Description'))
                    ->textarea('description', $this->values[ 'description' ], [
                        'class'     => 'form-control',
                        'maxlength' => 255,
                        'required'  => 1
                    ]);
                }, self::$attrGrp);
        }, self::$attrGrp)
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
}
