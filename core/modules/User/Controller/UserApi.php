<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserApi extends \Soosyze\Controller
{
    public function select(ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $data = self::query()
            ->from('user')
            ->fetchAll();

        $out = [];
        foreach ($data as $value) {
            $out[] = [
                'id'   => $value[ 'user_id' ],
                'text' => t($value[ 'username' ])
            ];
        }

        return $this->json(200, [ 'results' => $out ]);
    }
}
