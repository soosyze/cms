<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Paginate\Paginator;
use Soosyze\Components\Util\Util;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\Template\Services\Block;

class UsersManager extends \Soosyze\Controller
{
    /**
     * @var int
     */
    private static $limit = 20;

    /**
     * @var int
     */
    private static $page = 1;

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * @var string
     */
    private $username = '';

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

        $this->isAdmin = true;

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Administer users')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', self::user()->getUserManagerSubmenu('user.admin'))
                ->make('page.content', 'user/content-user_manager-admin.php', $this->pathViews, [
                    'link_create_user' => self::router()->getRoute('user.create'),
                    'link_filter_user' => self::router()->getRoute('user.filter'),
                    'link_user_admin'  => self::router()->getRoute('user.admin')
                ])
                ->addBlock('content.table', $this->filterPage(1, $req));
    }

    /**
     * @return Block|ResponseInterface
     */
    public function filter(ServerRequestInterface $req)
    {
        return $this->filterPage(1, $req);
    }

    /**
     * @return Block|ResponseInterface
     */
    public function filterPage(int $page, ServerRequestInterface $req)
    {
        if (!$req->isAjax() && !$this->isAdmin) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'actived'   => '!required|between_numeric:0,1',
                'firstname' => '!required|string|max:255',
                'name'      => '!required|string|max:255',
                'username'  => '!required|string|max:255',
            ])
            ->setInputs($req->getQueryParams());

        self::query()->from('user');

        $params = [];
        if (in_array($validator->getInput('actived'), [ '0', 0, '1', 1 ], true)) {
            $params[ 'actived' ] = $validator->getInput('actived');
            self::query()->where('actived', '=', (bool) $validator->getInput('actived'));
        }
        self::query()->where(function ($query) use ($validator, &$params) {
            if ($validator->getInput('firstname', '')) {
                $params[ 'firstname' ] = $validator->getInput('firstname');
                $query->orWhere('firstname', 'ilike', '%' . $validator->getInput('firstname') . '%');
            }
            if ($validator->getInput('name', '')) {
                $params[ 'name' ] = $validator->getInput('name');
                $query->orWhere('name', 'ilike', '%' . $validator->getInput('name') . '%');
            }
            if ($validator->getInput('username', '')) {
                $params[ 'username' ] = $validator->getInput('username');
                $this->username       = $validator->getInput('username');
                $query->orWhere('username', 'ilike', '%' . $validator->getInput('username') . '%');
            }
        });

        $paramsActivedSort       = $params;
        $paramsUsernameSort      = $params;
        $paramsTimeAccessSort    = $params;
        $paramsTimeInstalledSort = $params;

        $this->sortUser(self::query(), $req);

        $data = self::query()->fetchAll();

        $countData = count($data);
        $users     = array_slice($data, self::$limit * ($page - 1), self::$limit);

        $this->hydrateUsersLinks($users);

        list($orderBy, $sort, $sortInverse, $isSortAsc) = $this->getSortParams($req);
        $params[ 'order_by' ] = $orderBy;
        $params[ 'sort' ]     = $sort;

        /* Liens */
        $linkPagination = self::router()->getRequestByRoute('user.filter.page', [], false)->getUri();
        $linkSort       = self::router()->getRequestByRoute('user.filter')->getUri();

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

        $paramsActivedSort += [
            'q'        => $route,
            'order_by' => 'actived',
            'sort'     => $sortInverse
        ];

        $paramsTimeAccessSort += [
            'q'        => $route,
            'order_by' => 'time_access',
            'sort'     => $sortInverse
        ];

        $paramsTimeInstalledSort += [
            'q'        => $route,
            'order_by' => 'time_installed',
            'sort'     => $sortInverse
        ];

        $paramsUsernameSort += [
            'q'        => $route,
            'order_by' => 'username',
            'sort'     => $sortInverse
        ];

        return self::template()
                ->createBlock('user/table-user.php', $this->pathViews)
                ->addVars([
                    'count'                    => $countData,
                    'is_sort_asc'              => $isSortAsc,
                    'link_actived_sort'        => $linkSort->withQuery(http_build_query($paramsActivedSort)),
                    'link_username_sort'       => $linkSort->withQuery(http_build_query($paramsUsernameSort)),
                    'link_time_access_sort'    => $linkSort->withQuery(http_build_query($paramsTimeAccessSort)),
                    'link_time_installed_sort' => $linkSort->withQuery(http_build_query($paramsTimeInstalledSort)),
                    'order_by'                 => $orderBy,
                    'paginate'                 => new Paginator($countData, self::$limit, $page, $linkPagination),
                    'users'                    => $users
        ]);
    }

    private function sortUser(Query $users, ServerRequestInterface $req): Query
    {
        $get = $req->getQueryParams();

        if (in_array($get[ 'order_by' ] ?? [], [
                'actived', 'time_access', 'time_installed', 'username'
            ])) {
            $sort = !isset($get[ 'sort' ]) || $get[ 'sort' ] !== 'asc'
                ? SORT_DESC
                : SORT_ASC;

            return $users->orderBy($get[ 'order_by' ], $sort);
        }

        return $users->orderBy('time_access', SORT_DESC);
    }

    private function hydrateUsersLinks(array &$users): void
    {
        foreach ($users as &$user) {
            $user[ 'link_show' ]   = self::router()->getRoute('user.show', [
                ':id' => $user[ 'user_id' ]
            ]);
            $user[ 'link_edit' ]   = self::router()->getRoute('user.edit', [
                ':id' => $user[ 'user_id' ]
            ]);
            $user[ 'link_remove' ] = self::router()->getRoute('user.remove', [
                ':id' => $user[ 'user_id' ]
            ]);
            $user[ 'roles' ]       = self::user()->getRolesUser($user[ 'user_id' ]);
            $user[ 'username' ]    = Util::strHighlight($this->username, $user[ 'username' ]);
        }
        unset($user);
    }

    private function getSortParams(ServerRequestInterface $req): array
    {
        $get = $req->getQueryParams();

        $orderBy = !empty($get[ 'order_by' ]) && in_array($get[ 'order_by' ], [
                'actived', 'time_access', 'time_installed', 'username'
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
