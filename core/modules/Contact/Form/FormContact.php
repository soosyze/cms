<?php

declare(strict_types=1);

namespace SoosyzeCore\Contact\Form;

class FormContact extends \Soosyze\Components\Form\FormBuilder
{
    /**
     * @var array
     */
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var array
     */
    private $values = [
        'name'    => '',
        'email'   => '',
        'object'  => '',
        'message' => '',
    ];

    public function setValues(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function makeFields(): self
    {
        $this->nameGroup()
            ->emailGroup()
            ->objectGroup()
            ->messageGroup()
            ->copyGroup()
            ->token('token_contact')
            ->submit('submit', t('Send the message'), [ 'class' => 'btn btn-success' ]);

        return $this;
    }

    private function nameGroup(): self
    {
        return $this->group('name-group', 'div', function ($form) {
            $form->label('name-label', t('Name'))
                    ->text('name', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => $this->values[ 'name' ]
                ]);
        }, self::$attrGrp);
    }

    private function emailGroup(): self
    {
        return $this->group('email-group', 'div', function ($form) {
            $form->label('email-label', t('E-mail'))
                    ->email('email', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => $this->values[ 'email' ]
                ]);
        }, self::$attrGrp);
    }

    private function objectGroup(): self
    {
        return $this->group('object-group', 'div', function ($form) {
            $form->label('object-label', t('Object'))
                    ->text('object', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => $this->values[ 'object' ]
                ]);
        }, self::$attrGrp);
    }

    private function messageGroup(): self
    {
        return $this->group('message-group', 'div', function ($form) {
            $form->label('message-label', t('Message'))
                    ->textarea('message', $this->values[ 'message' ], [
                        'class'    => 'form-control',
                        'required' => 1,
                        'rows'     => 8
                ]);
        }, self::$attrGrp);
    }

    private function copyGroup(): self
    {
        return $this->group('copy-group', 'div', function ($form) {
            $form->checkbox('copy')
                    ->label('copy-label', '<i class="ui" aria-hidden="true"></i> ' . t('Send me a copy of the mail'), [
                        'for' => 'copy'
                ]);
        }, self::$attrGrp);
    }
}
