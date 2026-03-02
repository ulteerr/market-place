<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use App\Shared\Services\RelatedStateLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Auth\Access\AuthorizationException;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Enums\RoleCode;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\User;
use Modules\Users\Models\UserAccessPermission;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Illuminate\Support\Collection;
use Modules\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Files\Models\File;
use Modules\Files\Services\FilesService;
use RuntimeException;

final class UsersService implements UsersServiceInterface
{
    public function __construct(
        private readonly UsersRepositoryInterface $repository,
        private readonly FilesService $filesService,
        private readonly RelatedStateLogService $relatedStateLogService,
    ) {}

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->repository->create($data);
            $this->syncGlobalRoles($user, $data["roles"] ?? []);
            if (array_key_exists("permission_overrides", $data)) {
                $this->syncUserPermissionOverrides($user, $data["permission_overrides"] ?? []);
            }
            $this->attachAvatarIfProvided($user, $data);
            $relatedSnapshot = $this->buildRelatedSnapshot($user);
            $relatedMedia = $this->buildRelatedMediaSnapshot($user);
            $this->relatedStateLogService->enrichCreateChangeLogWithRelatedState(
                $user,
                $relatedSnapshot,
                $relatedMedia === [] ? null : $relatedMedia,
            );
            $this->relatedStateLogService->enrichCreateActionLogWithRelatedState(
                $user,
                $relatedSnapshot,
            );

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $beforeRelatedSnapshot = $this->buildRelatedSnapshot($user);
            $beforeRelatedMedia = $this->buildRelatedMediaSnapshot($user);

            $user = $this->repository->update($user, $data);
            if (array_key_exists("roles", $data)) {
                $this->syncGlobalRoles($user, $data["roles"] ?? []);
            }
            if (array_key_exists("permission_overrides", $data)) {
                $this->syncUserPermissionOverrides($user, $data["permission_overrides"] ?? []);
            }
            $this->removeAvatarIfRequested($user, $data);
            $this->attachAvatarIfProvided($user, $data);
            $afterRelatedSnapshot = $this->buildRelatedSnapshot($user);
            $afterRelatedMedia = $this->buildRelatedMediaSnapshot($user);
            $this->relatedStateLogService->writeRelatedChangeLogIfChanged(
                $user,
                $beforeRelatedSnapshot,
                $afterRelatedSnapshot,
                [
                    "before_media_snapshot" => $beforeRelatedMedia,
                    "after_media_snapshot" => $afterRelatedMedia,
                    "media_field_map" => ["avatar_id" => "avatar"],
                    "merge_meta" => function (array $existingMeta, array $contextMeta) use (
                        $user,
                    ): array {
                        return [
                            ...$existingMeta,
                            "scope" =>
                                ($existingMeta["scope"] ?? null) === "profile"
                                    ? "profile"
                                    : "user-related",
                            ...$contextMeta,
                            "schema_signature" => $user->changeLogSchemaSignature(),
                        ];
                    },
                    "create_meta" => function (array $contextMeta) use ($user): array {
                        return [
                            ...$contextMeta,
                            "scope" => "user-related",
                            "schema_signature" => $user->changeLogSchemaSignature(),
                        ];
                    },
                ],
            );
            $this->relatedStateLogService->writeRelatedActionLogIfChanged(
                $user,
                $beforeRelatedSnapshot,
                $afterRelatedSnapshot,
            );

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
        $this->ensureSuperAdminRoleCanBeManagedByActor($user, $roleCodes);

        if (!in_array(RoleCode::PARTICIPANT->value, $roleCodes, true)) {
            $roleCodes[] = RoleCode::PARTICIPANT->value;
        }

        $roleCodes = array_values(array_unique(array_filter($roleCodes)));

        $rolesToAssign = Role::whereIn("code", $roleCodes)->get();

        if ($rolesToAssign->count() !== count($roleCodes)) {
            throw new RuntimeException("One or more roles not found: " . implode(", ", $roleCodes));
        }

