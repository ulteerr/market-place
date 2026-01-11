<?php
declare(strict_types=1);

namespace Modules\Children\Repositories;

use Modules\Children\Models\Child;

interface ChildRepositoryInterface
{
    public function create(array $data): Child;
    public function update(Child $child, array $data): Child;
    public function findById(string $id): ?Child;
    public function findByParentId(string $parentId);
    public function delete(Child $child): bool;
}
