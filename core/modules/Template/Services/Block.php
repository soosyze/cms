<?php

declare(strict_types=1);

namespace SoosyzeCore\Template\Services;

use Soosyze\Components\Template\Template;

class Block extends Template
{
    public function getBlockWithParent(string $parent): Template
    {
        if ($block = strstr($parent, '.', true)) {
            return $this->getBlock($block)
                    ->getBlock(substr(strstr($parent, '.'), 1));
        }

        return $parent !== 'this'
            ? $this->getBlock($parent)
            : $this;
    }
}
