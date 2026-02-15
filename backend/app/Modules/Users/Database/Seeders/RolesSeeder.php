<?php

declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Enums\RoleCode;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRoleIfNotExists(RoleCode::PARTICIPANT->value, "Участник", true);
        $this->createRoleIfNotExists(RoleCode::SUPER_ADMIN->value, "Супер-администратор", true);
        $this->createRoleIfNotExists(RoleCode::ADMIN->value, "Администратор", true);
        $this->createRoleIfNotExists(RoleCode::MODERATOR->value, "Модератор", false);

        $this->syncAccessPermissions();
    }

    private function createRoleIfNotExists(
        string $code,
        string $label,
        bool $isSystem = false,
    ): void {
        Role::firstOrCreate(
            ["code" => $code],
            [
                "label" => $label,
                "is_system" => $isSystem,
            ],
        );
    }

    private function syncAccessPermissions(): void
    {
        $permissionsByRole = config("access-permissions.roles");
        $configuredPermissions = $this->flattenConfiguredPermissions();

        if (!is_array($permissionsByRole) || empty($permissionsByRole)) {
            return;
        }

        foreach ($configuredPermissions as $permissionCode => $meta) {
            AccessPermission::query()->firstOrCreate(
                ["code" => $permissionCode],
                [
                    "scope" => (string) ($meta["scope"] ?? "user"),
                    "label" => isset($meta["label"]) ? (string) $meta["label"] : null,
                ],
            );
        }

        $configuredCodes = array_keys($configuredPermissions);
        if (!empty($configuredCodes)) {
            AccessPermission::query()->whereNotIn("code", $configuredCodes)->delete();
        }

        foreach ($permissionsByRole as $roleCode => $permissionCodes) {
            $role = Role::query()->where("code", $roleCode)->first();

            if (!$role || !is_array($permissionCodes)) {
                continue;
            }

            $permissionIds = AccessPermission::query()
                ->whereIn("code", $permissionCodes)
                ->pluck("id")
                ->all();

            $role->permissions()->sync($permissionIds);
        }

        $this->syncSuperAdminPermissions();
    }

    private function syncSuperAdminPermissions(): void
    {
        $superAdminRole = Role::query()->where("code", RoleCode::SUPER_ADMIN->value)->first();

        if (!$superAdminRole) {
            return;
        }

        $allPermissionIds = AccessPermission::query()->pluck("id")->all();
        $superAdminRole->permissions()->sync($allPermissionIds);
    }

    private function flattenConfiguredPermissions(): array
    {
        $configured = config("access-permissions.permissions", []);

        if (!is_array($configured)) {
            return [];
        }

        $flat = [];

        foreach ($configured as $scope => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $code => $label) {
                if (!is_string($code) || $code === "") {
                    continue;
                }

                $flat[$code] = [
                    "scope" => (string) $scope,
                    "label" => is_string($label) ? $label : null,
                ];
            }
        }

        return $flat;
    }
}
