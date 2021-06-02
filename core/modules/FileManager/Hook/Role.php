<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Hook;

use Soosyze\Components\Validator\Validator;
use SoosyzeCore\QueryBuilder\Services\Query;

class Role
{
    /**
     * @var Query
     */
    private $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function hookRoleDeleteBefore(Validator $validator, int $id): void
    {
        $this->query
            ->from('profil_file_role')
            ->where('role_id', '==', $id)
            ->delete()
            ->execute();
    }
}
