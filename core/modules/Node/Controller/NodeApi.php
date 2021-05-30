<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\Node\Form\FormNodeDelete;

class NodeApi extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function remove(int $idNode, ServerRequestInterface $req): ResponseInterface
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

        $action = self::router()->getRoute('node.api.delete', [ ':id_node' => $idNode ]);

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
                ->createBlock('node/modal-form.php', $this->pathViews)
                ->addVars([
                    'form'  => $form,
                    'title' => t('Delete :name content', [ ':name' => $node[ 'title' ] ])
        ]);
    }

    public function delete(int $idNode, ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }
        if (!($node = self::node()->byId($idNode))) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'id'    => 'required',
                'files' => 'bool'
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

        $out = [];
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

            if ($validator->getInput('path')) {
                self::config()->set($validator->getInput('path_key'), $validator->getInput('path'));
            }

            $out[ 'messages' ][ 'success' ] = [
                t('Content :title has been deleted', [ ':title' => $node[ 'title' ] ])
            ];

            return $this->json(200, $out);
        }

        $out[ 'inputs' ]               = $validator->getInputs();
        $out[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $out[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return $this->json(400, $out);
    }
}
