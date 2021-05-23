<?php

namespace SoosyzeCore\News\Hook;

use Soosyze\Components\Form\FormBuilder;

class Block implements \SoosyzeCore\Block\BlockInterface
{
    /**
     * @var \SoosyzeCore\System\Services\Alias
     */
    private $alias;

    /**
     * @var \SoosyzeCore\Node\Services\Node
     */
    private $node;

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    /**
     * @var \Soosyze\Components\Router\Router
     */
    private $router;

    public function __construct($alias, $node, $query, $router)
    {
        $this->alias  = $alias;
        $this->node   = $node;
        $this->query  = $query;
        $this->router = $router;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function hookBlockCreateFormData(array &$blocks)
    {
        $blocks[ 'news.archive' ]        = [
            'hook'    => 'news.archive',
            'options' => [ 'expand' => false ],
            'path'    => $this->pathViews,
            'title'   => 'Archives',
            'tpl'     => 'components/block/news-archive.php'
        ];
        $blocks[ 'news.archive.select' ] = [
            'hook'  => 'news.archive.select',
            'path'  => $this->pathViews,
            'title' => 'Archives',
            'tpl'   => 'components/block/news-archive_select.php'
        ];
        $blocks[ 'news.last' ]           = [
            'hook'    => 'news.last',
            'options' => [ 'limit' => 3, 'offset' => 0, 'more' => true ],
            'path'    => $this->pathViews,
            'title'   => 'Last News',
            'tpl'     => 'components/block/news-last.php'
        ];
    }

    public function hookBlockNewsArchiveSelect($tpl, array $options)
    {
        $data = $this->query
            ->from('node')
            ->where('node_status_id', '==', 1)
            ->where('type', '=', 'article')
            ->fetchAll();

        $query      = $this->router->parseQueryFromRequest();
        $paramMonth = $this->router->parseParam('news/:year/:month:id', $query, [
            ':year'  => '\d{4}',
            ':month' => '0[1-9]|1[0-2]',
            ':id'    => '(/page/[1-9]\d*)?'
        ]);
        $paramYear  = $this->router->parseParam('news/:year:id', $query, [
            ':year' => '\d{4}',
            ':id'   => '(/page/[1-9]\d*)?'
        ]);

        $optionsSelect[] = [
            'label' => t('-- Select --'),
            'value' => $this->router->getRoute('news.index')
        ];
        foreach ($data as $value) {
            $year  = date('Y', $value[ 'date_created' ]);
            $month = date('m', $value[ 'date_created' ]);

            if (!isset($optionsSelect[ $year ])) {
                $optionsSelect[ $year ] = [
                    'label' => $year,
                    'value' => [
                        $year => [
                            'label' => t('All :year', [ ':year' => $year ]),
                            'value' => $this->router->getRoute('news.years', [
                                ':year' => $year,
                                ':id'   => ''
                            ])
                        ]
                    ]
                ];
            }

            if (isset($optionsSelect[ $year ][ 'value' ][ $month ])) {
                continue;
            }

            $optionsSelect[ $year ][ 'value' ][ $month ] = [
                'label' => strftime('%b', $value[ 'date_created' ]),
                'value' => $this->router->getRoute('news.month', [
                    ':year'  => $year,
                    ':month' => $month,
                    ':id'    => ''
                ])
            ];
        }

        $selected = '#';
        if (!empty($paramMonth)) {
            $selected = $this->router->getRoute('news.month', [
                ':year'  => $paramMonth[ 0 ],
                ':month' => $paramMonth[ 1 ],
                ':id'    => ''
            ]);
        } elseif (!empty($paramYear)) {
            $selected = $this->router->getRoute('news.years', [
                ':year' => $paramYear[ 0 ],
                ':id'   => ''
            ]);
        }

        $form = (new FormBuilder([ 'method' => 'get', 'action' => '#' ]))
            ->select('archives', $optionsSelect, [
            ':selected' => $selected,
            'class'     => 'form-control',
            'onchange'  => 'this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);'
        ]);

        return $tpl->addVar('form', $form);
    }

    public function hookBlockNewsArchive($tpl, array $options)
    {
        $data = $this->query
            ->from('node')
            ->where('node_status_id', '==', 1)
            ->where('type', '=', 'article')
            ->fetchAll();

        $query = $this->router->parseQueryFromRequest();
        $param = $this->router->parseParam('news/:year:id', $query, [
            ':year' => '\d{4}',
            ':id'   => '(/page/[1-9]\d*)?'
        ]);

        $output = [];
        foreach ($data as $value) {
            $year  = date('Y', $value[ 'date_created' ]);
            $month = date('m', $value[ 'date_created' ]);

            if (isset($output[ $year ])) {
                ++$output[ $year ][ 'number' ];
            } else {
                $output[ $year ] = [
                    'link'   => $this->router->getRoute('news.years', [
                        ':year' => $year,
                        ':id'   => ''
                    ]),
                    'number' => 1,
                    'year'   => $year
                ];
            }

            if (empty($options[ 'expand' ]) && (empty($param[ 0 ]) || $param[ 0 ] != $year)) {
                continue;
            }

            if (isset($output[ $year ][ 'months' ][ $month ])) {
                ++$output[ $year ][ 'months' ][ $month ][ 'number' ];
            } else {
                $output[ $year ][ 'months' ][ $month ] = [
                    'link'   => $this->router->getRoute('news.month', [
                        ':year'  => $year,
                        ':month' => $month,
                        ':id'    => ''
                    ]),
                    'month'  => strftime('%b', $value[ 'date_created' ]),
                    'number' => 1,
                    'year'   => $year
                ];
            }
        }
        $output[ 'all' ] = [
            'number' => count($data),
            'year'   => t('All'),
            'link'   => $this->router->getRoute('news.index')
        ];

        return $tpl->addVar('years', $output);
    }

    public function hookBlockNewsArchiveEditForm(&$form, array $data)
    {
        $form->group('new-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('new-legend', t('News setting'))
                ->group('limit-group', 'div', function ($form) use ($data) {
                    $form->checkbox('expand', [
                        'checked' => $data[ 'options' ][ 'expand' ],
                        'class'   => 'form-control'
                    ])
                    ->label('limit-label', '<span class="ui"></span>' . t('Expand archives per month'), [
                        'for' => 'expand'
                    ]);
                }, [ 'class' => 'form-group' ]);
        });
    }

