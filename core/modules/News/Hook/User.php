<?php

declare(strict_types=1);

namespace SoosyzeCore\News\Hook;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
    }

    public function hookNewShow(): array
    {
        return [ 'node.administer', 'node.show.published', 'node.show.published.article' ];
    }
}
