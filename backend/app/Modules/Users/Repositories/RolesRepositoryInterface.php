<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RolesRepositoryInterface
{
	
	public function findById(string $id): ?Role;

	public function create(array $data): Role;

	public function update(Role $role, array $data): Role;

	public function delete(Role $role): void;

	public function paginate(int $perPage = 20): LengthAwarePaginator;
}
