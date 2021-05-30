<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Controller;

use Psr\Http\Message\ResponseInterface;

class NodeStatus extends \Soosyze\Controller
{
    public function search(): ResponseInterface
    {
        $data = self::query()
            ->from('node_status')
            ->fetchAll();

        $out = [];
        foreach ($data as $value) {
            $out[] = [
                'id'   => $value[ 'node_status_id' ],
                'text' => t($value[ 'node_status_name' ])
            ];
        }

        return $this->json(200, [ 'results' => $out ]);
    }
}
