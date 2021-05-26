<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Services;

use SoosyzeCore\QueryBuilder\Services\Query;

class Auth
{
    /**
     * @var Query
     */
    private $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Créer la session et les token d'identification.
     *
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function login(string $email, string $password): bool
    {
        if (session_id() == '') {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => true,
            ]);
        }

        if (!($user = $this->attempt($email, $password))) {
            return false;
        }

        $token                         = $this->generateToken();
        $_SESSION[ 'token_connected' ] = $token;
        $this->query
            ->update('user', [ 'token_connected' => $token, 'time_access' => time() ])
            ->where('email', '=', $email)
            ->execute();

        return true;
    }

    public function generateToken(): string
    {
        $now    = new \DateTime();
        $future = new \DateTime('now +2 hours');
        $random = base64_encode(random_bytes(32));

        return hash('sha256', $now->getTimeStamp() . $future->getTimeStamp() . $random);
    }

    /**
     * Attempt to find the user based on email and verify password.
     */
    public function attempt(string $email, string $password): ?array
    {
        if (!($user = $this->getUserActived($email))) {
            return null;
        }

        if ($this->hashVerify($password, $user)) {
            return $user;
        }

        return null;
    }

    public function hashVerify(string $password, array $user): bool
    {
        return password_verify($password, $user[ 'password' ]);
    }

    public function hash(string $password): string
    {
        if ($hash = password_hash($password, PASSWORD_DEFAULT) === false) {
            throw new \Exception();
        }

        return $hash;
    }

    public function getUserActived(string $email, bool $actived = true): array
    {
        return $this->query
                ->from('user')
                ->where('email', '=', $email)
                ->where('actived', '=', $actived)
                ->fetch();
    }

    public function getUserActivedToken(string $token, bool $actived = true): array
    {
        return $this->query
                ->from('user')
                ->where('token_connected', '=', $token)
                ->where('actived', '=', $actived)
                ->fetch();
    }
}
