<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RolesRepository implements RolesRepositoryInterface
{

	public function findById(string $id): ?Role
	{
		return Role::find($id);
	}

	public function create(array $data): Role
	{
		return Role::create($data);
	}

	public function update(Role $role, array $data): Role
	{
		$role->update($data);

		return $role;
	}

	public function paginate(int $perPage = 20): LengthAwarePaginator
	{
		return Role::paginate($perPage);
	}

	public function delete(Role $role): void
	{
		$role->delete();
	}
}
