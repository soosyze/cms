<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Hook;

use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;

class Cron
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

    public function hookCron(): void
    {
        if ($this->config->get('settings.node_cron', false)) {
            $this->query
                ->update('node', [ 'node_status_id' => 1 ])
                ->where('node_status_id', '=', 2)
                ->where('date_created', '<=', time())
                ->execute();
        }
    }
}
