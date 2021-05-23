<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Hook;

use Soosyze\Components\Form\FormGroupBuilder;
use Soosyze\Components\Router\Router;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Menu\Services\Menu;
use SoosyzeCore\Template\Services\Block as TemplateBlock;

class Block implements \SoosyzeCore\Block\BlockInterface
{
    /**
     * @var Menu
     */
    private $menu;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Menu $menu, Router $router)
    {
        $this->menu   = $menu;
        $this->router = $router;
    }

    public function hookBlockCreateFormData(array &$blocks): void
    {
        $menus = $this->menu->getAllMenu();

        foreach ($menus as $menu) {
            $blocks[ "menu.{$menu[ 'name' ]}" ] = [
                'hook'      => 'menu',
                'key_block' => "menu.{$menu[ 'name' ]}",
                'options'   => [ 'name' => $menu[ 'name' ], 'parent' => -1, 'level' => 0 ],
                'path'      => $this->menu->getPathViews(),
                'title'     => $menu[ 'title' ],
                'tpl'       => "components/block/menu-{$menu[ 'name' ]}.php"
            ];
        }
    }

    public function hookBlockMenu(TemplateBlock $tpl, array $options): TemplateBlock
    {
        if ($menu = $this->menu->renderMenu($options[ 'name' ], $options[ 'parent' ])) {
            return $menu
                    ->setName('components/block/menu.php')
                    ->setNamesOverride([ "components/block/menu-{$options[ 'name' ]}.php" ]);
        }

        return $tpl;
    }

    public function hookMenuEditForm(FormGroupBuilder &$form, array $data): void
    {
        $menus = $this->menu->getAllMenu();

        $options = [];
        foreach ($menus as $menu) {
            $options[] = [
                'attr'  => [
                    'data-link' => $this->router->getRoute('menu.api.show', [
                        ':menu' => $menu[ 'name' ]
                    ])
                ],
                'label' => $menu[ 'title' ],
                'value' => $menu[ 'name' ]
            ];
        }

        $form->group('menu-fieldset', 'fieldset', function ($form) use ($data, $options) {
            $form->legend('menu-legend', t('Settings'))
                ->group('name-group', 'div', function ($form) use ($data, $options) {
                    $form->label('name-label', t('Menu to display'))
                    ->select('name', $options, [
                        ':selected'   => $data[ 'options' ][ 'name' ],
                        'class'       => 'form-control ajax-control',
                        'data-target' => 'select[name="parent"]',
                        'max'         => 4,
                        'min'         => 1
                    ]);
                }, [ 'class' => 'form-group' ])
                ->group('parent-group', 'div', function ($form) use ($data) {
                    $form->label('parent-label', t('Parent link'), [
                        'data-tooltip' => t('Show child links of the selected one.')
                    ])
                    ->select('parent', $this->menu->renderMenuSelect($data[ 'options' ][ 'name' ]), [
                        ':selected' => $data[ 'options' ][ 'parent' ],
                        'class'     => 'form-control',
                    ]);
                }, [ 'class' => 'form-group' ]);
        });
    }

    public function hookMenuUpdateValidator(Validator &$validator, int $id): void
    {
        $menus = $this->menu->getAllMenu();
        $names = array_column($menus, 'name');

        $validator
            ->addRule('name', 'required|inarray:' . implode(',', $names))
            ->addRule('parent', 'required|numeric')
            ->addLabel('name', t('Menu to display'))
            ->addLabel('parent', t('Parent link'));
    }

    public function hookMenuUpdateBefore(Validator $validator, array &$values, int $id): void
    {
        $values[ 'options' ] = json_encode([
            'name'   => $validator->getInput('name'),
            'parent' => (int) $validator->getInput('parent')
        ]);
    }
}
