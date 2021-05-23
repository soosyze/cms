<?php

declare(strict_types=1);

namespace SoosyzeCore\System;

interface ToolInterface
{
    public function hookToolAdmin(array &$tools): void;
}