    public function hookNewsBlockArchiveUpdateValidator(&$validator)
    {
        $validator
            ->addRule('expand', 'bool')
            ->addLabel('expand', t('Expand archives per month'));
    }

    public function hookNewsArchiveUpdateBefore($validator, &$values, $id)
    {
        $values[ 'options' ] = json_encode([
            'expand' => (bool) $validator->getInput('expand')
        ]);
    }

    public function hookBlockNewsLast($tpl, array $options)
    {
        $news = $this->query
            ->from('node')
            ->where('node_status_id', '=', 1)
            ->where('type', '=', 'article')
            ->orderBy('sticky', SORT_DESC)
            ->orderBy('date_created', SORT_DESC)
            ->limit($options[ 'limit' ] + 1, $options[ 'offset' ])
            ->fetchAll();

        $isMore = false;
        foreach ($news as $key => &$value) {
            if ($key > $options[ 'limit' ] - 1) {
                $isMore = $options[ 'more' ];
                unset($news[ $key ]);

                continue;
            }
            $value[ 'field' ] = $this->node->makeFieldsById('article', $value[ 'entity_id' ]);

            $alias = $this->alias->getAlias('node/' . $value[ 'id' ], 'node/' . $value[ 'id' ]);

            $value[ 'link_view' ] = $this->router->makeRoute($alias);
        }
        unset($value);

        return $tpl->addVars([
                'is_link_more' => $isMore,
                'limit'        => $options[ 'limit' ],
                'link_more'    => $this->router->getRoute('news.index'),
                'news'         => $news,
                'offset'       => $options[ 'offset' ]
        ]);
    }

    public function hookBlockNewsLastEditForm(&$form, array $data)
    {
        $form->group('new-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('new-legend', t('News setting'))
                ->group('limit-group', 'div', function ($form) use ($data) {
                    $options = [
                        [ 'label' => 1, 'value' => 1 ],
                        [ 'label' => 2, 'value' => 2 ],
                        [ 'label' => 3, 'value' => 3 ],
                        [ 'label' => 4, 'value' => 4 ]
                    ];

                    $form->label('limit-label', t('Number of news to display'))
                    ->select('limit', $options, [
                        ':selected' => $data[ 'options' ][ 'limit' ],
                        'class'     => 'form-control',
                        'max'       => 4,
                        'min'       => 1
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('offset-group', 'div', function ($form) use ($data) {
                    $form->label('offset-label', t('Offset'))
                    ->number('offset', [
                        'class' => 'form-control',
                        'min'   => 0,
                        'value' => $data[ 'options' ][ 'offset' ]
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('more-group', 'div', function ($form) use ($data) {
                    $form->checkbox('more', [
                        'checked' => $data[ 'options' ][ 'more' ]
                    ])
                    ->label('more-label', '<i class="ui" aria-hidden="true"></i> ' . t('Add a "more" link at the bottom of the screen if there is more content'), [
                        'for' => 'more'
                    ]);
                }, [ 'class' => 'form-group' ]);
        });
    }

    public function hookBlockNewsLastUpdateValidator(&$validator, $id)
    {
        $validator
            ->addRule('limit', 'required|inarray:1,2,3,4')
            ->addRule('offset', 'required|numeric|min_numeric:0')
            ->addRule('more', 'bool');
        $validator
            ->addLabel('limit', t('Number of news to display'))
            ->addLabel('offset', t('Offset'))
            ->addLabel('more', t('Add a "more" link at the bottom of the screen if there is more content'));
    }

    public function hookNewsLastUpdateBefore($validator, array &$values, $id)
    {
        $values[ 'options' ] = json_encode([
            'limit'  => (int) $validator->getInput('limit'),
            'more'   => (bool) $validator->getInput('more'),
            'offset' => (int) $validator->getInput('offset')
        ]);
    }
}
