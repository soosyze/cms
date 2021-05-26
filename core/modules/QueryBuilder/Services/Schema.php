<?php

declare(strict_types=1);

namespace SoosyzeCore\QueryBuilder\Services;

class Schema extends \Queryflatfile\Schema
{
    public function __construct(
        $host = null,
        $name = 'schema',
        \Queryflatfile\DriverInterface $driver = null
    ) {
        parent::__construct($host, $name, $driver);
        $this->root = ROOT;
    }
}
