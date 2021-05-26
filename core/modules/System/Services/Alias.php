<?php

declare(strict_types=1);

namespace SoosyzeCore\System\Services;

use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;

class Alias
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Query
     */
    private $query;

    public function __construct(Config $config, Query $query)
    {
        $this->config = $config;
        $this->query  = $query;
    }

    public function getAlias(string $source, ?string $default = null): string
    {
        $alias = $this->query->from('system_alias_url')->where('source', '=', $source)->fetch();

        return $alias['alias'] ?? $default;
    }

    public function getSource(string $alias, ?string $default = null): string
    {
        if ($alias === '/') {
            $index = $this->config[ 'settings.path_index' ];
            $alias = empty($index)
                ? $alias
                : $index;

            $default = $index;
        }

        $source = $this->query->from('system_alias_url')->where('alias', '=', $alias)->fetch();

        return $source[ 'source' ] ?? $default;
    }
}
