<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Modules\Users\Models\User;
use Modules\Users\Repositories\UsersRepositoryInterface;

final class UsersService
{
	public function __construct(
		private readonly UsersRepositoryInterface $repository
	) {}

	public function createUser(array $data): User
	{
		return $this->repository->create($data);
	}

	public function updateUser(User $user, array $data): User
	{
		return $this->repository->update($user, $data);
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

	public function getChildrenForParent(string $parentId)
	{
		$user = $this->repository->findById($parentId);
		return $user ? $user->children : collect();
	}
}
