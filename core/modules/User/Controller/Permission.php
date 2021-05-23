<?php

namespace SoosyzeCore\User\Controller;

use Soosyze\Components\Http\Redirect;

class Permission extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin($req)
    {
        /* Récupère toutes les permissions par module. */
        $modules = [];
        $this->container->callHook('user.permission.module', [ &$modules ]);
        ksort($modules);

        /* Récupére les pérmissions et role en base de données. */
        $permissionsByRole = self::query()
            ->select('permission_id', 'role_id')
            ->from('role')
            ->leftJoin('role_permission', 'role_id', '=', 'role_permission.role_id')
            ->fetchAll();

        $fetchRoles = self::query()->from('role')->orderBy('role_weight')->fetchAll();

        $roles = array_combine(array_column($fetchRoles, 'role_id'), $fetchRoles);

        /* Simplifie les permissions par roles. */
        $tmp = [];
        foreach ($permissionsByRole as $value) {
            $tmp[ $value[ 'permission_id' ] ][ $value[ 'role_id' ] ] = '';
        }
        $permissionsByRole = $tmp;

        /* Met en forme les droit utilisateurs. */
        $output = [];
        $count  = 0;
        foreach ($modules as $keyModule => $module) {
            foreach ($module as $keyPermission => $permission) {
                $output[ $keyModule ][ $keyPermission ][ 'name' ] = isset($permission[ 'name' ])
                    ? t($permission[ 'name' ], isset($permission['attr']) ? $permission['attr'] : [])
                    : $permission;
                foreach ($roles as $role) {
                    $output[ $keyModule ][ $keyPermission ][ 'roles' ][ $role[ 'role_id' ] ] =
                        isset($permissionsByRole[ $keyPermission ][ $role[ 'role_id' ] ])
                            ? 'checked'
                            : '';
                }
                ++$count;
            }
        }

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Administer permissions')
                ])
                ->view('page.messages', $messages)
                ->view('page.submenu', self::user()->getUserManagerSubmenu('user.permission.admin'))
                ->make('page.content', 'user/content-permission-admin.php', $this->pathViews, [
                    'count'       => $count,
                    'link_update' => self::router()->getRoute('user.permission.update'),
                    'modules'     => $output,
                    'roles'       => $roles
                ]);
    }

    public function udpate($req)
    {
        $post = $req->getParsedBody();

        $rolesId           = self::query()->from('role')->lists('role_id');
        $permissionsByRole = self::query()->from('role_permission')->fetchAll();

        foreach ($rolesId as $id) {
            $perm[ $id ] = [];
            if (empty($post[ $id ])) {
                $post[ $id ] = [];
            }
            foreach ($permissionsByRole as $permission) {
                $perm[ $permission[ 'role_id' ] ][ $permission[ 'permission_id' ] ] = $permission[ 'permission_id' ];
            }
            $this->storePermission($id, $perm[ $id ], $post[ $id ]);
            $this->deletePermission($id, $perm[ $id ], $post[ $id ]);
        }
        $_SESSION[ 'messages' ][ 'success' ] = [ t('Saved configuration') ];
        $route                               = self::router()->getRoute('user.permission.admin');

        return new Redirect($route);
    }

    private function storePermission(
        $idRole,
        array $permission,
        array $newPermission
    ) {
        if (!($diffCreate = array_diff_key($newPermission, $permission))) {
            return null;
        }

        self::query()->insertInto('role_permission', [ 'role_id', 'permission_id' ]);
        foreach ($diffCreate as $create) {
            if (self::user()->hasPermission($create)) {
                self::query()->values([ $idRole, $create ]);
            }
        }
        self::query()->execute();
    }

    private function deletePermission(
        $idRole,
        array $permission,
        array $newPermission
    ) {
        if (!($diffDelete = array_diff_key($permission, $newPermission))) {
            return null;
        }

        self::query()->from('role_permission')->delete()
            ->where('role_id', '==', $idRole)
            ->where(static function ($query) use ($diffDelete) {
                foreach ($diffDelete as $delete) {
                    $query->orWhere('permission_id', '==', $delete);
                }
            })
            ->execute();
    }
}
