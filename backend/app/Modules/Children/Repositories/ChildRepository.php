<?php
declare(strict_types=1);

namespace Modules\Children\Repositories;

use Modules\Children\Models\Child;

final class ChildRepository implements ChildRepositoryInterface
{
    public function create(array $data): Child
    {
        return Child::create($data);
    }

    public function update(Child $child, array $data): Child
    {
        $child->update($data);
        return $child;
    }

    public function findById(string $id): ?Child
    {
        return Child::find($id);
    }

    public function findByParentId(string $parentId)
    {
        return Child::where('parent_id', $parentId)->get();
    }

    public function delete(Child $child): bool
    {
        return $child->delete();
    }
}