<?php

declare(strict_types=1);

namespace SoosyzeCore\Menu\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MenuApi extends \Soosyze\Controller
{
    public function show(string $name, ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        return $this->json(200, self::menu()->renderMenuSelect($name));
    }
}
