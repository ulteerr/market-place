<?php

declare(strict_types=1);

namespace Modules\Users\Services;

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
use Modules\ChangeLog\Models\ChangeLog;
use Modules\ChangeLog\Services\ChangeLogContext;
use Modules\ActionLog\Models\ActionLog;
use Modules\ActionLog\Services\ActionLogService;

final class UsersService implements UsersServiceInterface
{
    public function __construct(
        private readonly UsersRepositoryInterface $repository,
        private readonly FilesService $filesService,
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
            $this->enrichCreateChangeLogWithRelatedState($user);
            $this->enrichCreateActionLogWithRelatedState($user);

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
            $this->writeRelatedChangeLogIfChanged(
                $user,
                $beforeRelatedSnapshot,
                $beforeRelatedMedia,
            );
            $this->writeRelatedActionLogIfChanged($user, $beforeRelatedSnapshot);

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

    private function enrichCreateChangeLogWithRelatedState(User $user): void
    {
        $createLog = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $user->id)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        if (!$createLog) {
            return;
        }

        $related = $this->buildRelatedSnapshot($user);
        $relatedMedia = $this->buildRelatedMediaSnapshot($user);
        $after = is_array($createLog->after) ? $createLog->after : [];

        $createLog->after = [...$after, ...$related];
        $createLog->media_after = $relatedMedia === [] ? null : $relatedMedia;
        $createLog->save();
    }

    private function writeRelatedChangeLogIfChanged(
        User $user,
        array $beforeRelatedSnapshot,
        array $beforeRelatedMedia,
    ): void {
        $afterRelatedSnapshot = $this->buildRelatedSnapshot($user);
        $afterRelatedMedia = $this->buildRelatedMediaSnapshot($user);
        $changedFields = [];

        foreach (array_keys($beforeRelatedSnapshot) as $key) {
            if (($beforeRelatedSnapshot[$key] ?? null) !== ($afterRelatedSnapshot[$key] ?? null)) {
                $changedFields[] = $key;
            }
        }

        if (empty($changedFields)) {
            return;
        }

        $context = app(ChangeLogContext::class);
        if ($context->disabled() || !$user->shouldWriteChangeLog("update")) {
            return;
        }

        $actor = auth()->user();

        $beforePayload = [];
        $afterPayload = [];
        foreach ($changedFields as $field) {
            $beforePayload[$field] = $beforeRelatedSnapshot[$field] ?? null;
            $afterPayload[$field] = $afterRelatedSnapshot[$field] ?? null;
        }

        $batchId = $context->currentBatchId();
        $resolvedMediaBefore = [];
        $resolvedMediaAfter = [];
        if (
            ($beforeRelatedSnapshot["avatar_id"] ?? null) !==
            ($afterRelatedSnapshot["avatar_id"] ?? null)
        ) {
            $resolvedMediaBefore["avatar"] = $beforeRelatedMedia["avatar"] ?? null;
            $resolvedMediaAfter["avatar"] = $afterRelatedMedia["avatar"] ?? null;
        }

        $existingEntry = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $user->id)
            ->where("event", "update")
            ->where("batch_id", $batchId)
            ->latest("created_at")
            ->first();

        if ($existingEntry) {
            $existingBefore = is_array($existingEntry->before) ? $existingEntry->before : [];
            $existingAfter = is_array($existingEntry->after) ? $existingEntry->after : [];
            $existingChangedFields = is_array($existingEntry->changed_fields)
                ? $existingEntry->changed_fields
                : [];
            $existingMeta = is_array($existingEntry->meta) ? $existingEntry->meta : [];
            $existingMediaBefore = is_array($existingEntry->media_before)
                ? $existingEntry->media_before
                : [];
            $existingMediaAfter = is_array($existingEntry->media_after)
                ? $existingEntry->media_after
                : [];

            $existingEntry->before = [...$existingBefore, ...$beforePayload];
            $existingEntry->after = [...$existingAfter, ...$afterPayload];
            $existingEntry->media_before = [...$existingMediaBefore, ...$resolvedMediaBefore];
            $existingEntry->media_after = [...$existingMediaAfter, ...$resolvedMediaAfter];
            $existingEntry->changed_fields = array_values(
                array_unique([...$existingChangedFields, ...$changedFields]),
            );
            $existingEntry->meta = [
                ...$existingMeta,
                "scope" =>
                    ($existingMeta["scope"] ?? null) === "profile" ? "profile" : "user-related",
                "schema_signature" => $user->changeLogSchemaSignature(),
            ];
            $existingEntry->rolled_back_from_id =
                $existingEntry->rolled_back_from_id ??
                ($context->meta()["rolled_back_from_id"] ?? null);
            $existingEntry->save();
            return;
        }

