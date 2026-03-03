<?php

declare(strict_types=1);

namespace Modules\Users\DTOs;

use Illuminate\Support\Arr;

final readonly class UserUpsertData
{
    /**
     * @param array<string, mixed> $userAttributes
     * @param array<int, string>   $roles
     * @param array<int, string>   $permissionOverridesAllow
     * @param array<int, string>   $permissionOverridesDeny
     */
    public function __construct(
        public array $userAttributes,
        public array $roles,
        public array $permissionOverridesAllow,
        public array $permissionOverridesDeny,
        public bool $hasRoles,
        public bool $hasPermissionOverrides,
        public bool $hasAvatar,
        public bool $avatarDelete,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $overrides = $data["permission_overrides"] ?? [];
        if (!is_array($overrides)) {
            $overrides = [];
        }
        $allow = $overrides["allow"] ?? [];
        $deny = $overrides["deny"] ?? [];
        $allow = is_array($allow) ? self::normalizeCodes($allow) : [];
        $deny = is_array($deny) ? self::normalizeCodes($deny) : [];

        $rolesRaw = $data["roles"] ?? [];
        $roles = is_array($rolesRaw) ? array_values(array_unique(array_filter(
            array_map(fn(mixed $v): string => trim((string) $v), $rolesRaw),
            fn(string $v): bool => $v !== "",
        ))) : [];

        return new self(
            userAttributes: Arr::except($data, ["roles", "avatar", "avatar_delete", "permission_overrides"]),
            roles: $roles,
            permissionOverridesAllow: $allow,
            permissionOverridesDeny: $deny,
            hasRoles: array_key_exists("roles", $data),
            hasPermissionOverrides: array_key_exists("permission_overrides", $data),
            hasAvatar: array_key_exists("avatar", $data) && $data["avatar"] !== null,
            avatarDelete: (bool) ($data["avatar_delete"] ?? false),
        );
    }

    /**
     * @param array<int, mixed> $codes
     * @return array<int, string>
     */
    private static function normalizeCodes(array $codes): array
    {
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
}
