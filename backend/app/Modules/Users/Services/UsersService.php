<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Models\User;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Illuminate\Support\Collection;
use Modules\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

final class UsersService implements UsersServiceInterface
{
	public function __construct(
		private readonly UsersRepositoryInterface $repository
	) {}

	public function createUser(array $data): User
	{
		return DB::transaction(function () use ($data) {

			$user = $this->repository->create($data);
			$this->syncGlobalRoles(
				$user,
				$data['roles'] ?? []
			);

			return $user;
		});
	}

	public function updateUser(User $user, array $data): User
	{
		return DB::transaction(function () use ($user, $data) {
			$user = $this->repository->update($user, $data);
			if (array_key_exists('roles', $data)) {
				$this->syncGlobalRoles(
					$user,
					$data['roles'] ?? []
				);
			}

			return $user;
		});
	}


	public function findByEmail(string $email): ?User
	{
		return $this->repository->findByEmail($email);
	}

	public function findByEmailOrPhone(string $value): ?User
	{
		return $this->repository->findByEmailOrPhone($value);
	}

	public function getUserById(string $id): ?User
	{
		return $this->repository->findById($id);
	}

	public function getChildrenForParent(string $parentId): Collection
	{
		$user = $this->repository->findById($parentId);
		return $user ? $user->children : collect();
	}

	private function syncGlobalRoles(User $user, array $roleCodes = []): void
	{

		if (!in_array('participant', $roleCodes, true)) {
			$roleCodes[] = 'participant';
		}


		$roleCodes = array_values(
			array_unique(
				array_filter($roleCodes)
			)
		);


		$rolesToAssign = Role::whereIn('code', $roleCodes)->get();

		if ($rolesToAssign->count() !== count($roleCodes)) {
			throw new RuntimeException(
				'One or more roles not found: ' . implode(', ', $roleCodes)
			);
		}

		$user->roles()->sync(
			$rolesToAssign->pluck('id')->all()
		);
	}

	public function paginate(
		int $perPage = 20,
		array $with = [],
		array $filters = []
	): LengthAwarePaginator {
		return $this->repository->paginate($perPage, $with, $filters);
	}

	public function deleteUser(string $id): void
	{
		$user = $this->repository->findById($id);

		if (!$user) {
			throw new RuntimeException('User not found');
		}

		$this->repository->delete($user);
	}
}