        $lastVersion = (int) ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $user->id)
            ->max("version");

        ChangeLog::query()->create([
            "auditable_type" => User::class,
            "auditable_id" => (string) $user->id,
            "event" => "update",
            "version" => $lastVersion + 1,
            "before" => $beforePayload,
            "after" => $afterPayload,
            "media_before" => $resolvedMediaBefore === [] ? null : $resolvedMediaBefore,
            "media_after" => $resolvedMediaAfter === [] ? null : $resolvedMediaAfter,
            "changed_fields" => array_values($changedFields),
            "actor_type" => $actor ? $actor::class : null,
            "actor_id" => $actor?->getKey(),
            "batch_id" => $batchId,
            "rolled_back_from_id" => $context->meta()["rolled_back_from_id"] ?? null,
            "meta" => [
                ...$context->meta(),
                "scope" => "user-related",
                "schema_signature" => $user->changeLogSchemaSignature(),
            ],
        ]);
    }

    private function enrichCreateActionLogWithRelatedState(User $user): void
    {
        if (!$user->shouldWriteActionLog("create")) {
            return;
        }

        $createLog = ActionLog::query()
            ->where("model_type", User::class)
            ->where("model_id", (string) $user->id)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        if (!$createLog) {
            return;
        }

        $related = $this->buildRelatedSnapshot($user);
        $after = is_array($createLog->after) ? $createLog->after : [];
        $createLog->after = [...$after, ...$related];
        $createLog->save();
    }

    private function writeRelatedActionLogIfChanged(User $user, array $beforeRelatedSnapshot): void
    {
        if (!$user->shouldWriteActionLog("update")) {
            return;
        }

        $afterRelatedSnapshot = $this->buildRelatedSnapshot($user);
        $changedFields = [];

        foreach (array_keys($beforeRelatedSnapshot) as $key) {
            if (($beforeRelatedSnapshot[$key] ?? null) !== ($afterRelatedSnapshot[$key] ?? null)) {
                $changedFields[] = $key;
            }
        }

        if ($changedFields === []) {
            return;
        }

        $beforePayload = [];
        $afterPayload = [];
        foreach ($changedFields as $field) {
            $beforePayload[$field] = $beforeRelatedSnapshot[$field] ?? null;
            $afterPayload[$field] = $afterRelatedSnapshot[$field] ?? null;
        }

        $actorId = auth()->id();
        $ipAddress = request()?->ip();

        $existingEntry = ActionLog::query()
            ->where("model_type", User::class)
            ->where("model_id", (string) $user->id)
            ->where("event", "update")
            ->where("user_id", $actorId)
            ->where("ip_address", $ipAddress)
            ->where("created_at", ">=", now()->subSeconds(10))
            ->latest("created_at")
            ->first();

        if ($existingEntry) {
            $existingBefore = is_array($existingEntry->before) ? $existingEntry->before : [];
            $existingAfter = is_array($existingEntry->after) ? $existingEntry->after : [];
            $existingChangedFields = is_array($existingEntry->changed_fields)
                ? $existingEntry->changed_fields
                : [];

            $existingEntry->before = [...$existingBefore, ...$beforePayload];
            $existingEntry->after = [...$existingAfter, ...$afterPayload];
            $existingEntry->changed_fields = array_values(
                array_unique([...$existingChangedFields, ...$changedFields]),
            );
            $existingEntry->save();
            return;
        }

        app(ActionLogService::class)->logModelEvent(
            $user,
            "update",
            $beforePayload,
            $afterPayload,
            $changedFields,
        );
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
