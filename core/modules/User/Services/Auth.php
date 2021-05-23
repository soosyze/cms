<?php

namespace SoosyzeCore\User\Services;

class Auth
{
    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    public function __construct($query)
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
    public function login($email, $password)
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

    public function generateToken()
    {
        $now    = new \DateTime();
        $future = new \DateTime('now +2 hours');
        $random = base64_encode(random_bytes(32));

        return hash('sha256', $now->getTimeStamp() . $future->getTimeStamp() . $random);
    }

    /**
     * Attempt to find the user based on email and verify password
     *
     * @param $email
     * @param $password
     *
     * @return bool|array
     */
    public function attempt($email, $password)
    {
        if (!($user = $this->getUserActived($email))) {
            return false;
        }

        if ($this->hashVerify($password, $user)) {
            return $user;
        }

        return false;
    }

    public function hashVerify($password, array $user)
    {
        return password_verify($password, $user[ 'password' ]);
    }

    public function hash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
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
}
