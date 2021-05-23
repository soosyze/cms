<?php

namespace SoosyzeCore\User\Services;

use Psr\Http\Message\RequestInterface;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Http\Stream;
use SoosyzeCore\Template\Services\Templating;

class User
{
    /**
     * Les données utilisateur courant ou false.
     *
     * @var bool|array
     */
    private $connect = false;

    /**
     * @var \Soosyze\App
     */
    private $core;

    /**
     * La liste des permissions pour l'utilisateur courant.
     * @var array
     */
    private $granted     = [];

    /**
     * La liste des permissions
     * @var array
     */
    private $permissions = [];

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    /**
     * @var \Soosyze\Components\Router\Router
     */
    private $router;

    public function __construct($core, $query, $router)
    {
        $this->core   = $core;
        $this->query  = $query;
        $this->router = $router;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function find($id)
    {
        return $this->query
                ->from('user')
                ->where('user_id', '==', $id)
                ->fetch();
    }

    public function findActived($id, $actived = true)
    {
        return $this->query
                ->from('user')
                ->where('user_id', '==', $id)
                ->where('actived', '=', $actived)
                ->fetch();
    }

    public function getUser($email)
    {
        return $this->query
                ->from('user')
                ->where('email', '=', $email)
                ->fetch();
    }

    public function getUserByUsername($username)
    {
        return $this->query
                ->from('user')
                ->where('username', '=', $username)
                ->fetch();
    }

    public function getUserActived($email, $actived = true)
    {
        return $this->query
                ->from('user')
                ->where('email', '=', $email)
                ->where('actived', '=', $actived)
                ->fetch();
    }

    public function getUserActivedToken($token, $actived = true)
    {
        return $this->query
                ->from('user')
                ->where('token_connected', '=', $token)
                ->where('actived', '=', $actived)
                ->fetch();
    }

    public function getUsers()
    {
        return $this->query->from('user')->fetchAll();
    }

    public function getRolesUser($idUser)
    {
        return $this->query
                ->from('user_role')
                ->leftJoin('role', 'role_id', '=', 'role.role_id')
                ->where('user_id', '==', $idUser)
                ->fetchAll();
    }

    public function getRoles()
    {
        return $this->query->from('role')->fetchAll();
    }

    public function getRolesAttribuable()
    {
        return $this->query
            ->from('role')
            ->where('role_id', '>', 2)
            ->orderBy('role_weight')
            ->fetchAll();
    }

    public function getIdRolesUser($idUser)
    {
        $data = $this->getRolesUser($idUser);

        $out  = [];
        foreach ($data as $value) {
            $out[ $value[ 'role_id' ] ] = $value[ 'role_label' ];
        }

        return $out;
    }

    public function getUserSubmenu($keyRoute, $id)
    {
        $menu = [
            [
                'key'        => 'user.show',
                'request'    => $this->router->getRequestByRoute('user.show', [
                    ':id' => $id
                ]),
                'title_link' => t('View')
            ], [
                'key'        => 'user.edit',
                'request'    => $this->router->getRequestByRoute('user.edit', [
                    ':id' => $id
                ]),
                'title_link' => t('Edit')
            ], [
                'key'        => 'user.remove',
                'request'    => $this->router->getRequestByRoute('user.remove', [
                    ':id' => $id
                ]),
                'title_link' => t('Delete')
            ]
        ];

        $this->core->callHook('user.submenu', [ &$menu, $id ]);

        foreach ($menu as $key => &$link) {
            if (!$this->isGrantedRequest($link[ 'request' ])) {
                unset($menu[ $key ]);

                continue;
            }
            $link[ 'link' ] = $link[ 'request' ]->getUri();
        }
        unset($link);

        return [
            'key_route' => $keyRoute,
            'menu'      => count($menu) === 1
                ? []
                : $menu
        ];
    }

    public function getUserManagerSubmenu($keyRoute)
    {
        $menu = [
            [
                'key'        => 'user.admin',
                'request'    => $this->router->getRequestByRoute('user.admin'),
                'title_link' => t('Users')
            ], [
                'key'        => 'user.role.admin',
                'request'    => $this->router->getRequestByRoute('user.role.admin'),
                'title_link' => t('Roles')
            ], [
                'key'        => 'user.permission.admin',
                'request'    => $this->router->getRequestByRoute('user.permission.admin'),
                'title_link' => t('Permissions')
            ]
        ];

        $this->core->callHook('user.manager.submenu', [ &$menu ]);

        foreach ($menu as $key => &$link) {
            if (!$this->isGrantedRequest($link[ 'request' ])) {
                unset($menu[ $key ]);

                continue;
            }
            $link[ 'link' ] = $link[ 'request' ]->getUri();
        }
        unset($link);

        return [ 'key_route' => $keyRoute, 'menu' => $menu ];
    }

    public function hasPermission($idPermission)
    {
        if (!empty($this->permissions)) {
            return isset($this->permissions[ $idPermission ]);
        }

        $permission = [];
        $this->core->callHook('user.permission.module', [ &$permission ]);
        foreach ($permission as $value) {
            $this->permissions += $value;
        }

        return isset($this->permissions[ $idPermission ]);
    }

    public function getGranted($user, $idPermission)
    {
        if (!empty($this->granted)) {
            return in_array($idPermission, $this->granted);
        }

        $this->granted = $this->query
            ->from('user_role')
            ->leftJoin('role', 'role_id', '=', 'role.role_id')
            ->leftJoin('role_permission', 'role_id', '=', 'role_permission.role_id')
            ->where('user_id', '=', $user[ 'user_id' ])
            ->lists('permission_id');
        $this->granted = array_merge($this->granted, $this->query
            ->from('role_permission')
            ->where('role_id', '=', 2)
            ->lists('permission_id'));

        return in_array($idPermission, $this->granted);
    }

    public function getGrantedAnonymous($idPermission)
    {
        if (!empty($this->granted)) {
            return in_array($idPermission, $this->granted);
        }

        $this->granted = $this->query->from('role_permission')
            ->where('role_id', '=', 1)
            ->lists('permission_id');

        return in_array($idPermission, $this->granted);
    }

    /**
     * Si la session existe renvoie l'utilisateur,
     * sinon s'il y a correspondance dans les autres cas renvoie faux.
     *
     * @return bool|array
     */
    public function isConnected()
    {
        if ($this->connect) {
            return $this->connect;
        }
        if (!empty($_SESSION[ 'token_connected' ])) {
            if (!($user = $this->getUserActivedToken($_SESSION[ 'token_connected' ]))) {
                return false;
            }

            $this->connect = $_SESSION[ 'token_connected' ] == $user[ 'token_connected' ]
                ? $user
                : false;

            return $this->connect;
        }

        return false;
    }

    public function isConnectUrl($url)
    {
        $connectUrl = $this->core->get('config')->get('settings.connect_url', '');

        return !empty($connectUrl) && $url !== '/' . $connectUrl;
    }

    public function passwordPolicy()
    {
        if (($length = (int) $this->core->get('config')->get('settings.password_length', 8)) < 8) {
            $length = 8;
        }
        if (($upper = (int) $this->core->get('config')->get('settings.password_upper', 1)) < 1) {
            $upper = 1;
        }
        if (($digit = (int) $this->core->get('config')->get('settings.password_digit', 1)) < 1) {
            $digit = 1;
        }
        if (($special = (int) $this->core->get('config')->get('settings.password_special', 1)) < 1) {
            $special = 1;
        }

        return '/(?=.*\d){' . $digit . ',}(?=.*[a-z])(?=.*\W){' . $special . ',}(?=.*[A-Z]){' . $upper . ',}.{' . $length . ',}/';
    }

    /**
     * Vérifie les droits d'accès aux contrôleurs.
     *
     * @param type $key
     * @param type $grant
     *
     * @return type
     */
    public function isGranted($key, &$grant = false)
    {
        /* Si la permission n'existe pas. */
        if (!$this->hasPermission($key)) {
            $grant = false;
        }
        /* Si l'utilisateur et connecté. */
        elseif ($user = $this->isConnected()) {
            $grant = (bool) $this->getGranted($user, $key);
        }
        /* Si l'utilisateur annonyme peut voir la route. */
        else {
            $grant = (bool) $this->getGrantedAnonymous($key);
        }

        return $grant;
    }

    public function isGrantedRequest(RequestInterface $request)
    {
        $route = $this->router->parse($request);

        /* Si la permission n'existe pas. */
        if ($this->hasPermission($route[ 'key' ])) {
            return $this->isGranted($route[ 'key' ]);
        }

        if (isset($route[ 'with' ])) {
            $query  = $this->router->parseQueryFromRequest($request);
            $params = $this->router->parseParam($route[ 'path' ], $query, $route[ 'with' ]);
        }

        $params[]    = $request;
        $params[]    = $this->isConnected();
        $permissions = $this->core->callHook('route.' . $route[ 'key' ], $params);

        return $this->isGrantedPermission($permissions);
    }

    public function isGrantedPermission($permissions)
    {
        if (\is_bool($permissions)) {
            return $permissions;
        }
        if (\is_string($permissions)) {
            return $this->isGranted($permissions);
        }
        foreach ($permissions as $permission) {
            if ($this->isGranted($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fonctionnement par défaut de l'application.
     * Défini les règles du déclenchement d'un retour 403 à l'aide des hooks.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function hookResponseBefore(RequestInterface &$request, &$response)
    {
        if (!$this->isGrantedRequest($request)) {
            $response = new Response(403, new Stream('Error HTTP 403 Forbidden'));
        }
    }

    public function hookResponseAfter(RequestInterface $request, &$response)
    {
        if (!($response instanceof Templating)) {
            return;
        }

        $vendor = $this->core->getPath('modules', 'modules/core', false);

        $response->addScript('user', "$vendor/User/Assets/js/user.js");
    }
}
