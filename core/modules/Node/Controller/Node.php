<?php

namespace SoosyzeCore\Node\Controller;

use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Http\Stream;
use Soosyze\Components\Http\UploadedFile;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Node\Form\FormNode;
use SoosyzeCore\Node\Form\FormNodeDelete;

class Node extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function add($req)
    {
        $nodeType = self::query()
            ->from('node_type')
            ->orderBy('node_type_name')
            ->fetchAll();

        foreach ($nodeType as $key => &$value) {
            $reqGranted = self::router()->getRequestByRoute('node.create', [
                ':node' => $value[ 'node_type' ]
            ]);
            if (!$this->container->callHook('app.granted.request', [ $reqGranted ])) {
                unset($nodeType[ $key ]);
            }
            $value[ 'link' ] = self::router()->getRoute('node.create', [
                ':node' => $value[ 'node_type' ]
            ]);
        }
        unset($value);

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-file" aria-hidden="true"></i>',
                    'title_main' => t('Add content')
                ])
                ->make('page.content', 'node/content-node-add.php', $this->pathViews, [
                    'node_type' => $nodeType
        ]);
    }

    public function create($type, $req)
    {
        if (!$fields = self::node()->getFieldsDisplay($type)) {
            return $this->get404($req);
        }

        $content = [];

        $this->container->callHook('node.create.form.data', [ &$content, $type ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormNode([
                'action'  => self::router()->getRoute('node.store', [ ':node' => $type ]),
                'enctype' => 'multipart/form-data',
                'id'      => 'form-node',
                'method'  => 'post' ], self::file(), self::query(), self::router(), self::config()))
            ->setValues($content, $type, $fields)
            ->setUserCurrent(self::user()->isConnected())
            ->setDisabledUserCurrent(!self::user()->isGranted('node.user.edit'))
            ->makeFields();

        $this->container->callHook('node.create.form', [ &$form, $content, $type ]);

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
                        ':name' => $fields[ 0 ][ 'node_type_name' ]
                    ])
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'node/content-node-form.php', $this->pathViews, [
                    'form'                  => $form,
                    'node_fieldset_submenu' => $this->getNodeFieldsetSubmenu()
                ])
                ->override('page.content', [ 'node/content-node-form_create.php' ]);
    }

    public function store($type, $req)
    {
        if ($req->isMaxSize()) {
            $_SESSION[ 'messages' ][ 'errors' ] = [
                t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
            ];
            $_SESSION[ 'errors_keys' ]          = [];

            return new Redirect(self::router()->getRoute('node.create', [ ':node' => $type ]));
        }
        if (!($fields = self::node()->getFieldsForm($type))) {
            return $this->get404($req);
        }

        /* Test les champs par defauts de la node. */
        $validator = $this->getValidator($req, $type, $fields);

        $this->container->callHook('node.store.validator', [ &$validator, $type ]);

        if ($validator->isValid()) {
            /* Prépare les champs de la table enfant. */
            $fieldsInsert   = [];
            $fieldsRelation = false;
            foreach ($fields as $value) {
                $key = $value[ 'field_name' ];
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $fieldsInsert[ $key ] = '';
                } elseif (in_array($value[ 'field_type' ], [ 'one_to_many' ])) {
                    $fieldsRelation = true;
                } elseif ($value[ 'field_type' ] === 'checkbox') {
                    $fieldsInsert[ $key ] = implode(',', $validator->getInput($key, []));
                } else {
                    $fieldsInsert[ $key ] = $validator->getInput($key, '');
                }
            }

            $this->container->callHook('node.entity.store.before', [ $validator, &$fieldsInsert, $type ]);
            self::query()
                ->insertInto('entity_' . $type, array_keys($fieldsInsert))
                ->values($fieldsInsert)
                ->execute();
            $this->container->callHook('node.entity.store.after', [ $validator, $type ]);

            /* Rassemble les champs personnalisés dans la node. */
            $node = [
                'date_changed'     => (string) time(),
                'date_created'     => (string) strtotime($validator->getInput('date_created')),
                'entity_id'        => self::schema()->getIncrement('entity_' . $type),
                'meta_description' => $validator->getInput('meta_description'),
                'meta_noarchive'   => (bool) $validator->getInput('meta_noarchive'),
                'meta_nofollow'    => (bool) $validator->getInput('meta_nofollow'),
                'meta_noindex'     => (bool) $validator->getInput('meta_noindex'),
                'meta_title'       => $validator->getInput('meta_title'),
                'sticky'           => (bool) $validator->getInput('sticky'),
                'node_status_id'   => (int) $validator->getInput('node_status_id'),
                'title'            => $validator->getInput('title'),
                'type'             => $type,
                'user_id'          => $validator->getInput('user_id') === ''
                    ? null
                    : (int) $validator->getInput('user_id')
            ];

            $this->container->callHook('node.store.before', [ $validator, &$node ]);
            self::query()
                ->insertInto('node', array_keys($node))
                ->values($node)
                ->execute();
            $this->container->callHook('node.store.after', [ $validator ]);

            /* Télécharge et enregistre les fichiers. */
            $node[ 'id' ] = self::schema()->getIncrement('node');

            foreach ($fields as $value) {
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $this->saveFile($node, $value[ 'field_name' ], $validator);
                }
            }

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Your content has been saved.') ];

            $idNode = self::schema()->getIncrement('node');

            return new Redirect(
                $fieldsRelation
                ? self::router()->getRoute('node.edit', [ ':id_node' => $idNode ])
                : self::router()->getRoute('node.admin')
            );
        }
        $_SESSION[ 'inputs' ]               = $validator->getInputsWithoutObject();
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        if (in_array('date_created', $_SESSION[ 'errors_keys' ])) {
            $_SESSION[ 'errors_keys' ][] = 'date';
            $_SESSION[ 'errors_keys' ][] = 'date_time';
        }

        return new Redirect(self::router()->getRoute('node.create', [ ':node' => $type ]));
    }

    public function show($idNode, $req)
    {
        if (!($node = self::node()->getCurrentNode($idNode))) {
            return $this->get404($req);
        }

        $fields = self::node()->makeFieldsById($node[ 'type' ], $node[ 'entity_id' ]);
        $user   = self::nodeuser()->getInfosUser($node);

        $messages = [];
        if ($node[ 'node_status_id' ] != 1) {
            $messages = [
                'infos' => [ t('This content is not published') ]
            ];
        }

        $tpl = self::template()
                ->view('this', [
                    'description' => $node[ 'meta_description' ],
                    'title'       => $node[ 'meta_title' ]
                ])
                ->addMetas($this->getMeta($node, $fields))
                ->view('page', [
                    'fields'     => $fields,
                    'node'       => $node,
                    'title_main' => $node[ 'title' ],
                    'user'       => $user
                ])
                ->override('page', [
                    'page-node-show_' . $node[ 'type' ] . '.php',
                    'page-node.php'
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', $this->getSubmenuNode('node.show', $idNode))
                ->make('page.content', 'node/content-node-show.php', $this->pathViews, [
                    'fields' => $fields,
                    'node'   => $node,
                    'user'   => $user
                ])->override('page.content', [
                    'node/content-node-show_' . $idNode . '.php',
                    'node/content-node-show_' . $node[ 'type' ] . '.php'
                ]);

        $this->container->callHook('node.show.tpl', [ &$tpl, $node, $idNode ]);

        return $tpl;
    }

    public function edit($idNode, $req)
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        if (!($fields = self::node()->getFieldsDisplay($node[ 'type' ]))) {
            return $this->get404($req);
        }

        $content = $node + self::node()->getEntity($node[ 'type' ], $node[ 'entity_id' ]);

        $this->container->callHook('node.edit.form.data', [ &$content, $idNode ]);

        if (isset($_SESSION[ 'inputs' ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormNode([
                'action'  => self::router()->getRoute('node.update', [ ':id_node' => $idNode ]),
                'enctype' => 'multipart/form-data',
                'id'      => 'form-node',
                'method'  => 'post' ], self::file(), self::query(), self::router(), self::config()))
            ->setValues($content, $content[ 'type' ], $fields)
            ->setDisabledUserCurrent(!self::user()->isGranted('node.user.edit'))
            ->makeFields();

        $this->container->callHook('node.edit.form', [ &$form, $content ]);

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
                    'title_main' => t('Edit :title content', [ ':title' => $content[ 'title' ] ])
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', $this->getSubmenuNode('node.edit', $idNode))
                ->make('page.content', 'node/content-node-form.php', $this->pathViews, [
                    'form'                  => $form,
                    'node_fieldset_submenu' => $this->getNodeFieldsetSubmenu()
                ])
                ->override('page.content', [ 'node/content-node-form_edit.php' ]);
    }

    public function update($idNode, $req)
    {
        if ($req->isMaxSize()) {
            $_SESSION[ 'messages' ][ 'errors' ] = [
                t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
            ];
            $_SESSION[ 'errors_keys' ]          = [];

            return new Redirect(
                self::router()->getRoute('node.edit', [
                    ':id_node' => $idNode
                ])
            );
        }
        if (!($node = self::node()->getCurrentNode($idNode))) {
            return $this->get404($req);
        }
        if (!($fields = self::node()->getFieldsForm($node[ 'type' ]))) {
            return $this->get404($req);
        }

        $validator = $this->getValidator($req, $node[ 'type' ], $fields, $idNode);

        $this->container->callHook('node.update.validator', [ &$validator, $idNode ]);

        if ($validator->isValid()) {
            $fieldsUpdate = [];
            foreach ($fields as $value) {
                $key = $value[ 'field_name' ];
                if (in_array($value[ 'field_type' ], [ 'image', 'file' ])) {
                    $this->saveFile($node, $key, $validator);
                } elseif (in_array($value[ 'field_type' ], [ 'one_to_many' ])) {
                    $this->updateWeightEntity(
                        $value,
                        $validator->getInput($key, [])
                    );
                } elseif ($value[ 'field_type' ] === 'checkbox') {
                    $fieldsUpdate[ $key ] = implode(',', $validator->getInput($key, [
                        ]));
                } else {
                    $fieldsUpdate[ $key ] = $validator->getInput($key, '');
                }
            }

            $this->container->callHook('node.entity.update.before', [
                $validator, &$fieldsUpdate, $node, $idNode
            ]);
            self::query()
                ->update('entity_' . $node[ 'type' ], $fieldsUpdate)
                ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
                ->execute();
            $this->container->callHook('node.entity.update.after', [
                $validator, $node, $idNode
            ]);

            $value = [
                'date_changed'     => (string) time(),
                'date_created'     => strtotime($validator->getInput('date_created')),
                'meta_description' => $validator->getInput('meta_description'),
                'meta_noarchive'   => (bool) $validator->getInput('meta_noarchive'),
                'meta_nofollow'    => (bool) $validator->getInput('meta_nofollow'),
                'meta_noindex'     => (bool) $validator->getInput('meta_noindex'),
                'meta_title'       => $validator->getInput('meta_title'),
                'node_status_id'   => (int) $validator->getInput('node_status_id'),
                'sticky'           => (bool) $validator->getInput('sticky'),
                'title'            => $validator->getInput('title'),
                'user_id'          => $validator->getInput('user_id') === ''
                    ? null
                    : (int) $validator->getInput('user_id')
            ];

            $this->container->callHook('node.update.before', [
                $validator, &$value, $idNode
            ]);
            self::query()
                ->update('node', $value)
                ->where('id', '==', $idNode)
                ->execute();
            $this->container->callHook('node.update.after', [ $validator, $idNode ]);

            $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        } else {
            $_SESSION[ 'inputs' ]               = $validator->getInputsWithoutObject();
            $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
            $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

            if (in_array('date_created', $_SESSION[ 'errors_keys' ])) {
                $_SESSION[ 'errors_keys' ][] = 'date';
                $_SESSION[ 'errors_keys' ][] = 'date_time';
            }
        }

        return new Redirect(
            self::router()->getRoute('node.edit', [
                ':id_node' => $idNode
            ])
        );
    }

    public function remove($idNode, $req)
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }

        $content = [];

        if (isset($_SESSION[ 'inputs' ])) {
            $content = array_merge($content, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $content[ 'current_path' ] = self::alias()->getAlias('node/' . $node[ 'id' ], 'node/' . $node[ 'id' ]);

        $pathsSettings = self::node()->getPathSettings();

        $useInPath = null;
        foreach ($pathsSettings as $value) {
            if (!empty($value[ 'path' ]) && self::alias()->getSource($value[ 'path' ], $value[ 'path' ]) === 'node/' . $idNode) {
                $useInPath = $value;

                break;
            }
        }

        $this->container->callHook('node.remove.form.data', [ &$node, $idNode ]);

        $action = self::router()->getRoute('node.delete', [ ':id_node' => $idNode ]);

        $form = (new FormNodeDelete([ 'action' => $action, 'method' => 'post' ], self::router()))
            ->setValues($content, $useInPath)
            ->makeFields();

        $this->container->callHook('node.remove.form', [ &$form, $node, $idNode ]);

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
                    'title_main' => t('Delete :name content', [ ':name' => $node[ 'title' ] ])
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', $this->getSubmenuNode('node.delete', $idNode))
                ->make('page.content', 'node/content-node-form.php', $this->pathViews, [
                    'form' => $form,
                ])
                ->override('page.content', [ 'node/content-node-form_remove.php' ]);
    }

    public function delete($idNode, $req)
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'files' => 'bool',
                'id'    => 'required'
            ])
            ->setInputs([ 'id' => $idNode ] + $req->getParsedBody());

        $pathsSettings = self::node()->getPathSettings();

        foreach ($pathsSettings as $value) {
            if (empty($value[ 'path' ]) && self::alias()->getSource($value[ 'path' ], $value[ 'path' ]) !== 'node/' . $idNode) {
                continue;
            }

            $not = empty($value[ 'required' ])
                ? ''
                : '!';

            $currentAlias = self::alias()->getalias('node/' . $idNode, 'node/' . $idNode);

            $validator
                ->addRule('path', $not . "required|route|!equal:$currentAlias|!equal:node/$idNode")
                ->addInput('path_key', $value[ 'key' ])
                ->addRule('path_key', $not . 'required|string')
                ->addLabel('path', t('New path for') . ' ' . t($value[ 'title' ]))
                ->setMessages([
                    'path' => [
                        'equal' => [
                            'not' => t('You cannot enter the URL of the content that is going to be deleted.')
                        ]
                    ]
                ]);

            break;
        }

        $this->container->callHook('node.delete.validator', [ &$validator, $idNode ]);

        if ($validator->isValid()) {
            $this->container->callHook('node.delete.before', [ $validator, $idNode ]);
            self::node()->deleteRelation($node);

            self::query()
                ->from('node')
                ->delete()
                ->where('id', '==', $idNode)
                ->execute();

            if ((bool) $validator->getInput('files')) {
                self::node()->deleteFile($node[ 'type' ], $idNode);
            }
            $this->container->callHook('node.delete.after', [ $validator, $idNode ]);

            $_SESSION[ 'messages' ][ 'success' ] = [
                t('Content :title has been deleted', [ ':title' => $node[ 'title' ] ])
            ];

            if ($validator->getInput('path')) {
                var_dump($validator->getInput('path_key'), $validator->getInput('path'));
                self::config()->set($validator->getInput('path_key'), $validator->getInput('path'));
            }

            return new Redirect(self::router()->getRoute('node.admin'));
        }

        $_SESSION[ 'inputs' ]               = $validator->getInputs();
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect(self::router()->getRoute('node.remove', [ ':id_node' => $idNode ]));
    }

    public function cloneNode($idNode, $req)
    {
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }
        $type   = $node[ 'type' ];
        if (!($entity = self::node()->getEntity($type, $node[ 'entity_id' ]))) {
            return $this->get404($req);
        }
        if (!($fields = self::node()->getFieldsform($type))) {
            return $this->get404($req);
        }
        if (mb_strlen($node[ 'title' ] . ' clone') > 255) {
            $_SESSION[ 'messages' ][ 'errors' ] = [ 'Clone content title is too long' ];

            return new Redirect(self::router()->getRoute('node.admin'));
        }

        $entityClone = $entity;
        /* Construit l'entity principale */
        unset($entityClone[ $type . '_id' ]);
        self::query()
            ->insertInto('entity_' . $type, array_keys($entityClone))
            ->values($entityClone)
            ->execute();
        $entityId    = self::schema()->getIncrement('entity_' . $type);

        /* Construit la node */
        unset($node[ 'id' ], $node[ 'node_status_id' ]);
        $node[ 'entity_id' ]    = $entityId;
        $node[ 'title' ]        .= ' clone';
        $node[ 'date_created' ] = (string) time();
        $node[ 'date_changed' ] = (string) time();

        self::query()
            ->insertInto('node', array_keys($node))
            ->values($node)
            ->execute();
        $nodeId = self::schema()->getIncrement('node');

        /* Parcours les champs de l'entité principal. */
        foreach ($fields as $value) {
            $fieldName = $value[ 'field_name' ];
            /* Copie ses fichiers. */
            if (in_array($value[ 'field_type' ], [ 'file', 'image' ])) {
                $file = $entity[ $fieldName ];

                if (!is_file($file)) {
                    continue;
                }

                $upload = new UploadedFile(
                    new Stream(fopen($file, 'r')),
                    self::getBasename($file)
                );
                self::file()
                    ->add($upload)
                    ->setPath("/node/$type/$nodeId")
                    ->isResolvePath()
                    ->isResolveName()
                    ->callMove(function ($key, $name, $move) use ($type, $entityId, $fieldName) {
                        self::query()
                        ->update('entity_' . $type, [ $fieldName => $move ])
                        ->where($type . '_id', '==', $entityId)
                        ->execute();
                    })
                    ->saveOne();
            }
            /* Si elle possède des sous entités. */
            elseif (in_array($value[ 'field_type' ], [ 'one_to_many' ])) {
                $options      = !empty($value[ 'field_option' ])
                    ? json_decode($value[ 'field_option' ], true)
                    : [];
                $dataRelation = self::query()
                    ->from($options[ 'relation_table' ])
                    ->where($options[ 'foreign_key' ], $entity[ $options[ 'foreign_key' ] ])
                    ->fetchAll();

                $fieldsFile = self::query()
                    ->from('node_type_field')
                    ->leftJoin('field', 'field_id', '=', 'field.field_id')
                    ->where('node_type', '=', $fieldName)
                    ->in('field_type', [ 'file', 'image' ])
                    ->fetchAll();

                /* Parcours toutes les sous entités. */
                foreach ($dataRelation as $data) {
                    foreach ($fieldsFile as $file) {
                        $fieldName = $file[ 'field_name' ];
                        /* Parcours ses fichiers pour les copier. */
                        if (isset($data[ $fieldName ])) {
                            $pathFile = $data[ $fieldName ];

                            if (!is_file($pathFile)) {
                                continue;
                            }
                            $upload = new UploadedFile(
                                new Stream(fopen($pathFile, 'r')),
                                self::getBasename($pathFile)
                            );

                            self::file()
                                ->add($upload)
                                ->setPath("/node/$type/$nodeId/{$file['node_type']}")
                                ->isResolvePath()
                                ->isResolveName()
                                ->callMove(function ($key, $name, $move) use (&$data, $fieldName) {
                                    $data[ $fieldName ] = $move;
                                })
                                ->saveOne();
                        }
                    }
                    unset($data[ $value[ 'field_name' ] . '_id' ]);
                    $data[ $options[ 'foreign_key' ] ] = $entityId;
                    self::query()
                        ->insertInto($options[ 'relation_table' ], array_keys($data))
                        ->values($data)
                        ->execute();
                }
            }
        }

        return new Redirect(self::router()->getRoute('node.admin'), 302);
    }

    public static function getBasename($pathFile)
    {
        return strtolower(pathinfo($pathFile, PATHINFO_BASENAME));
    }

    public function getSubmenuNode($keyRoute, $idNode)
    {
        $menu = [
            [
                'key'        => 'node.edit',
                'request'    => self::router()->getRequestByRoute('node.edit', [
                    ':id_node' => $idNode
                ]),
                'title_link' => t('Edit')
            ], [
                'key'        => 'node.delete',
                'request'    => self::router()->getRequestByRoute('node.remove', [
                    ':id_node' => $idNode
                ]),
                'title_link' => t('Delete')
            ]
        ];

        $this->container->callHook('node.submenu', [ &$menu, $idNode ]);

        foreach ($menu as $key => &$link) {
            if ($this->container->callHook('app.granted.request', [ $link[ 'request' ] ])) {
                $link[ 'link' ] = $link[ 'request' ]->getUri();

                continue;
            }

            unset($menu[ $key ]);
        }
        unset($link);

        if ($menu) {
            $nodeShow = [
                'key'        => 'node.show',
                'request'    => self::router()->getRequestByRoute('node.show', [
                    ':id_node' => $idNode
                ]),
                'title_link' => t('View')
            ];
            if ($this->container->callHook('app.granted.request', [ $nodeShow[ 'request' ] ])) {
                $alias     = self::alias()->getAlias('node/' . $idNode, 'node/' . $idNode);
                $pathIndex = self::config()->get('settings.path_index');

                $nodeShow[ 'link' ] = self::router()->makeRoute(
                    in_array($pathIndex, [ $alias, 'node/' . $idNode ])
                        ? ''
                        : $alias
                );

                $menu = array_merge([ $nodeShow ], $menu);
            }
        }

        return [ 'key_route' => $keyRoute, 'menu' => $menu ];
    }

    public function getNodeFieldsetSubmenu()
    {
        $menu = [
            [
                'class'      => 'active',
                'link'       => '#fields-fieldset',
                'title_link' => t('Content')
            ], [
                'class'      => '',
                'link'       => '#publication-fieldset',
                'title_link' => t('Publication')
            ], [
                'class'      => '',
                'link'       => '#user-fieldset',
                'title_link' => t('User')
            ], [
                'class'      => '',
                'link'       => '#seo-fieldset',
                'title_link' => t('SEO')
            ], [
                'class'      => '',
                'link'       => '#url-fieldset',
                'title_link' => t('Url')
            ]
        ];

        $this->container->callHook('node.fieldset.submenu', [ &$menu ]);

        return self::template()
                ->createBlock('node/submenu-node_fieldset.php', $this->pathViews)
                ->addVar('menu', $menu);
    }

    private function getValidator($req, $type, $fields, $idNode = null)
    {
        $node      = self::node()->getCurrentNode($idNode);
        /* Test les champs par defauts de la node. */
        $validator = (new Validator())
            ->setRules([
                'date_created'     => 'required|date_format:Y-m-d H:i',
                'meta_description' => '!required|string|max:512',
                'meta_noarchive'   => 'bool',
                'meta_nofollow'    => 'bool',
                'meta_noindex'     => 'bool',
                'meta_title'       => '!required|string|max:255',
                'node_status_id'   => 'required|numeric|to_int|inarray:1,2,3,4',
                'sticky'           => 'bool',
                'title'            => 'required|string|max:255',
                'user_id'          => '!required|numeric|inarray:' . $this->getListUsersId()
            ])
            ->setLabels([
                'date_created'     => t('Publication date'),
                'meta_description' => t('Description'),
                'meta_noarchive'   => t('Block caching'),
                'meta_nofollow'    => t('Block link tracking'),
                'meta_noindex'     => t('Block indexing'),
                'meta_title'       => t('Title'),
                'node_status_id'   => t('Publication status'),
                'sticky'           => t('Pin content'),
                'title'            => t('Title of the content'),
                'user_id'          => t('User')
            ])
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles())
            ->addInput('type', $type);

        $validator->addRule($node
                ? ('token_node_' . $idNode)
                : 'token_node', 'token:3600');

        /* Test des champs personnalisé de la node. */
        $canPublish = true;
        foreach ($fields as $value) {
            /* Si une node possède une relation requise, elle ne peut-être publié. */
            if (in_array($value[ 'field_type' ], [ 'one_to_many' ])) {
                $rules = self::node()->getRules($value);

                if (empty($rules[ 'required' ])) {
                    continue;
                }

                /* Si la node existe. */
                if ($node) {
                    $options = json_decode($value[ 'field_option' ], true);

                    $entitys = self::query()
                        ->from($options[ 'relation_table' ])
                        ->where($options[ 'foreign_key' ], '==', $node[ 'entity_id' ])
                        ->limit(2)
                        ->fetchAll();

                    $canPublish = count($entitys) >= 1;
                } else {
                    /* Si la node n'existe pas et que les champs multiples sont requis, la node ne peut pas être publié. */
                    $canPublish = false;
                }
            } else {
                $validator
                    ->addRule($value[ 'field_name' ], $value[ 'field_rules' ])
                    ->addLabel($value[ 'field_name' ], t($value[ 'field_label' ]));
            }
        }

        if (!$validator->getInput('date', false)) {
            $validator->addInput('date', date('Y-m-d'));
        }
        if (!$validator->getInput('date_time', false)) {
            $validator->addInput('date_time', date('H:i'));
        }

        $validator
            ->addInput(
                'date_created',
                $validator->getInput('date') . ' ' . $validator->getInput('date_time')
            );

        if ($validator->getInput('node_status_id') == 1) {
            $validator->addRule(
                'date_created',
                'required|date_format:Y-m-d H:i|date_before_or_equal:' . date('Y-m-d H:i')
            );
        }

        /* Ne peut pas publier la node si les règles des relations ne sont pas respectées. */
        if (!$canPublish) {
            $validator->addRule('node_status_id', '!accepted');
        }

        return $validator;
    }

    private function getMeta(array $node, array $fields)
    {
        if (!empty($node[ 'meta_description' ])) {
            $description = $node[ 'meta_description' ];
        } elseif (!empty($fields[ 'summary' ][ 'field_value' ])) {
            $description = $fields[ 'summary' ][ 'field_value' ];
        } elseif (!empty($fields[ 'body' ][ 'field_value' ])) {
            $description = $fields[ 'body' ][ 'field_value' ];
        } else {
            $description = self::config()->get('settings.meta_description');
        }

        $alias = self::alias()->getAlias('node/' . $node[ 'id' ], 'node/' . $node[ 'id' ]);

        $meta = [
            [
                'property' => 'og:title',
                'content'  => $node[ 'title' ]
            ], [
                'property' => 'og:type',
                'content'  => 'website'
            ], [
                'property' => 'og:description',
                'content'  => $this->cleanDescription($description)
            ], [
                'property' => 'og:site_name',
                'content'  => self::config()->get('settings.meta_title')
            ], [
                'property' => 'og:url',
                'content'  => self::router()->makeRoute($alias)
            ],
        ];

        $robots = '';
        if ($node[ 'meta_noindex' ]) {
            $robots .= 'noindex,';
        }
        if ($node[ 'meta_nofollow' ]) {
            $robots .= 'nofollow,';
        }
        if ($node[ 'meta_noarchive' ]) {
            $robots .= 'noarchive,';
        }

        if ($robots) {
            $meta[] = [ 'name' => 'robots', 'content' => substr($robots, 0, -1) ];
        }

        if (!empty($fields[ 'image' ][ 'field_value' ])) {
            $meta[] = [ 'property' => 'og:image', 'content' => $fields[ 'image' ][ 'field_value' ] ];
        } elseif ($logo = self::config()->get('settings.logo')) {
            $meta[] = [ 'property' => 'og:image', 'content' => $logo ];
        }

        return $meta;
    }

    private function cleanDescription($str)
    {
        $str = strip_tags($str);
        $str = htmlentities($str);
        $str = trim($str);
        $str = preg_replace('#[ \n\r\t\v\0]+#', ' ', $str);

        return mb_strcut($str, 0, 200);
    }

    private function updateWeightEntity(array $field, array $data)
    {
        $options = json_decode($field[ 'field_option' ], true);
        if ($options[ 'sort' ] !== 'weight') {
            return;
        }
        foreach ($data as $value) {
            self::query()
                ->update('entity_' . $field[ 'field_name' ], [
                    'weight' => $value[ 'weight' ]
                ])
                ->where($field[ 'field_name' ] . '_id', '==', $value[ 'id' ])
                ->execute();
        }
    }

    private function getListUsersId()
    {
        $usersId = self::query()->from('user')->lists('user_id');

        return implode(',', $usersId);
    }

    private function saveFile($node, $nameField, $validator)
    {
        self::file()
            ->add($validator->getInput($nameField), $validator->getInput("file-$nameField-name"))
            ->setName($nameField)
            ->setPath("/node/{$node[ 'type' ]}/{$node[ 'id' ]}")
            ->isResolvePath()
            ->callGet(function ($key, $name) use ($node) {
                return self::query()
                    ->from('entity_' . $node[ 'type' ])
                    ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
                    ->fetch()[ $key ];
            })
            ->callMove(function ($key, $name, $move) use ($node) {
                self::query()
                ->update('entity_' . $node[ 'type' ], [ $key => $move ])
                ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
                ->execute();
            })
            ->callDelete(function ($key, $name) use ($node) {
                self::query()
                ->update('entity_' . $node[ 'type' ], [ $key => '' ])
                ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
                ->execute();
            })
            ->save();
    }
}
