<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\QueryBuilder\Services\Query;

final class Config implements \SoosyzeCore\Config\ConfigInterface
{
    private static $attrGrp = [ 'class' => 'form-group' ];

    /**
     * @var array
     */
    private $nodeTypes = [];

    public function __construct(Query $query)
    {
        $this->nodeTypes = $query
            ->from('node_type')
            ->fetchAll();
    }

    public function defaultValues(): array
    {
        return [
            'node_default_url' => '',
            'node_cron'        => ''
        ];
    }

    public function menu(array &$menu): void
    {
        $menu[ 'node' ] = [
            'title_link' => 'Node'
        ];
    }

    public function form(FormBuilder &$form, array $data, ServerRequestInterface $req): void
    {
        $form->group('node_default_url-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('node_default_url-legend', t('Url'))
                    ->group('node_default_url-group', 'div', function ($form) use ($data) {
                        $form->label('node_default_url-label', t('Default url'), [
                            'data-tooltip' => t('Applies to all types of content if the templates below are empty')
                        ])
                        ->text('node_default_url', [
                            'class' => 'form-control',
                            'value' => $data[ 'node_default_url' ]
                        ]);
                    }, self::$attrGrp);
            foreach ($this->nodeTypes as $nodeType) {
                $form->group('node_url_' . $nodeType[ 'node_type' ] . '-group', 'div', function ($form) use ($data, $nodeType) {
                    $form->label('node_url_' . $nodeType[ 'node_type' ] . '-label', t($nodeType[ 'node_type_name' ]))
                            ->text('node_url_' . $nodeType[ 'node_type' ], [
                                'class' => 'form-control',
                                'value' => $data[ 'node_url_' . $nodeType[ 'node_type' ] ] ?? ''
                            ]);
                }, self::$attrGrp);
            }
            $form->html('node_default_url-info', '<p>:content</p>', [
                        ':content' => t('Variables allowed for all') .
                        ' <code>:date_created_year</code>, <code>:date_created_month</code>, <code>:date_created_day</code>, ' .
                        '<code>:node_id</code>, <code>:node_title</code>, <code>:node_type</code>'
                    ]);
        })
                ->group('node_cron-fieldset', 'fieldset', function ($form) use ($data) {
                    $form->legend('node_cron-legend', t('Published'))
                    ->group('node_cron-group', 'div', function ($form) use ($data) {
                        $form->checkbox('node_cron', [ 'checked' => $data[ 'node_cron' ] ])
                        ->label('node_cron-label', '<span class="ui"></span> ' . t('Activate automatic publication of CRON content'), [
                            'for' => 'node_cron'
                        ]);
                    }, self::$attrGrp)
                    ->group('cron_info-group', 'div', function ($form) {
                        $form->html('cron_info', '<a target="_blank" href="https://fr.wikipedia.org/wiki/Cron">:content</a>', [
                            ':content' => t('How to set up the CRON service ?')
                        ]);
                    }, self::$attrGrp);
                });
    }

    public function validator(Validator &$validator): void
    {
        $validator->setRules([
            'node_default_url' => '!required|string|max:255|regex:/^[-:\w\d_\/]+$/',
            'node_cron'        => 'bool'
        ])->setLabels([
            'node_default_url' => t('Default url'),
            'node_cron'        => t('Default url'),
        ])->addMessage('node_default_url', [
            'regex' => [
                'must' => t('The: label field must contain allowed variables, alphanumeric characters, slashes (/), hyphens (-) or underscores (_).')
            ]
        ]);
        foreach ($this->nodeTypes as $nodeType) {
            $validator->addRule('node_url_' . $nodeType[ 'node_type' ], '!required|string|max:255|regex:/^[-:\w\d_\/]+$/')
                ->addLabel('node_url_' . $nodeType[ 'node_type' ], t($nodeType[ 'node_type_name' ]))
                ->addMessage('meta_url', [
                    'regex' => [
                        'must' => t('The: label field must contain allowed variables, alphanumeric characters, slashes (/), hyphens (-) or underscores (_).')
                    ]
                ]);
        }
    }

    public function before(Validator &$validator, array &$data, string $id): void
    {
        $data = [
            'node_default_url' => $validator->getInput('node_default_url'),
            'node_cron'        => (bool) $validator->getInput('node_cron')
        ];

        foreach ($this->nodeTypes as $nodeType) {
            $key = 'node_url_' . $nodeType[ 'node_type' ];

            $data[ $key ] = $validator->getInput($key);
        }
    }

    public function after(Validator &$validator, array $data, string $id): void
    {
    }

    public function files(array &$inputsFile): void
    {
    }
}
