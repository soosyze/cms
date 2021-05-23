<?php

namespace SoosyzeCore\Node\Hook;

class Block implements \SoosyzeCore\Block\BlockInterface
{
    const DISPLAY_DEFAULT = 'meta-title';

    const NEXT_TEXT_DEFAULT = 'Next :node_type_name';

    const PREVIOUS_TEXT_DEFAULT = 'Previous :node_type_name';

    const TYPE_DEFAULT = 'page';

    /**
     * @var \SoosyzeCore\System\Services\Alias
     */
    private $alias;

    /**
     * @var Node
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
        $blocks[ 'node.next_previous' ] = [
            'hook'    => 'node.next_previous',
            'options' => [
                'display'       => self::DISPLAY_DEFAULT,
                'next_text'     => self::NEXT_TEXT_DEFAULT,
                'previous_text' => self::PREVIOUS_TEXT_DEFAULT,
                'type'          => self::TYPE_DEFAULT
            ],
            'path'    => $this->pathViews,
            'title'   => 'Next/previous button for content types',
            'tpl'     => 'components/block/node-next_previous.php'
        ];
    }

    public function hookBlockNextPrevious($tpl, array $options)
    {
        $node = $this->node->getCurrentNode();
        if ($node === null || $node[ 'type' ] !== $options[ 'type' ]) {
            return;
        }

        $nodeTypeName = $this->getNodeTypeName($options[ 'type' ]);

        return $tpl->addVars([
                'display'        => $options[ 'display' ],
                'next'           => $this->getNextNode($node),
                'next_text'      => $options[ 'next_text' ],
                'node_type_name' => $nodeTypeName,
                'previous'       => $this->getPreviousNode($node),
                'previous_text'  => $options[ 'previous_text' ]
        ]);
    }

    public function hookNodeNextPreviousEditForm(&$form, $data)
    {
        $form->group('node-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('node-legend', t('Settings'))
                ->group('node-group', 'div', function ($form) use ($data) {
                    $form->label('type-label', t('Content type'))
                    ->select('type', $this->getOptionsType(), [
                        ':selected' => $data[ 'options' ][ 'type' ],
                        'class'     => 'form-control'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('display-group', 'div', function ($form) use ($data) {
                    $form->label('display-label', t('Display type'))
                    ->select('display', $this->getOptionsDisplay(), [
                        ':selected' => $data[ 'options' ][ 'display' ],
                        'class'     => 'form-control'
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('previous_text-group', 'div', function ($form) use ($data) {
                    $form->label('previous_text-label', t('Previous text'))
                    ->text('previous_text', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => t($data[ 'options' ][ 'previous_text' ])
                    ]);
                }, [ 'class' => 'form-group' ])
                ->html('previous_text-info', '<p>:content</p>', [
                    ':content' => t('Variables allowed') .
                    ' <code>:node_type_name</code>'
                ])
                ->group('next_text-group', 'div', function ($form) use ($data) {
                    $form->label('next_text-label', t('Next text'))
                    ->text('next_text', [
                        'class'    => 'form-control',
                        'required' => 1,
                        'value'    => t($data[ 'options' ][ 'next_text' ])
                    ]);
                }, [ 'class' => 'form-group' ])
                ->html('next_text-info', '<p>:content</p>', [
                    ':content' => t('Variables allowed') .
                    ' <code>:node_type_name</code>'
            ]);
        });
    }

    public function hookNodeNextPreviousUpdateValidator(&$validator, $id)
    {
        $validator
            ->addRule('display', 'required|inarray:' . $this->getListNameOptionsDisplay())
            ->addRule('next_text', 'required|string|max:255')
            ->addRule('previous_text', 'required|string|max:255')
            ->addRule('type', 'required|inarray:' . implode(',', $this->getListNameOptionsType()));
        $validator
            ->addLabel('display', t('Display type'))
            ->addLabel('next_text', t('Next text'))
            ->addLabel('previous_text', t('Previous text'))
            ->addLabel('type', t('Content type'));
    }

    public function hookNodeNextPreviousUpdateBefore($validator, &$values, $id)
    {
        $values[ 'options' ] = json_encode([
            'display'       => $validator->getInput('display'),
            'next_text'     => $validator->getInput('next_text'),
            'previous_text' => $validator->getInput('previous_text'),
            'type'          => $validator->getInput('type')
        ]);
    }

    private function getNodeTypeName($type)
    {
        $nodeType = $this->query
            ->from('node_type')
            ->where('node_type', '=', $type)
            ->fetch();

        return isset($nodeType[ 'node_type' ])
            ? $nodeType[ 'node_type' ]
            : '';
    }

    private function getNextNode(array $node)
    {
        $next = $this->query->from('node')
            ->where(static function ($query) use ($node) {
                return $query
                    ->where('date_created', '>=', $node[ 'date_created' ])
                    ->where('id', '>', $node[ 'id' ]);
            })
            ->where('type', '=', $node[ 'type' ])
            ->where('id', '!=', $node[ 'id' ])
            ->where('node_status_id', '=', 1)
            ->orderBy('date_created')
            ->fetch();

        if ($next) {
            $linkNext = $this->alias->getAlias('node/' . $next[ 'id' ], 'node/' . $next[ 'id' ]);

            $next[ 'link' ] = $this->router->makeRoute($linkNext);
        }

        return $next;
    }

    private function getPreviousNode(array $node)
    {
        $previous = $this->query->from('node')
            ->where(static function ($query) use ($node) {
                return $query
                    ->where('date_created', '<=', $node[ 'date_created' ])
                    ->where('id', '<', $node[ 'id' ]);
            })
            ->where('type', '=', $node[ 'type' ])
            ->where('id', '!=', $node[ 'id' ])
            ->where('node_status_id', '=', 1)
            ->orderBy('date_created', SORT_DESC)
            ->fetch();

        if ($previous) {
            $linkPrevious = $this->alias->getAlias('node/' . $previous[ 'id' ], 'node/' . $previous[ 'id' ]);

            $previous[ 'link' ] = $this->router->makeRoute($linkPrevious);
        }

        return $previous;
    }

    private function getOptionsType()
    {
        $nodeTypes = $this->query
            ->from('node_type')
            ->orderBy('node_type_name')
            ->fetchAll();

        $out = [];
        foreach ($nodeTypes as $type) {
            $out[] = [
                'label' => t($type[ 'node_type_name' ]),
                'value' => $type[ 'node_type' ]
            ];
        }

        return $out;
    }

    private function getOptionsDisplay()
    {
        return [
            [
                'label' => t('Buttons next/previous'),
                'value' => 'meta'
            ], [
                'label' => t('Content names'),
                'value' => 'title'
            ], [
                'label' => t('Names of contents and next/previous buttons'),
                'value' => 'meta-title'
            ],
        ];
    }

    private function getListNameOptionsType()
    {
        $nodeTypes = $this->query
            ->from('node_type')
            ->fetchAll();

        return $nodeTypes
            ? array_column($nodeTypes, 'node_type')
            : [];
    }

    private function getListNameOptionsDisplay()
    {
        return 'meta,title,meta-title';
    }
}
