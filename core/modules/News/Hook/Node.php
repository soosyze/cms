<?php

declare(strict_types=1);

namespace SoosyzeCore\News\Hook;

use Soosyze\Components\Validator\Validator;
use Soosyze\Config;
use SoosyzeCore\Template\Services\Templating;

class Node
{
    const NODE_TYPE = 'article';

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function hookNodeShowTpl(Templating $tpl, array $node, int $idNode): void
    {
        if ($node[ 'type' ] === self::NODE_TYPE) {
            $tpl->getBlock('page.content')->addPathOverride(dirname(__DIR__) . '/Views/');
        }
    }

    public function hookNodeStoreBefore(Validator $validator, array &$fieldsInsert, string $type): void
    {
        if ($type === self::NODE_TYPE) {
            $words = str_word_count(strip_tags($fieldsInsert[ 'body' ]));

            $fieldsInsert[ 'reading_time' ] = ceil($words / 200);
        }
    }

    public function hookNodeUpdateBefore(
        Validator $validator,
        array &$fieldsUpdate,
        array $node,
        int $idNode
    ): void {
        if ($node[ 'type' ] === self::NODE_TYPE) {
            $words = str_word_count(strip_tags($fieldsUpdate[ 'body' ]));

            $fieldsUpdate[ 'reading_time' ] = ceil($words / 200);
        }
    }

    public function hookNodeMakefields(string $type, array &$fields, array &$data): void
    {
        if ($type === self::NODE_TYPE && empty($data[ 'image' ])) {
            $fields[] = [
                'field_name' => 'icon',
                'field_type' => 'text'
            ];

            $data[ 'image' ] = $this->config->get('settings.new_default_image', null);
            $data[ 'icon' ]  = $this->config->get('settings.new_default_icon', null);
        }
    }

    public function hookNodeFormData(array &$content, string $type): void
    {
        if ($type === self::NODE_TYPE && empty($content[ 'image' ])) {
            $content[ 'image' ] = $this->config->get('settings.new_default_image', '');
        }
    }
}
