<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Illuminate\Support\Arr;
use Modules\Users\Models\Role;
use Modules\Users\Repositories\RolesRepositoryInterface;
use RuntimeException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Models\AccessPermission;

final class RolesService
{
    public function __construct(private readonly RolesRepositoryInterface $repository) {}

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function findById(string $id): Role
    {
        $role = $this->repository->findById($id);

        if (!$role) {
            throw new RuntimeException("Role not found");
        }

        return $role;
    }

    public function createRole(array $data): Role
    {
        $role = $this->repository->create(Arr::except($data, ["permissions"]));
        $this->syncRolePermissions($role, $data["permissions"] ?? []);

        return $role->load("permissions:id,code,scope,label");
    }

    public function updateRole(string $id, array $data): Role
    {
        $role = $this->repository->findById($id);

        if (!$role) {
            throw new RuntimeException("Role not found");
        }

        if ($role->is_system) {
            throw new RuntimeException("System roles cannot be modified");
        }

        $updatedRole = $this->repository->update($role, Arr::except($data, ["permissions"]));

        if (array_key_exists("permissions", $data)) {
            $this->syncRolePermissions($updatedRole, $data["permissions"] ?? []);
        }

        return $updatedRole->load("permissions:id,code,scope,label");
    }

    public function getRoleById(string $id): ?Role
    {
        $role = $this->repository->findById($id);
        return $role?->load("permissions:id,code,scope,label");
    }

    public function delete(string $id): void
    {
        $role = $this->repository->findById($id);

        if (!$role) {
            throw new RuntimeException("Role not found");
        }

        if ($role->is_system) {
            throw new RuntimeException("System roles cannot be deleted");
        }

        $this->repository->delete($role);
    }

    private function syncRolePermissions(Role $role, array $permissionCodes): void
    {
        $codes = array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn(mixed $code): string => is_string($code) ? trim($code) : "",
                        $permissionCodes,
                    ),
                    fn(string $code): bool => $code !== "",
                ),
            ),
        );

        if ($codes === []) {
            $role->permissions()->sync([]);
            return;
        }

        $permissions = AccessPermission::query()
            ->whereIn("code", $codes)
            ->get(["id", "code"]);

        if ($permissions->count() !== count($codes)) {
            $found = $permissions->pluck("code")->all();
            $missing = array_values(array_diff($codes, $found));
            throw new RuntimeException(
                "One or more permissions not found: " . implode(", ", $missing),
            );
        }

        $role->permissions()->sync($permissions->pluck("id")->all());
    }
}
