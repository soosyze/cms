<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Hook;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Router\Router;

class AppInstall
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function hook404(RequestInterface $request, ResponseInterface &$response): void
    {
        $response = new Redirect($this->router->getRoute('install.index'));
    }
}
