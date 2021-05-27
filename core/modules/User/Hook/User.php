<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Config;
use SoosyzeCore\User\Services\User as ServiceUser;

class User implements \SoosyzeCore\User\UserInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ServiceUser
     */
    private $user;

    public function __construct(Config $config, ServiceUser $user)
    {
        $this->config = $config;
        $this->user   = $user;
    }

    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'User' ] = [
            'user.people.manage'     => 'Administer users',
            'user.permission.manage' => 'Administer permissions',
            'user.showed'            => 'View user profiles',
            'user.edited'            => 'Edit your user account',
            'user.deleted'           => 'Delete your user account'
        ];

        $permissions[ 'User role' ][ 'role.all' ] = 'Assign all roles';
        foreach ($this->user->getRolesAttribuable() as $role) {
            $permissions[ 'User role' ][ 'role.' . $role[ 'role_id' ] ] = [
                'name' => 'Assign the role :name',
                'attr' => [ ':name' => $role[ 'role_label' ] ]
            ];
        }
    }

    public function hookPermissionAdminister(): string
    {
        return 'user.permission.manage';
    }

    public function hookPeopleAdminister(): string
    {
        return 'user.people.manage';
    }

    /**
     * @return array|bool
     */
    public function hookUserShow(
        int $id,
        ServerRequestInterface $req,
        ?array $user
    ) {
        if ($id == $user[ 'user_id' ]) {
            return true;
        }

        return [ 'user.people.manage', 'user.showed' ];
    }

    public function hookUserEdited(
        int $id,
        ServerRequestInterface $req,
        ?array $user
    ): array {
        $output[] = 'user.people.manage';
        if ($id == $user[ 'user_id' ]) {
            $output[] = 'user.edited';
        }

        return $output;
    }

    public function hookUserDeleted(
        int $id,
        ServerRequestInterface $req,
        ?array $user
    ): array {
        $output[] = 'user.people.manage';
        if ($id == $user[ 'user_id' ]) {
            $output[] = 'user.deleted';
        }

        return $output;
    }

    public function hookRegister(ServerRequestInterface $req, ?array $user): bool
    {
        return empty($user) && $this->config->get('settings.user_register');
    }

    public function hookActivate(
        int $id,
        string $token,
        ServerRequestInterface $req,
        ?array $user
    ): bool {
        return empty($user) && $this->config->get('settings.user_register');
    }

    public function hookLogin(
        string $url,
        ServerRequestInterface $req,
        ?array $user
    ): bool {
        return $this->user->isConnectUrl($url)
            ? false
            : empty($user);
    }

    public function hookLoginCheck(
        string $url,
        ?ServerRequestInterface $req,
        ?array $user
    ): bool {
        if ($this->user->isConnectUrl($url)) {
            return false;
        }
        /* Si le site est en maintenance. */
        if (!$this->config->get('settings.maintenance')) {
            return empty($user);
        }
        /* Et que l'utilisateur qui se connect existe. */
        $post = $req->getParsedBody();
        if (!isset($post[ 'email' ]) || !filter_var($post[ 'email' ], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!($userActived = $this->user->getUserActived($post[ 'email' ]))) {
            return false;
        }
        /* Si l'utilisateur à le droit de se connecter en mode maintenance. */
        return $this->user->getGranted($userActived, 'system.config.maintenance');
    }

    public function hookLogout(?ServerRequestInterface $req, ?array $user): bool
    {
        return !empty($user);
    }

    public function hookRelogin(
        string $url,
        ServerRequestInterface $req,
        ?array $user
    ): bool {
        if ($this->user->isConnectUrl($url)) {
            return false;
        }

        return empty($user) && $this->config->get('settings.user_relogin');
    }

    public function hookRoleDeleted(int $idRole): ?string
    {
        /* Si le role est requis par le système, alors la suppression est interdite. */
        if (in_array($idRole, [ 1, 2, 3 ])) {
            return null;
        }

        return 'user.people.manage';
    }

    public function hookUserApiSelect(?ServerRequestInterface $req, ?array $user): bool
    {
        return !empty($user);
    }
}
