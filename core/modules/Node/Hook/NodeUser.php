<?php

namespace SoosyzeCore\Node\Hook;

use SoosyzeCore\User\Hook\Config as UserConfig;

class NodeUser
{
    /**
     * @var \Soosyze\Config
     */
    private $config;

    /**
     * @var \SoosyzeCore\Node\Services\Node
     */
    private $node;

    /**
     * @var \SoosyzeCore\Node\Services\NodeUser
     */
    private $nodeuser;

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

    /**
     * @var \SoosyzeCore\Template\Services\Templating
     */
    private $template;

    /**
     * @var \SoosyzeCore\User\Services\User
     */
    private $user;

    public function __construct(
        $config,
        $node,
        $nodeuser,
        $query,
        $router,
        $template,
        $user
    ) {
        $this->config   = $config;
        $this->node     = $node;
        $this->nodeuser = $nodeuser;
        $this->query    = $query;
        $this->router   = $router;
        $this->template = $template;
        $this->user     = $user;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function hookUserShow(array &$contentUser, array $user)
    {
        $nodesQuery = $this->nodeuser->getNodesQuery();

        $this->nodeuser->whereNodes($nodesQuery);

        $nodes = $nodesQuery
            ->where('user_id', '=', $user[ 'user_id' ])
            ->limit(20)
            ->fetchAll();

        $this->nodeuser->hydrateNodesLinks($nodes);

        $requestLinkAdd = $this->router->getRequestByRoute('node.add');

        $contentNothing = 'The user has no content at the moment.';
        if (($userConnected  = $this->user->isConnected()) && $userConnected[ 'user_id' ] == $user[ 'user_id' ]) {
            $contentNothing = 'Your account has no content at the moment.';
        }

        $contentUser[] = $this->template
            ->createBlock('components/user/content_user-nodes.php', $this->pathViews)
            ->addVars([
            'content_nothing' => $contentNothing,
            'link_add'        => $this->user->isGrantedRequest($requestLinkAdd)
                ? $requestLinkAdd->getUri()
                : null,
            'nodes'           => $nodes
        ]);
    }

    public function hookUserDeleteAfter($validator, array $user, $userId)
    {
        $userDelete = $this->config->get('settings.user_delete', UserConfig::DELETE_ACCOUNT);

        if ($userDelete === UserConfig::DELETE_ACCOUNT) {
            $this->query
                ->delete()
                ->from('node')
                ->where('user_id', '==', $userId)
                ->execute();
        } else {
            $nodes = $this->query
                ->from('node')
                ->where('user_id', '==', $userId)
                ->fetchAll();

            foreach ($nodes as $node) {
                self::node()->deleteRelation($node);
                self::node()->deleteFile($node[ 'type' ], $node[ 'id' ]);
            }

            $this->query
                ->update('node', [
                    'user_id' => null
                ])
                ->where('user_id', '==', $userId)
                ->execute();
        }
    }
}
