<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Node\Form\FormNode;

class Entity extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function create(int $idNode, string $entity, ServerRequestInterface $req): ResponseInterface
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fieldNode = self::node()->getFieldRelationByEntity($entity))) {
            return $this->get404($req);
        }
        $options      = json_decode($fieldNode[ 'field_option' ]);
        if (!($fieldsEntity = self::node()->getFieldsEntity($entity))) {
            return $this->get404($req);
        }
        if (self::node()->isMaxEntity($entity, $options->foreign_key, $idNode, $options->count)) {
            return $this->get404($req);
        }

        $content = [];

        $this->container->callHook('entity.create.form.data', [ &$content, $node, $entity ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormNode([
            'action'  => self::router()->getRoute('entity.store', [
                ':id_node' => $idNode,
                ':entity'  => $entity
            ]),
            'enctype' => 'multipart/form-data',
            'method'  => 'post' ], self::file(), self::query(), self::router(), self::config()))
            ->setValues($content, $entity, $fieldsEntity)
            ->entityFieldset()
            ->actionsEntitySubmit();

        $this->container->callHook('entity.create.form', [ &$form, $content, $node, $entity ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-file" aria-hidden="true"></i>',
                    'title_main' => t('Add content of type :name', [
                        ':name' => $entity ])
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'node/content-entity-form.php', $this->pathViews, [
                    'form' => $form
        ]);
    }

    public function store(int $idNode, string $entity, ServerRequestInterface $req): ResponseInterface
    {
        if ($req->isMaxSize()) {
            $_SESSION[ 'messages' ][ 'errors' ] = [
                t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
            ];
            $_SESSION[ 'errors_keys' ]          = [];

            return new Redirect(
                self::router()->getRoute('entity.create', [
                    ':id_node' => $idNode,
                    ':entity'  => $entity
                ])
            );
        }
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fieldNode = self::node()->getFieldRelationByEntity($entity))) {
            return $this->get404($req);
        }
        if (!($fieldsEntity = self::node()->getFieldsEntity($entity))) {
            return $this->get404($req);
        }
        $options = json_decode($fieldNode[ 'field_option' ]);
        if (self::node()->isMaxEntity($entity, $options->foreign_key, $idNode, $options->count)) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([ 'token_entity' => 'token' ])
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles());

        /* Test des champs personnalisés de la node. */
        $files = [];
        foreach ($fieldsEntity as $value) {
            $key = $value[ 'field_name' ];
            $validator->addRule($key, $value[ 'field_rules' ]);
            if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                $files[] = $key;
            }
        }

        $this->container->callHook('entity.store.validator', [ &$validator, $node, $entity ]);

        if ($validator->isValid()) {
            /* Prépare les champs de la table enfant. */
            $fields = [];
            foreach ($fieldsEntity as $value) {
                $key = $value[ 'field_name' ];
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $fields[ $key ] = '';
                } elseif ($value[ 'field_type' ] === 'number') {
                    $fields[ $key ] = (int) $validator->getInput($key, '');
                } elseif ($value[ 'field_type' ] === 'checkbox') {
                    $fields[ $key ] = implode(',', $validator->getInput($key, []));
                } else {
                    $fields[ $key ] = $validator->getInput($key, '');
                }
            }

            $data = self::node()->getEntity($node[ 'type' ], $node[ 'entity_id' ]);

            $fields[ $node[ 'type' ] . '_id' ] = $data[ $node[ 'type' ] . '_id' ];

            $this->container->callHook('entity.store.before', [ $validator, &$fields, $node, $entity ]);
            self::query()
                ->insertInto('entity_' . $entity, array_keys($fields))
                ->values($fields)
                ->execute();
            $this->container->callHook('entity.store.after', [ $validator, $node, $entity ]);

            /* Télécharge et enregistre les fichiers. */
            $idEntity = self::schema()->getIncrement('entity_' . $entity);
            foreach ($fieldsEntity as $value) {
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $this->saveFile($node[ 'type' ], $idNode, $entity, $idEntity, $value[ 'field_name' ], $validator);
                }
            }

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Your content has been saved.') ];

            return new Redirect(
                self::router()->getRoute('node.edit', [
                    ':id_node' => $idNode
                ])
            );
        }
        $_SESSION[ 'inputs' ]               = $validator->getInputsWithout($files);
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect(
            self::router()->getRoute('entity.create', [
                ':id_node' => $idNode,
                ':entity'  => $entity
            ])
        );
    }

    public function edit(int $idNode, string $entity, int $idEntity, ServerRequestInterface $req): ResponseInterface
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fieldNode = self::node()->getFieldRelationByEntity($entity))) {
            return $this->get404($req);
        }
        if (!($fieldsEntity = self::node()->getFieldsEntity($entity))) {
            return $this->get404($req);
        }
        if (!($content = self::node()->getEntity($entity, $idEntity))) {
            return $this->get404($req);
        }

        $this->container->callHook('entity.edit.form.data', [
            &$content, $node, $entity, $idEntity
        ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormNode([
            'action'  => self::router()->getRoute('entity.update', [
                ':id_node'   => $idNode,
                ':entity'    => $entity,
                ':id_entity' => $idEntity
            ]),
            'enctype' => 'multipart/form-data',
            'method'  => 'post' ], self::file(), self::query(), self::router(), self::config()))
            ->setValues($content, $entity, $fieldsEntity)
            ->entityFieldset()
            ->actionsEntitySubmit();

        $this->container->callHook('entity.edit.form', [ &$form, $content, $node, $entity, $idEntity ]);

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            unset($_SESSION[ 'errors_keys' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-file" aria-hidden="true"></i>',
                    'title_main' => t('Edit :title content', [
                        ':title' => $entity
                    ])
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'node/content-entity-form.php', $this->pathViews, [ 'form' => $form ]);
    }

    public function update(int $idNode, string $entity, int $idEntity, ServerRequestInterface $req): ResponseInterface
    {
        if ($req->isMaxSize()) {
            $_SESSION[ 'messages' ][ 'errors' ] = [
                t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
            ];
            $_SESSION[ 'errors_keys' ]          = [];

            return new Redirect(
                self::router()->getRoute('entity.update', [
                    ':id_node'   => $idNode,
                    ':entity'    => $entity,
                    ':id_entity' => $idEntity
                ])
            );
        }

        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fieldNode = self::node()->getFieldRelationByEntity($entity))) {
            return $this->get404($req);
        }
        if (!($fieldsEntity = self::node()->getFieldsEntity($entity))) {
            return $this->get404($req);
        }
        if (!self::node()->getEntity($entity, $idEntity)) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([ 'token_entity' => 'token' ])
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles());

        /* Test des champs personnalisé de la node. */
        $files = [];
        foreach ($fieldsEntity as $value) {
            if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                $files[] = $value[ 'field_type' ];
            }
            $validator->addRule($value[ 'field_name' ], $value[ 'field_rules' ]);
        }

        $this->container->callHook('entity.update.validator', [
            &$validator, $node, $entity, $idEntity
        ]);

        if ($validator->isValid()) {
            $fields = [];
            foreach ($fieldsEntity as $value) {
                $key = $value[ 'field_name' ];
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $this->saveFile($node['type'], $idNode, $entity, $idEntity, $key, $validator);
                } elseif ($value[ 'field_type' ] === 'number') {
                    $fields[ $key ] = (int) $validator->getInput($key, '');
                } elseif ($value[ 'field_type' ] === 'checkbox') {
                    $fields[ $key ] = implode(',', $validator->getInput($key, []));
                } else {
                    $fields[ $key ] = $validator->getInput($key, '');
                }
            }

            $this->container->callHook('entity.update.before', [
                $validator, &$fields, $node, $entity, $idEntity
            ]);
            self::query()
                ->update('entity_' . $entity, $fields)
                ->where($entity . '_id', '==', $idEntity)
                ->execute();
            $this->container->callHook('entity.update.after', [
                $validator, $node, $entity, $idEntity
            ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];

            return new Redirect(
                self::router()->getRoute('node.edit', [
                    ':id_node' => $idNode
                ])
            );
        }
        $_SESSION[ 'inputs' ]               = $validator->getInputsWithout($files);
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect(
            self::router()->getRoute('entity.update', [
                ':id_node'   => $idNode,
                ':entity'    => $entity,
                ':id_entity' => $idEntity
            ])
        );
    }

    public function delete(int $idNode, string $typeEntity, int $idEntity, ServerRequestInterface $req): ResponseInterface
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fieldNode = self::node()->getFieldRelationByEntity($typeEntity))) {
            return $this->get404($req);
        }
        if (!($fieldsEntity = self::node()->getFieldsEntity($typeEntity))) {
            return $this->get404($req);
        }
        if (!($entity = self::node()->getEntity($typeEntity, $idEntity))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([ 'id' => 'required' ])
            ->setInputs([ 'id' => $idNode ]);

        /* Si la node est publié. */
        if ($node[ 'node_status_id' ] === 1 && ($rules = self::node()->getRules($fieldNode))) {
            $entitys = self::query()
                ->from('entity_' . $typeEntity)
                ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
                ->limit(2)
                ->fetchAll();

            /* Et que l'entité est requise, la dernière entité ne peut-être supprimé. */
            if (isset($rules[ 'required' ]) && count($entitys) === 1) {
                $validator
                    ->addRule('node_status_id', '!equal:1')
                    ->addInput('node_status_id', 1);
            }
        }

        if ($validator->isValid()) {
            self::query()
                ->from('entity_' . $typeEntity)
                ->delete()
                ->where($typeEntity . '_id', '==', $idEntity)
                ->execute();

            $this->deleteFile($fieldsEntity, $entity);
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputs();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        }

        return new Redirect(
            self::router()->getRoute('node.edit', [
                ':id_node'   => $idNode,
                ':entity'    => $typeEntity,
                ':id_entity' => $idEntity
            ]),
            302
        );
    }

    private function deleteFile(array $fieldsEntity, string $entity): void
    {
        foreach ($fieldsEntity as $field) {
            if (!in_array($field[ 'field_type' ], [ 'image', 'file' ])) {
                continue;
            }

            $file = $dir = self::core()->getSetting('root', '') . $entity[ $field[ 'field_name' ] ];
            if (!is_file($file)) {
                continue;
            }
            \unlink($file);
        }
    }

    private function saveFile(
        string $typeNode,
        int $idNode,
        string $typeEntity,
        int $idEntity,
        string $nameField,
        Validator $validator
    ): void {
        self::file()
            ->add($validator->getInput($nameField), $validator->getInput("file-$nameField-name"))
            ->setPath("/node/$typeNode/{$idNode}/$typeEntity")
            ->isResolvePath()
            ->isResolveName()
            ->callGet(function ($key, $name) use ($typeEntity, $idEntity) {
                return self::query()
                    ->from('entity_' . $typeEntity)
                    ->where($typeEntity . '_id', '==', $idEntity)
                    ->fetch();
            })
            ->callMove(function ($key, $name, $move) use ($typeEntity, $idEntity, $nameField) {
                self::query()
                ->update('entity_' . $typeEntity, [ $nameField => $move ])
                ->where($typeEntity . '_id', '==', $idEntity)
                ->execute();
            })
            ->callDelete(function ($key, $name) use ($typeEntity, $idEntity, $nameField) {
                self::query()
                ->update('entity_' . $typeEntity, [ $nameField => '' ])
                ->where($typeEntity . '_id', '==', $idEntity)
                ->execute();
            })
            ->save();
    }
}
