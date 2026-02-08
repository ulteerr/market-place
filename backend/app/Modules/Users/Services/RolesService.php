<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Modules\Users\Models\Role;
use Modules\Users\Repositories\RolesRepositoryInterface;
use RuntimeException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RolesService
{
	public function __construct(
		private readonly RolesRepositoryInterface $repository
	) {}

	public function paginate(
		int $perPage = 20,
		array $with = [],
		array $filters = []
	): LengthAwarePaginator {
		return $this->repository->paginate($perPage, $with, $filters);
	}

	public function findById(string $id): Role
	{
		$role = $this->repository->findById($id);

		if (!$role) {
			throw new RuntimeException('Role not found');
		}

		return $role;
	}

	public function createRole(array $data): Role
	{
		return $this->repository->create($data);
	}

	public function updateRole(string $id, array $data): Role
	{
		$role = $this->repository->findById($id);

		if (!$role) {
			throw new RuntimeException('Role not found');
		}

		if ($role->is_system) {
			throw new RuntimeException('System roles cannot be modified');
		}

		return $this->repository->update($role, $data);
	}

	public function getRoleById(string $id): ?Role
	{
		return $this->repository->findById($id);
	}


	public function delete(string $id): void
	{
		$role = $this->repository->findById($id);

		if (!$role) {
			throw new RuntimeException('Role not found');
		}

		if ($role->is_system) {
			throw new RuntimeException('System roles cannot be deleted');
		}

		$this->repository->delete($role);
	}
}
