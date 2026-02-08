<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;


final class UsersRepository implements UsersRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByEmailOrPhone(string $value): ?User
    {
        return User::where('email', $value)
            ->orWhere('phone', $value)
            ->first();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = []
    ): LengthAwarePaginator
    {
        $query = User::query()->with('roles:id,code');

        if (!empty($with)) {
            $query->with($with);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function (Builder $subQuery) use ($like) {
                $subQuery
                    ->where('email', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('roles', function (Builder $roleQuery) use ($like) {
                        $roleQuery
                            ->where('code', 'like', $like)
                            ->orWhere('label', 'like', $like);
                    });
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc'));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $allowedSorts = [
            'id',
            'email',
            'first_name',
            'last_name',
            'phone',
            'created_at',
        ];

        if ($sortBy === 'name') {
            $query->orderBy('first_name', $sortDir)->orderBy('last_name', $sortDir);
        } elseif ($sortBy === 'access') {
            $query
                ->withCount([
                    'roles as admin_roles_count' => fn(Builder $roleQuery) => $roleQuery->where('code', '!=', 'participant'),
                ])
                ->orderBy('admin_roles_count', $sortDir)
                ->orderBy('first_name', 'asc');
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
