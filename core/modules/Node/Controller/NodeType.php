<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Controller;

use Psr\Http\Message\ResponseInterface;

class NodeType extends \Soosyze\Controller
{
    public function search(): ResponseInterface
    {
        $data = self::query()
            ->from('node_type')
            ->fetchAll();

        $out = [];
        foreach ($data as $value) {
            $out[] = [
                'id'   => $value[ 'node_type' ],
                'text' => t($value[ 'node_type_name' ])
            ];
        }

        return $this->json(200, [ 'results' => $out ]);
    }
}
