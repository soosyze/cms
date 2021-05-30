<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Block\Form\FormBlock;
use SoosyzeCore\Template\Services\Block as ServiceBlock;

class Block extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    /**
     * @return ServiceBlock|ResponseInterface
     */
    public function show(int $id, ServerRequestInterface $req)
    {
        if (!($block = $this->find($id))) {
            return $this->get404($req);
        }

        $block[ 'link_edit' ]   = self::router()->getRoute('block.edit', [ ':id' => $id ]);
        $block[ 'link_delete' ] = self::router()->getRoute('block.delete', [ ':id' => $id ]);
        $block[ 'link_update' ] = self::router()->getRoute('block.update', [ ':id' => $id ]);

        if (!empty($block[ 'hook' ])) {
            $data = self::block()->getBlocks();
            $key  = $block[ 'key_block' ];

            $tpl = self::template()
                ->getTheme('theme_admin')
                ->createBlock($data[ $key ][ 'tpl' ], $data[ $key ][ 'path' ]);

            $block[ 'content' ] .= (string) $this->container->callHook(
                'block.' . $block[ 'hook' ],
                [ $tpl, $this->getOptions($block) ]
            );
        }

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/block-show.php', $this->pathViews)
                ->addVars([ 'block' => $block ]);
    }

    public function create(string $theme, string $section): ServiceBlock
    {
        $data = self::block()->getBlocks();

        $action = self::router()->getRoute('block.store', [
            ':theme'   => $theme, ':section' => $section
        ]);

        $form = new FormBuilder([ 'action' => $action, 'method' => 'post' ]);

        $srcImage = self::core()->getPath('modules', 'modules/core', false) . '/Block/Assets/misc/static.svg';

        foreach ($data as $key => $block) {
            if (empty($block[ 'hook' ])) {
                $content = self::template()
                    ->getTheme('theme_admin')
                    ->createBlock($block[ 'tpl' ], $block[ 'path' ])
                    ->addVar('src_image', $srcImage);
            } else {
                $tpl = self::template()
                    ->getTheme('theme_admin')
                    ->createBlock($block[ 'tpl' ], $block[ 'path' ]);

                $content = $this->container->callHook('block.' . $block[ 'hook' ], [
                    $tpl,
                    $block[ 'options' ] ?? []
                ]);
            }

            $attrContent = empty($content)
                ? [
                    'class'    => 'block-content-disabled',
                    ':content' => t('No content available for this block')
                ] : [
                    'class'    => 'block-content',
                    ':content' => $content
                ];

            $form->html("key_block-$key-content", '<div:attr>:content</div>', $attrContent)
                ->group("key_block-$key-group", 'div', function ($form) use ($key) {
                    $form
                    ->radio('key_block', [
                        'id'    => "key_block-$key",
                        'value' => $key
                    ])
                    ->label("$key-label", t('Select'), [
                        'for' => "key_block-$key"
                    ]);
                }, [ 'class' => 'radio-button' ]);
        }

        $form->group('submit-group', 'div', function ($form) use ($section) {
            $form->token("token_$section")
                ->submit('submit', t('Add'), [ 'class' => 'btn btn-success' ]);
        });

        $this->container->callHook('block.create.form', [ &$form, $data ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/content-block-create.php', $this->pathViews)
                ->addVars([
                    'blocks'  => $data,
                    'form'    => $form,
                    'section' => $section
        ]);
    }

    public function store(string $theme, string $section, ServerRequestInterface $req): ResponseInterface
    {
        $blocks = self::block()->getBlocks();

        $validator = (new Validator())
            ->setRules([
                'key_block'      => 'required|string|max:255',
                "token_$section" => 'token'
            ])
            ->setInputs($req->getParsedBody());

        $this->container->callHook('block.store.validator', [ &$validator ]);

        if ($validator->isValid()) {
            $block   = $blocks[ $validator->getInput('key_block') ];
            $content = '';

            if (empty($block[ 'hook' ])) {
                $block[ 'hook' ] = null;

                $content = (string) self::template()
                        ->getTheme('theme_admin')
                        ->createBlock($block[ 'tpl' ], $block[ 'path' ])
                        ->addVars([
                            'src_image' => self::core()->getPath('modules', 'modules/core', false) . '/Block/Assets/misc/static.svg'
                ]);
            }

            $values = [
                'section'          => $section,
                'title'            => t($block[ 'title' ]),
                'content'          => $content,
                'weight'           => 1,
                'visibility_roles' => true,
                'roles'            => '1,2',
                'hook'             => $block[ 'hook' ],
                'key_block'        => $validator->getInput('key_block'),
                'options'          => empty($block[ 'options' ])
                    ? null
                    : json_encode($block[ 'options' ])
            ];

            $this->container->callHook('block.store.before', [ $validator, &$values ]);

            self::query()
                ->insertInto('block', array_keys($values))
                ->values($values)
                ->execute();

            $this->container->callHook('block.store.after', [ $validator, $values ]);
        }

        return new Redirect(
            self::router()->getRoute('block.section.admin', [ ':theme' => $theme ])
        );
    }

    /**
     * @return ServiceBlock|ResponseInterface
     */
    public function edit(int $id, ServerRequestInterface $req)
    {
        if (!($data = $this->find($id))) {
            return $this->get404($req);
        }
        $data[ 'roles' ]   = explode(',', $data[ 'roles' ]);
        $data[ 'options' ] = $this->getOptions($data);

        $this->container->callHook('block.edit.form.data', [ &$data, $id ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $action = self::router()->getRoute('block.update', [
            ':id' => $data[ 'block_id' ]
        ]);

        $form = (new FormBlock([ 'action' => $action, 'method' => 'post' ]))
            ->setValues($data, $id, self::user()->getRoles())
            ->makeFields();

        if (!empty($data[ 'hook' ])) {
            $form->after('block-fieldset', function ($form) use ($data, $id) {
                self::core()->callHook("block.{$data[ 'hook' ]}.edit.form", [
                    &$form, $data, $id
                ]);
            });
        }

        $this->container->callHook('block.edit.form', [ &$form, $data, $id ]);

        if (isset($_SESSION[ 'errors' ])) {
            unset($_SESSION[ 'errors_keys' ][ 'roles' ]);
            $form->addErrors($_SESSION[ 'errors' ])
                ->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors' ], $_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('block/content-block-form.php', $this->pathViews)
                ->addVars([
                    'form'      => $form,
                    'link_show' => self::router()->getRoute('block.show', [ ':id' => $data[ 'block_id' ] ])
        ]);
    }

    /**
     * @return ServiceBlock|ResponseInterface
     */
    public function update(int $id, ServerRequestInterface $req)
    {
        if (!($block = $this->find($id))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'title'            => '!required|string|max:255',
                'content'          => '!required|string|max:5000',
                'class'            => '!required|string|max:255',
                'visibility_pages' => 'bool',
                'pages'            => '!required|string',
                'visibility_roles' => 'bool',
                'roles'            => '!required|array',
                "token_block_$id"  => 'token'
            ])
            ->setLabels([
                'title'   => t('Title'),
                'content' => t('Content'),
                'pages'   => t('List of pages'),
                'roles'   => t('User Roles')
            ])
            ->setInputs(
                $req->getParsedBody()
            );

        if ($block[ 'hook' ]) {
            $this->container->callHook("block.{$block[ 'hook' ]}.update.validator", [ &$validator, $id ]);
        }
        $this->container->callHook('block.update.validator', [ &$validator, $id ]);

        $validatorRoles = new Validator();

        if ($isValid = $validator->isValid()) {
            $listRoles = implode(',', self::query()->from('role')->lists('role_id'));
            foreach ($validator->getInput('roles', []) as $key => $role) {
                $validatorRoles
                    ->addRule($key, 'int|inarray:' . $listRoles)
                    ->addLabel($key, t($role))
                    ->addInput($key, $key);
            }
        }
        $isValid &= $validatorRoles->isValid();

        if ($isValid) {
            $idRoles = array_keys($validator->getInput('roles', []));
            $values  = [
                'title'            => $validator->getInput('title'),
                'content'          => $validator->getInput('content'),
                'class'            => $validator->getInput('class'),
                'visibility_pages' => (bool) $validator->getInput('visibility_pages'),
                'pages'            => $validator->getInput('pages'),
                'visibility_roles' => (bool) $validator->getInput('visibility_roles'),
                'roles'            => implode(',', $idRoles)
            ];

            if ($block[ 'hook' ]) {
                $this->container->callHook("block.{$block[ 'hook' ]}.update.before", [
                    &$validator, &$values, $id
                ]);
            }
            $this->container->callHook('block.update.before', [
                $validator, &$values, $id
            ]);

            self::query()
                ->update('block', $values)
                ->where('block_id', '==', $id)
                ->execute();

            if ($block[ 'hook' ]) {
                $this->container->callHook("block.{$block[ 'hook' ]}.update.after", [
                    &$validator, $id
                ]);
            }
            $this->container->callHook('block.update.after', [ $validator, $id ]);

            return $this->show($id, $req);
        }

        $_SESSION[ 'inputs' ]      = $validator->getInputs();
        $_SESSION[ 'errors' ]      = $validator->getKeyErrors() + $validatorRoles->getKeyErrors();
        $_SESSION[ 'errors_keys' ] = $validator->getKeyInputErrors();

        return $this->edit($id, $req);
    }

    public function delete(int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!$this->find($id)) {
            return $this->get404($req);
        }

        $this->container->callHook('block.delete.before', [ $id ]);
        self::query()->from('block')->where('block_id', '==', $id)->delete()->execute();
        $this->container->callHook('block.delete.after', [ $id ]);

        return $this->json();
    }

    private function find(int $id): array
    {
        return self::query()->from('block')->where('block_id', '==', $id)->fetch();
    }

    private function getOptions(array $block, array $default = []): array
    {
        return empty($block[ 'options' ])
                ? $default
                : json_decode($block[ 'options' ], true);
    }
}
