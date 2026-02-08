<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

	public function paginate(
		int $perPage = 20,
		array $with = [],
		array $filters = []
	): LengthAwarePaginator
	{
		$query = Role::query();

		if (!empty($with)) {
			$query->with($with);
		}

		$search = trim((string) ($filters['search'] ?? ''));
		if ($search !== '') {
			$like = '%' . $search . '%';
			$query->where(function (Builder $subQuery) use ($like) {
				$subQuery
					->where('code', 'like', $like)
					->orWhere('label', 'like', $like);
			});
		}

		$sortBy = (string) ($filters['sort_by'] ?? 'code');
		$sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc'));
		if (!in_array($sortDir, ['asc', 'desc'], true)) {
			$sortDir = 'asc';
		}

		$allowedSorts = ['id', 'code', 'label', 'is_system', 'created_at'];
		if (in_array($sortBy, $allowedSorts, true)) {
			$query->orderBy($sortBy, $sortDir);
		} else {
			$query->orderBy('code', 'asc');
		}

		return $query->paginate($perPage);
	}

	public function delete(Role $role): void
	{
		$role->delete();
	}
}
