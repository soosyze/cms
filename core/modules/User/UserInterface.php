<?php

declare(strict_types=1);

namespace SoosyzeCore\User;

interface UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void;
}