        $user->roles()->sync($rolesToAssign->pluck("id")->all());
    }

    private function ensureSuperAdminRoleCanBeManagedByActor(User $user, array $roleCodes): void
    {
        $actor = auth()->user();
        if (!$actor instanceof User || $actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return;
        }

        $requestedSuperAdmin = in_array(RoleCode::SUPER_ADMIN->value, $roleCodes, true);
        $targetIsSuperAdmin = $user->roles()->where("code", RoleCode::SUPER_ADMIN->value)->exists();

        if ($requestedSuperAdmin || $targetIsSuperAdmin) {
            throw new AuthorizationException("Only super admin can manage super admin role.");
        }
    }

    private function syncUserPermissionOverrides(User $user, mixed $overrides): void
    {
        if (!is_array($overrides)) {
            return;
        }

        $allowCodes = $this->normalizePermissionCodes($overrides["allow"] ?? []);
        $denyCodes = $this->normalizePermissionCodes($overrides["deny"] ?? []);
        $allowCodes = array_values(array_diff($allowCodes, $denyCodes));

        $allCodes = array_values(array_unique(array_merge($allowCodes, $denyCodes)));
        $codeToId = [];

        if ($allCodes !== []) {
            $permissions = AccessPermission::query()
                ->whereIn("code", $allCodes)
                ->get(["id", "code"]);

            if ($permissions->count() !== count($allCodes)) {
                $foundCodes = $permissions->pluck("code")->all();
                $missingCodes = array_values(array_diff($allCodes, $foundCodes));
                throw new RuntimeException(
                    "One or more permissions not found: " . implode(", ", $missingCodes),
                );
            }

            $codeToId = $permissions->pluck("id", "code")->all();
        }

        $user->permissionOverrides()->delete();

        foreach ($allowCodes as $code) {
            UserAccessPermission::query()->create([
                "user_id" => (string) $user->id,
                "permission_id" => (string) $codeToId[$code],
                "allowed" => true,
            ]);
        }

        foreach ($denyCodes as $code) {
            UserAccessPermission::query()->create([
                "user_id" => (string) $user->id,
                "permission_id" => (string) $codeToId[$code],
                "allowed" => false,
            ]);
        }
    }

    private function normalizePermissionCodes(mixed $codes): array
    {
        if (!is_array($codes)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn(mixed $code): string => is_string($code) ? trim($code) : "",
                        $codes,
                    ),
                    fn(string $code): bool => $code !== "",
                ),
            ),
        );
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function deleteUser(string $id): void
    {
        $user = $this->repository->findById($id);

        if (!$user) {
            throw new RuntimeException("User not found");
        }

        $this->repository->delete($user);
    }

    private function attachAvatarIfProvided(User $user, array $data): void
    {
        $avatar = $data["avatar"] ?? null;

        if (!$avatar instanceof UploadedFile) {
            return;
        }

        $this->filesService->attachUploadedFile($avatar, $user, "avatar", "public", false);
    }

    private function removeAvatarIfRequested(User $user, array $data): void
    {
        if (!($data["avatar_delete"] ?? false)) {
            return;
        }

        $this->filesService->removeAttachedFile($user, "avatar", false);
    }

    private function buildRelatedSnapshot(User $user): array
    {
        $fresh =
            $user
                ->fresh()
                ?->load([
                    "roles:id,code",
                    "avatar:id,fileable_id,fileable_type,collection",
                    "permissionOverrides.permission:id,code",
                ]) ??
            $user->load([
                "roles:id,code",
                "avatar:id,fileable_id,fileable_type,collection",
                "permissionOverrides.permission:id,code",
            ]);

        $roles = $fresh->roles->pluck("code")->filter()->values()->all();
        $permissionOverridesAllow = [];
        $permissionOverridesDeny = [];

        foreach ($fresh->permissionOverrides as $override) {
            $code = (string) ($override->permission?->code ?? "");
            if ($code === "") {
                continue;
            }

            if ((bool) $override->allowed) {
                $permissionOverridesAllow[] = $code;
            } else {
                $permissionOverridesDeny[] = $code;
            }
        }

        sort($roles);
        sort($permissionOverridesAllow);
        sort($permissionOverridesDeny);

        return [
            "roles" => $roles,
            "avatar_id" => $fresh->avatar?->id,
            "permission_overrides" => [
                "allow" => array_values(array_unique($permissionOverridesAllow)),
                "deny" => array_values(array_unique($permissionOverridesDeny)),
            ],
        ];
    }

    private function buildRelatedMediaSnapshot(User $user): array
    {
        $fresh =
            $user
                ->fresh()
                ?->load([
                    "avatar:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
                ]) ??
            $user->load([
                "avatar:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
            ]);

        return [
            "avatar" => $this->buildFileSnapshot($fresh->avatar),
        ];
    }

    private function buildFileSnapshot(?File $file): ?array
    {
        if (!$file) {
            return null;
        }

        return [
            "file_id" => (string) $file->id,
            "disk" => $file->disk,
            "path" => $file->path,
            "original_name" => $file->original_name,
            "mime_type" => $file->mime_type,
            "size" => (int) $file->size,
            "collection" => $file->collection,
        ];
    }
}
