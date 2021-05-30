<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Paginate\Paginator;
use Soosyze\Components\Validator\Validator;

class NodeManager extends \Soosyze\Controller
{
    /**
     * @var int
     */
    private static $limit = 25;

    /**
     * @var int
     */
    private static $page = 1;

    /**
     * @var bool
     */
    private $admin = false;

    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin(ServerRequestInterface $req): ResponseInterface
    {
        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        $requestNodeAdd = self::router()->getRequestByRoute('node.add');
        $linkAdd        = $this->container->callHook('app.granted.request', [ $requestNodeAdd ])
            ? $requestNodeAdd->getUri()
            : null;

        $this->admin = true;

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-file" aria-hidden="true"></i>',
                    'title_main' => t('Contents')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'node/content-node_manager-admin.php', $this->pathViews, [
                    'action_filter'         => self::router()->getRoute('node.filter'),
                    'link_add'              => $linkAdd,
                    'link_index'            => self::router()->getRoute('node.admin'),
                    'link_search_status'    => self::router()->getRoute('node.status.search'),
                    'link_search_node_type' => self::router()->getRoute('node.type.search'),
                ])
                ->addBlock('content.table', $this->filterPage(1, $req));
    }

    public function filter(ServerRequestInterface $req)
    {
        return $this->filterPage(1, $req);
    }

    public function filterPage(int $page, ServerRequestInterface $req)
    {
        if (!$req->isAjax() && !$this->admin) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'title'          => '!required|string|max:255',
                'types'          => '!required|array',
                'node_status_id' => '!required|array'
            ])
            ->setInputs($req->getQueryParams());

        $query  = self::nodeuser()->getNodesQuery();
        $userId = null;

        if ($user = self::user()->isConnected()) {
            $userId = $user[ 'user_id' ];
        }

        self::nodeuser()
            ->whereNodes($query)
            ->orWhereNodesUser($query, $userId)
            ->orderNodes($query, $req);

        $params = [];
        if ($validator->getInput('title', '')) {
            $params[ 'title' ] = $validator->getInput('title');
            self::nodeuser()->title = $validator->getInput('title');
            $query->where('title', 'ilike', '%' . $validator->getInput('title') . '%');
        }
        if ($validator->getInput('types', [])) {
            $params[ 'types' ] = $validator->getInput('types');
            $query->in('type', $validator->getInput('types'));
        }
        if ($validator->getInput('node_status_id', [])) {
            $params[ 'node_status_id' ] = $validator->getInput('node_status_id');
            $query->in('node_status_id', $validator->getInput('node_status_id'));
        }

        $paramsDateChangedSort = $params;
        $paramsStatusSort      = $params;
        $paramsTitleSort       = $params;
        $paramsTypeSort        = $params;

        /* Met en forme les donnes du tableau. */
        $data      = $query->fetchAll();
        $countData = count($data);
        $nodes     = array_slice($data, self::$limit * ($page - 1), self::$limit);
        unset($data);

        self::nodeuser()->hydrateNodesLinks($nodes);

        list($orderBy, $sort, $sortInverse, $isSortAsc) = $this->getSortParams($req);
        $params[ 'order_by' ] = $orderBy;
        $params[ 'sort' ]     = $sort;

        /* Liens */
        $linkPagination = self::router()->getRequestByRoute('node.filter.page', [], false)->getUri();
        $linkSort       = self::router()->getRequestByRoute('node.filter')->getUri();

        if ($params) {
            $linkPagination = $linkPagination->withQuery(
                self::router()->isRewrite()
                ? http_build_query($params)
                : $linkPagination->getQuery() . '&' . http_build_query($params)
            );
        }

        $parseQuery = [];

        parse_str($linkSort->getQuery(), $parseQuery);
        $route = self::router()->isRewrite()
            ? null
            : $parseQuery[ 'q' ];

        $paramsDateChangedSort += [
            'q'        => $route,
            'order_by' => 'date_changed',
            'sort'     => $sortInverse
        ];

        $paramsStatusSort += [
            'q'        => $route,
            'order_by' => 'node_status_id',
            'sort'     => $sortInverse
        ];

        $paramsTitleSort += [
            'q'        => $route,
            'order_by' => 'title',
            'sort'     => $sortInverse
        ];

        $paramsTypeSort += [
            'q'        => $route,
            'order_by' => 'type',
            'sort'     => $sortInverse
        ];

        return self::template()
                ->createBlock('node/table-node.php', $this->pathViews)
                ->addVars([
                    'count'                  => $countData,
                    'is_admin'               => $this->admin,
                    'is_sort_asc'            => $isSortAsc,
                    'link_date_changed_sort' => $linkSort->withQuery(http_build_query($paramsDateChangedSort)),
                    'link_status_sort'       => $linkSort->withQuery(http_build_query($paramsStatusSort)),
                    'link_title_sort'        => $linkSort->withQuery(http_build_query($paramsTitleSort)),
                    'link_type_sort'         => $linkSort->withQuery(http_build_query($paramsTypeSort)),
                    'nodes'                  => $nodes,
                    'order_by'               => $orderBy,
                    'paginate'               => new Paginator($countData, self::$limit, $page, $linkPagination)
        ]);
    }

    private function getSortParams(ServerRequestInterface $req): array
    {
        $get = $req->getQueryParams();

        $orderBy = !empty($get[ 'order_by' ]) && in_array($get[ 'order_by' ], [
                'date_changed', 'node_status_id', 'title', 'type'
            ])
            ? $get[ 'order_by' ]
            : null;

        $sort = !isset($get[ 'sort' ]) || $get[ 'sort' ] !== 'asc'
            ? 'desc'
            : 'asc';

        $sortInverse = $sort === 'asc'
            ? 'desc'
            : 'asc';

        return [ $orderBy, $sort, $sortInverse, $sort === 'asc' ];
    }
}
