<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Services;

use Soosyze\App;
use Soosyze\Config;
use SoosyzeCore\QueryBuilder\Services\Query;
use SoosyzeCore\QueryBuilder\Services\Schema;
use SoosyzeCore\Template\Services\Templating;

class Node
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var App
     */
    private $core;

    /**
     * La liste des champs de bases fournient par le module.
     *
     * @var string[]
     */
    private $fieldsCore = [
        'body',
        'image',
        'summary',
        'reading_time',
        'weight'
    ];

    /**
     * Les données du contenu courant.
     *
     * @var array|null
     */
    private $nodeCurrent = null;

    /**
     * Données permettant de créer l'affichage d'un contenu.
     *
     * @var array
     */
    private $nodeTypeField = [];

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Templating
     */
    private $tpl;

    public function __construct(Config $config, App $core, Query $query, Schema $schema, Templating $tpl)
    {
        $this->config = $config;
        $this->core   = $core;
        $this->query  = $query;
        $this->schema = $schema;
        $this->tpl    = $tpl;

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function makeFieldsByData(string $type, array $data): array
    {
        $out = [];
        foreach ($data as $value) {
            $out[] = $this->makeFieldsById($type, $value[ 'node_id' ]);
        }

        return $out;
    }

    public function makeFieldsById(string $entity, int $idEntity): array
    {
        $data         = $this->getEntity($entity, $idEntity);
        $data[ 'id' ] = $idEntity;

        return $this->makeFields($entity, $this->getFields($entity), $data);
    }

    public function makeFieldsByEntity(string $entity, array $data, array $options): array
    {
        $this->query
            ->from('entity_' . $entity)
            ->where($options[ 'foreign_key' ], '==', $data[ $options[ 'foreign_key' ] ]);

        if (isset($options[ 'order_by' ])) {
            $this->query->orderBy(
                $options[ 'order_by' ],
                $options[ 'sort' ] === 'weight'
                    ? SORT_ASC
                    : ($options[ 'sort' ] === 'desc' || $options[ 'sort' ] === SORT_ASC
                        ? SORT_ASC
                        : SORT_DESC)
            );
        }

        $data   = $this->query->fetchAll();
        $fields = $this->getFields($entity);
        $out    = [];

        foreach ($data as $value) {
            $out[] = $this->makeFields($entity, $fields, $value);
        }

        return $out;
    }

    public function getFields(string $type): array
    {
        if (isset($this->nodeTypeField[ $type ])) {
            return $this->nodeTypeField[ $type ];
        }

        $this->nodeTypeField[ $type ] = $this->query
            ->select('field_name', 'field_type', 'field_label', 'field_show_label', 'field_option', 'field_weight')
            ->from('node_type_field')
            ->leftJoin('field', 'field_id', '=', 'field.field_id')
            ->where('node_type', '=', $type)
            ->where('field_show', '=', true)
            ->orderby('field_weight')
            ->fetchAll();

        return $this->nodeTypeField[ $type ];
    }

    public function getCurrentNode(?int $idNode = null): ?array
    {
        if (!$this->nodeCurrent && $idNode !== null) {
            $this->nodeCurrent = $this->byId($idNode);
        }

        return $this->nodeCurrent;
    }

    public function byId(int $idNode): array
    {
        return $this->query
                ->from('node')
                ->where('id', '==', $idNode)
                ->fetch();
    }

    public function getEntity(string $entity, int $idEntity): array
    {
        return $this->query
                ->from('entity_' . $entity)
                ->where($entity . '_id', '==', $idEntity)
                ->fetch();
    }

    public function getFieldRelationByEntity(string $entity): array
    {
        return $this->query
                ->from('node_type_field')
                ->leftJoin('field', 'field_id', '=', 'field.field_id')
                ->where('field_name', '=', $entity)
                ->where('field_type', '=', 'one_to_many')
                ->fetch();
    }

    public function getFieldsForm(string $type): array
    {
        return $this->query
                ->from('node_type')
                ->leftJoin('node_type_field', 'node_type', '=', 'node_type_field.node_type')
                ->leftJoin('field', 'field_id', '=', 'field.field_id')
                ->where('field_show_form', '=', true)
                ->where('node_type', '=', $type)
                ->fetchAll();
    }

    public function getFieldsDisplay(string $type): array
    {
        return $this->query
                ->from('node_type')
                ->leftJoin('node_type_field', 'node_type', '=', 'node_type_field.node_type')
                ->leftJoin('field', 'field_id', '=', 'field.field_id')
                ->where('node_type', '=', $type)
                ->where('field_show_form', '=', true)
                ->orderBy('field_weight')
                ->fetchAll();
    }

    public function getFieldsEntity(string $entity): array
    {
        return $this->getNodeTypeFieldsQuery($entity)
                ->where('field_show_form', '=', true)
                ->orderBy('field_weight')
                ->fetchAll();
    }

    public function deleteAliasByType(string $nodeType): void
    {
        $nodes = $this->query
            ->from('node')
            ->where('type', '=', $nodeType)
            ->fetchAll();

        if (!empty($nodes)) {
            $this->query
                ->from('system_alias_url')
                ->delete();

            foreach ($nodes as $node) {
                $this->query->orWhere('source', '=', 'node/' . $node[ 'id' ]);
            }

            $this->query->execute();
        }
    }

    public function deleteByType(string $nodeType): void
    {
        $nodeTypeFields = $this->getNodeTypeFieldsQuery($nodeType)->fetchAll();

        foreach ($nodeTypeFields as $nodeTypeField) {
            if ($nodeTypeField[ 'field_type' ] === 'one_to_many') {
                $this->deleteByType($nodeTypeField[ 'field_name' ]);
            }

            /* Supprime la relation entre le champs et l'entité. */
            $this->query
                ->from('node_type_field')
                ->delete()
                ->where('node_type', '=', $nodeType)
                ->execute();

            if (in_array($nodeTypeField[ 'field_name' ], $this->fieldsCore)) {
                continue;
            }

            $isUseOtherTable = $this->query
                ->from('node_type_field')
                ->where('field_id', '=', $nodeTypeField[ 'field_id' ])
                ->where('node_type', '=', $nodeType)
                ->fetch();

            /* Si le champ n'est pas utiliser dans une autre entityé, il est supprimé. */
            if (empty($isUseOtherTable)) {
                $this->query
                    ->from('field')
                    ->delete()
                    ->where('field_id', '=', $nodeTypeField[ 'field_id' ])
                    ->execute();
            }
        }

        /* Supprimes les contenus. */
        $this->query
            ->from('node')
            ->delete()
            ->where('type', '=', $nodeType)
            ->execute();

        /* Supprime le type de contenu. */
        $this->query
            ->from('node_type')
            ->delete()
            ->where('node_type', '=', $nodeType)
            ->execute();

        /* Supprime la table de l'entité principal. */
        $this->schema->dropTableIfExists("entity_$nodeType");
    }

    public function deleteRelation(array $node): void
    {
        /* Suppression des relations */
        $entity = $this->getEntity($node[ 'type' ], $node[ 'entity_id' ]);

        $relationNode = $this->getNodeTypeFieldsQuery($node[ 'type' ])
            ->where('field_type', '=', 'one_to_many')
            ->fetchAll();
        foreach ($relationNode as $relation) {
            $options = json_decode($relation[ 'field_option' ], true);
            $this->query
                ->from($options[ 'relation_table' ])
                ->delete()
                ->where($options[ 'foreign_key' ], '=', $entity[ $options[ 'local_key' ] ])
                ->execute();
        }

        /* Supression du contenu. */
        $this->query
            ->from('entity_' . $node[ 'type' ])
            ->delete()
            ->where($node[ 'type' ] . '_id', '==', $node[ 'entity_id' ])
            ->execute();
    }

    public function deleteFile(string $type, int $idNode): void
    {
        $dir = $this->core->getSettingEnv('files_public', 'app/files') . "/node/$type/$idNode";
        if (!is_dir($dir)) {
            return;
        }

        $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $iterator    = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::CHILD_FIRST);

        /* Supprime tous les dossiers et fichiers */
        foreach ($iterator as $file) {
            $file->isDir()
                    ? \rmdir($file)
                    : \unlink($file);
        }
        /* Supprime le dossier cible. */
        \rmdir($dir);
    }

    public function isMaxEntity(string $entity, string $foreignKey, int $idNode, int $count): bool
    {
        if ($count === 0) {
            return false;
        }
        $data = $this->query
            ->from('entity_' . $entity)
            ->where($foreignKey, '=', $idNode)
            ->limit($count + 1)
            ->fetchAll();

        return count($data) >= $count;
    }

    public function getRules(array $field): array
    {
        $out = [];
        if (preg_match('/^(.*\|)?required(\|.*)?/', $field[ 'field_rules' ])) {
            $out[ 'required' ] = 1;
        }
        if (preg_match('/[\|]?(between|between_numeric):(\d+),(\d+)?/', $field[ 'field_rules' ], $matches)) {
            $out[ 'min' ] = (int) $matches[ 2 ];
            $out[ 'max' ] = (int) $matches[ 3 ];
        }
        if (preg_match('/[\|]?(max|max_numeric):(\d+)(yb|zb|eb|pb|tb|gb|mb|kb|b)?/', $field[ 'field_rules' ], $matches)) {
            $out[ 'max' ] = (int) $matches[ 2 ];
        }
        if (preg_match('/[\|]?(min|min_numeric):(\d+)(yb|zb|eb|pb|tb|gb|mb|kb|b)?/', $field[ 'field_rules' ], $matches)) {
            $out[ 'min' ] = (int) $matches[ 2 ];
        }

        return $out;
    }

    public function getPathSettings(): array
    {
        return [
            [
                'key'      => 'settings.path_index',
                'path'     => $this->config->get('settings.path_index'),
                'required' => 1,
                'title'    => 'Default homepage'
            ],
            [
                'key'   => 'settings.path_no_found',
                'path'  => $this->config->get('settings.path_no_found'),
                'title' => 'Page 404 by default (page not found)'
            ],
            [
                'key'   => 'settings.path_access_denied',
                'path'  => $this->config->get('settings.path_access_denied'),
                'title' => 'Page 403 by default (access denied)'
            ],
            [
                'key'   => 'settings.path_maintenance',
                'path'  => $this->config->get('settings.path_maintenance'),
                'title' => 'Default maintenance page'
            ],
            [
                'key'   => 'settings.connect_redirect',
                'path'  => $this->config->get('settings.connect_redirect'),
                'title' => 'Redirect page after connection'
            ],
            [
                'key'   => 'settings.rgpd_page',
                'path'  => $this->config->get('settings.rgpd_page'),
                'title' => 'GDPR Page'
            ],
            [
                'key'   => 'settings.terms_of_service_page',
                'path'  => $this->config->get('settings.terms_of_service_page'),
                'title' => 'Terms page'
            ]
        ];
    }

    private function getNodeTypeFieldsQuery(string $nodeType): Query
    {
        return $this->query
                ->from('node_type_field')
                ->leftJoin('field', 'field_id', '=', 'field.field_id')
                ->where('node_type', '=', $nodeType);
    }

    private function makeFields(string $type, array $fields, array $data): array
    {
        $out = [];

        $this->core->callHook('node.makefields', [ $type, &$fields, &$data ]);

        foreach ($fields as $value) {
            $key = $value[ 'field_name' ];

            $out[ $key ] = $value;

            if (isset($data[ $key ])) {
                $out[ $key ][ 'field_value' ]   = $data[ $key ];
                $out[ $key ][ 'field_display' ] = '<div>' . $data[ $key ] . '</div>';
            }
            if ($value[ 'field_type' ] === 'image') {
                $link = is_file($data[ $key ])
                    ? $this->core->getRequest()->getBasePath() . $data[ $key ]
                    : $data[ $key ];

                $out[ $key ][ 'field_value' ]   = $link;
                $out[ $key ][ 'field_display' ] = '<img src="' . $link . '" alt="' . $value[ 'field_label' ] . '">';
            } elseif ($value[ 'field_type' ] === 'file') {
                $link = is_file($data[ $key ])
                    ? $this->core->getRequest()->getBasePath() . $data[ $key ]
                    : $data[ $key ];

                $out[ $key ][ 'field_value' ]   = $link;
                $out[ $key ][ 'field_display' ] = '<a href="' . $link . '">' . $data[ $key ] . '</a>';
            } elseif ($value[ 'field_type' ] === 'select') {
                $options = json_decode($value[ 'field_option' ], true);

                $out[ $key ][ 'field_display' ] = '<p>' . $options[ $data[ $key ] ] . '</p>';
            } elseif ($value[ 'field_type' ] === 'radio') {
                $options = json_decode($value[ 'field_option' ], true);

                $out[ $key ][ 'field_display' ] = '<p>' . $options[ $data[ $key ] ] . '</p>';
            } elseif ($value[ 'field_type' ] === 'checkbox') {
                $options   = json_decode($value[ 'field_option' ], true);
                $explode   = explode(',', $data[ $key ]);
                $intersect = array_intersect_key($options, array_flip($explode));

                $out[ $key ][ 'field_display' ] = '<p>' . implode(', ', $intersect) . '</p>';
            } elseif ($value[ 'field_type' ] === 'one_to_many') {
                $option = json_decode($value[ 'field_option' ], true);

                $out[ $key ][ 'field_value' ]   = $this->makeFieldsByEntity($key, $data, $option);
                $out[ $key ][ 'field_display' ] = $this->tpl
                    ->getTheme()
                    ->createBlock('node/content-entity-show.php', $this->pathViews)
                    ->addVars([
                        'entities' => $out[ $key ][ 'field_value' ]
                    ])
                    ->addNamesOverride([ 'node/content-entity_' . $value[ 'field_name' ] . '-show.php' ]);

                $this->core->callHook('node.entity.' . $value[ 'field_name' ] . '.show', [
                    &$out[ $key ][ 'field_display' ]
                ]);
            }
        }

        return $out;
    }
}
