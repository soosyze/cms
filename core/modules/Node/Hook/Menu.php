<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Hook;

use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\QueryBuilder\Services\Schema;
use SoosyzeCore\System\Services\Alias;

class Menu
{
    const MENU_DEFAULT = 'menu-main';

    /**
     * @var Alias
     */
    private $alias;

    /**
     * Si le module menu existe.
     *
     * @var bool
     */
    private $isMenu;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Alias $alias, Query $query, Schema $schema)
    {
        $this->alias  = $alias;
        $this->query  = $query;
        $this->schema = $schema;

        $this->isMenu = $this->schema->hasTable('menu');
    }

    public function hookNodeFieldsetSubmenu(array &$menu): void
    {
        if (!$this->isMenu) {
            return;
        }

        $menu[] = [
            'class'      => '',
            'link'       => '#menu-fieldset',
            'title_link' => t('Menu')
        ];
    }

    public function hookCreateFormData(array &$data): void
    {
        if (!$this->isMenu) {
            return;
        }

        $data[ 'active' ]     = '';
        $data[ 'menu_title' ] = self::MENU_DEFAULT;
        $data[ 'title_link' ] = '';
    }

    public function hookEditFormData(array &$data, int $idNode): void
    {
        if (!$this->isMenu) {
            return;
        }

        $data[ 'active' ]     = '';
        $data[ 'menu_title' ] = self::MENU_DEFAULT;
        $data[ 'title_link' ] = '';

        $link = $this->query
            ->from('node_menu_link')
            ->leftJoin('menu_link', 'menu_link_id', '=', 'menu_link.id')
            ->where('node_id', '==', $idNode)
            ->fetch();

        if ($link) {
            $data[ 'active' ]     = (bool) $link[ 'menu_link_id' ];
            $data[ 'menu_title' ] = $link[ 'menu' ];
            $data[ 'title_link' ] = $link[ 'title_link' ];
        }
    }

    public function hookCreateForm(FormBuilder $form, array $data): void
    {
        if (!$this->isMenu) {
            return;
        }

        $form->before('actions-group', function ($form) use ($data) {
            $form->group('menu-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('menu-legend', t('Menu'))
                    ->group('active-group', 'div', function ($form) use ($data) {
                        $form->checkbox('active', [
                            'checked'      => $data[ 'active' ],
                            'data-dismiss' => 'toogle',
                            'data-target'  => '#menu_toogle',
                        ])
                        ->label('active-label', '<span class="ui"></span> ' . t('Add a link in the menu'), [
                            'for' => 'active'
                        ]);
                    }, [ 'class' => 'form-group' ])
                    ->group('menu-group', 'div', function ($form) use ($data) {
                        $form->group('menu_title-group', 'div', function ($form) use ($data) {
                            $form->label('menu_title-label', t('Menu title'))
                            ->select('menu_title', $this->getOptions(), [
                                ':selected' => $data[ 'menu_title' ],
                                'class'     => 'form-control'
                            ]);
                        }, [ 'class' => 'form-group' ])
                        ->group('title_link-group', 'div', function ($form) use ($data) {
                            $form->label('title_link-label', t('Link title'))
                            ->text('title_link', [
                                'class'       => 'form-control',
                                'placeholder' => t('Example: Home'),
                                'value'       => $data[ 'title_link' ]
                            ]);
                        }, [ 'class' => 'form-group' ]);
                    }, [
                        'id'    => 'menu_toogle',
                        'class' => $data[ 'active' ]
                            ? ''
                            : 'hidden'
                ]);
            }, [
                'class' => 'tab-pane fade',
                'id'    => 'menu-fieldset'
            ]);
        });
    }

    public function hookStoreValidator(Validator $validator): void
    {
        if (!$this->isMenu || !$validator->hasInput('active')) {
            return;
        }

        $validator
            ->addRule('active', 'bool')
            ->addRule('menu_title', 'required|inarray:' . $this->getListNamesMenu())
            ->addRule('title_link', 'required|string|max:255|to_striptags')
            ->addLabel('menu_title', t('Menu title'))
            ->addLabel('title_link', t('Link title'));
    }

    public function hookStoreValid(Validator $validator): void
    {
        if (!$this->isMenu || !$validator->hasInput('active')) {
            return;
        }

        $id   = $this->schema->getIncrement('node');
        $link = $this->alias->getAlias("node/$id", "node/$id");

        $this->query->insertInto('menu_link', [
                'key', 'title_link', 'link', 'link_router', 'menu', 'weight', 'parent',
                'active'
            ])
            ->values([
                'node.show',
                $validator->getInput('title_link'),
                $link,
                "node/$id",
                $validator->getInput('menu_title'),
                1,
                -1,
                $validator->getInput('node_status_id') == 1,
            ])
            ->execute();

        $linkId = $this->schema->getIncrement('menu_link');

        $this->query->insertInto('node_menu_link', [ 'node_id', 'menu_link_id' ])
            ->values([ $id, $linkId ])
            ->execute();
    }

    public function hookUpdateValid(Validator $validator, int $id): void
    {
        if (!$this->isMenu) {
            return;
        }

        $nodeMenuLink = $this->query->from('node_menu_link')
            ->where('node_id', '==', $id)
            ->fetch();

        $link = $this->alias->getAlias("node/$id", "node/$id");

        if ($validator->hasInput('active') && $nodeMenuLink) {
            $this->query->update('menu_link', [
                    'active'     => $validator->getInput('node_status_id') == 1,
                    'link'       => $link,
                    'menu'       => $validator->getInput('menu_title'),
                    'title_link' => $validator->getInput('title_link')
                ])
                ->where('id', '=', $nodeMenuLink[ 'menu_link_id' ])
                ->execute();
        } elseif ($validator->hasInput('active') && !$nodeMenuLink) {
            $this->query->insertInto('menu_link', [
                    'key', 'title_link', 'link', 'link_router', 'menu', 'weight',
                    'parent', 'active'
                ])
                ->values([
                    'node.show',
                    $validator->getInput('title_link'),
                    $link,
                    "node/$id",
                    $validator->getInput('menu_title'),
                    1,
                    -1,
                    $validator->getInput('node_status_id') == 1
                ])
                ->execute();

            $linkId = $this->schema->getIncrement('menu_link');

            $this->query->insertInto('node_menu_link', [ 'node_id', 'menu_link_id' ])
                ->values([ (int) $id, (int) $linkId ])
                ->execute();
        } elseif (!$validator->hasInput('active') && $nodeMenuLink) {
            $this->query->from('node_menu_link')
                ->where('node_id', '==', $id)
                ->delete()
                ->execute();

            $this->query->from('menu_link')
                ->where('id', '==', $nodeMenuLink[ 'menu_link_id' ])
                ->delete()
                ->execute();
        }
    }

    public function hookDeleteValid(Validator $validator, int $item): void
    {
        if (!$this->isMenu) {
            return;
        }

        $nodeMenuLink = $this->query->from('node_menu_link')
            ->where('node_id', '==', $item)
            ->fetch();

        if ($nodeMenuLink) {
            $this->query->from('node_menu_link')
                ->where('node_id', '==', $item)
                ->delete()
                ->execute();

            $this->query->from('menu_link')
                ->where('id', '==', $nodeMenuLink[ 'menu_link_id' ])
                ->delete()
                ->execute();
        }
    }

    public function hookLinkDeleteValid(Validator $validator, int $id): void
    {
        $this->query->from('node_menu_link')
            ->where('menu_link_id', '==', $id)
            ->delete()
            ->execute();
    }

    private function getOptions(): array
    {
        $menus = $this->query->from('menu')->fetchAll();

        $options = [];
        foreach ($menus as $menu) {
            $options[] = [
                'label' => t($menu[ 'title' ]),
                'value' => $menu[ 'name' ]
            ];
        }

        return $options;
    }

    private function getListNamesMenu(): string
    {
        $menus = $this->query->from('menu')->fetchAll();
        $names = $menus
            ? array_column($menus, 'name')
            : [];

        return implode(',', $names);
    }
}
