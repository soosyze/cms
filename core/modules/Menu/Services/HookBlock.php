<?php

namespace SoosyzeCore\Menu\Services;

class HookBlock
{
    protected $menu;

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    public function hookCreateFormData(array &$blocks)
    {
        $menus = $this->menu->getAllMenu();

        foreach( $menus as $menu )
        {
            $blocks[ "menu.{$menu[ 'name' ]}" ] = [
                'hook'      => 'menu',
                'key_block' => 'menu',
                'options'   => [ 'name' => $menu[ 'name' ] ],
                'path'      => $this->menu->getPathViews(),
                'title'     => t($menu[ 'title' ]),
                'tpl'       => "block_menu-{$menu[ 'name' ]}.php"
            ];
        }
    }

    public function hookBlockMenu($tpl, array $options)
    {
        if ($menu = $this->menu->renderMenu($options[ 'name' ])) {
            return $menu->setName('block_menu.php')
                    ->addNamesOverride([ 'block_menu_' . $options[ 'name' ] ]);
        }
    }
    
    public function hookMenuEditFormData( &$form, $data )
    {
        $menus = $this->menu->getAllMenu();
        
        $options = [];
        foreach( $menus as $menu )
        {
            $options[] = [ 'value' => $menu[ 'name' ], 'label' => $menu[ 'title' ] ];
        }

        $form->group('menu-fieldset', 'fieldset', function ($form) use ($data, $options)
        {
            $form->legend('name-legend', t('Paramètre des news'))
                ->group('name-group', 'div', function ($form) use ($data, $options)
                {
                    $form->label('name-label', t('Menu à afficher'))
                    ->select('name', $options, [
                        'class'    => 'form-control',
                        'min'      => 1,
                        'max'      => 4,
                        'selected' => $data[ 'options' ][ 'name' ]
                    ]);
                }, [ 'class' => 'form-group' ]);
        });
    }
    
    public function hookMenuUpdateValidator( &$validator, $id )
    {
        $menus = $this->menu->getAllMenu();
        $listName = [];
        foreach( $menus as $menu )
        {
            $listName[] = $menu[ 'name' ];
        }

        $validator
            ->addRule('name', 'required|inarray:' . implode(',', $listName))
            ->addLabel('name', t('Menu à afficher'));
    }
    
    public function hookMenuUpdateBefore( $validator, &$values, $id )
    {
        $values[ 'options' ] = json_encode([
            'name'  => $validator->getInput('name')
        ]);
    }
}
