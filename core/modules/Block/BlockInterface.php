<?php

declare(strict_types=1);

namespace SoosyzeCore\Block;

interface BlockInterface
{
    public function hookBlockCreateFormData(array &$blocks) : void;
}
