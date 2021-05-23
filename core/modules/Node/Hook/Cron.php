<?php

namespace SoosyzeCore\Node\Hook;

class Cron
{
    /**
     * @var \Soosyze\Config
     */
    private $config;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    public function __construct($config, $query)
    {
        $this->config = $config;
        $this->query  = $query;
    }

    public function hookCron()
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
