<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use Modules\Children\Models\Child;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Enums\RoleCode;
use Modules\Files\Traits\HasFiles;
use RuntimeException;
use Illuminate\Support\Arr;

final class User extends Authenticatable
{
    use Notifiable, HasUuid, HasApiTokens, HasFactory, HasFiles, HasChangeLog, HasActionLog;

    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "first_name",
        "last_name",
        "middle_name",
        "gender",
        "email",
        "password",
        "phone",
        "settings",
    ];

    protected $hidden = ["password", "remember_token"];

    protected $casts = [
        "email_verified_at" => "datetime",
        "settings" => "array",
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            $user->files()->delete();
        });
    }

    protected function password(): Attribute
    {
        return Attribute::make(set: fn(?string $value) => $value ? Hash::make($value) : null);
    }

    public function children()
    {
        return $this->hasMany(Child::class, "user_id");
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, "role_user", "user_id", "role_id");
    }

    public function avatar()
    {
        return $this->fileFromCollection("avatar");
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserAccessPermission::class, "user_id");
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains("code", $code);
    }

    public function hasAnyRole(array $codes): bool
    {
        return $this->roles->whereIn("code", $codes)->isNotEmpty();
    }
    public function isAdmin(): bool
    {
        return $this->roles->contains("code", RoleCode::ADMIN->value);
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->hasPermission("admin.panel.access") ||
            $this->roles->where("code", "!=", RoleCode::PARTICIPANT->value)->isNotEmpty();
    }

    public function hasPermission(string $code): bool
    {
        $override = $this->resolvePermissionOverride($code);
        if ($override !== null) {
            return $override;
        }

        if ($this->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return true;
        }

        $hasFromDatabase = $this->roles()
            ->whereHas("permissions", fn($query) => $query->where("code", $code))
            ->exists();

        if ($hasFromDatabase) {
            return true;
        }

        $roleCodes = $this->roles()->pluck("code")->all();
        $permissionsByRole = config("access-permissions.roles", []);

        foreach ($roleCodes as $roleCode) {
            if (!is_string($roleCode)) {
                continue;
            }

            $permissions = $permissionsByRole[$roleCode] ?? null;
            if (is_array($permissions) && in_array($code, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(array $codes): bool
    {
        if (empty($codes)) {
            return false;
        }

        foreach ($codes as $code) {
            if (!is_string($code) || $code === "") {
                continue;
            }

            if ($this->hasPermission($code)) {
                return true;
            }
        }

        return false;
    }

    public function effectivePermissionCodes(): array
    {
        $this->loadMissing(["roles.permissions:id,code", "permissionOverrides.permission:id,code"]);

        $configuredCodes = $this->configuredPermissionCodes();
        $databaseCodes = AccessPermission::query()
            ->pluck("code")
            ->filter(fn($code): bool => is_string($code) && $code !== "")
            ->values()
            ->all();

        $allCodes = array_values(array_unique(array_merge($databaseCodes, $configuredCodes)));
        if ($allCodes === []) {
            return [];
        }

        $overrideMap = [];
        foreach ($this->permissionOverrides as $override) {
            $code = (string) ($override->permission?->code ?? "");
            if ($code === "") {
                continue;
            }
            $overrideMap[$code] = (bool) $override->allowed;
        }

        $fromRoleDatabase = [];
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $code = (string) ($permission->code ?? "");
                if ($code === "") {
                    continue;
                }
                $fromRoleDatabase[$code] = true;
            }
        }

        $fromRoleConfig = [];
        $permissionsByRole = config("access-permissions.roles", []);
        foreach ($this->roles as $role) {
            $roleCode = (string) ($role->code ?? "");
            if ($roleCode === "") {
                continue;
            }

            $codes = $permissionsByRole[$roleCode] ?? null;
            if (!is_array($codes)) {
                continue;
            }

            foreach ($codes as $code) {
                if (!is_string($code) || $code === "") {
                    continue;
                }
                $fromRoleConfig[$code] = true;
            }
        }

        $isSuperAdmin = $this->hasRole(RoleCode::SUPER_ADMIN->value);
        $effective = [];

        foreach ($allCodes as $code) {
            if (array_key_exists($code, $overrideMap)) {
                if ($overrideMap[$code] === true) {
                    $effective[] = $code;
                }
                continue;
            }

            if ($isSuperAdmin || isset($fromRoleDatabase[$code]) || isset($fromRoleConfig[$code])) {
                $effective[] = $code;
            }
        }

        sort($effective);

        return array_values(array_unique($effective));
    }

    public function setPermissionOverride(string $code, bool $allowed): void
    {
        $permission = AccessPermission::query()->where("code", $code)->first();
        if (!$permission) {
            throw new RuntimeException("Permission not found: {$code}");
        }

        UserAccessPermission::query()->updateOrCreate(
            [
                "user_id" => (string) $this->id,
                "permission_id" => (string) $permission->id,
            ],
            [
                "allowed" => $allowed,
            ],
        );
    }

    public function clearPermissionOverride(string $code): void
    {
        $permission = AccessPermission::query()->where("code", $code)->first();
        if (!$permission) {
            return;
        }

        UserAccessPermission::query()
            ->where("user_id", (string) $this->id)
            ->where("permission_id", (string) $permission->id)
            ->delete();
    }

    private function resolvePermissionOverride(string $code): ?bool
    {
        $override = UserAccessPermission::query()
            ->where("user_id", (string) $this->id)
            ->whereHas("permission", fn($query) => $query->where("code", $code))
            ->value("allowed");

        if ($override === null) {
            return null;
        }

        return (bool) $override;
    }

    public function changeLogExcludedAttributes(): array
    {
        return ["password", "remember_token"];
    }

    private function configuredPermissionCodes(): array
    {
        $configured = config("access-permissions.permissions", []);
        if (!is_array($configured)) {
            return [];
        }

        $codes = [];
        foreach ($configured as $items) {
            if (!is_array($items)) {
                continue;
            }
            $codes = array_merge($codes, array_keys($items));
        }

        return array_values(
            array_unique(
                array_filter(
                    Arr::map($codes, fn($code): string => is_string($code) ? trim($code) : ""),
                    fn(string $code): bool => $code !== "",
                ),
            ),
        );
    }
}
