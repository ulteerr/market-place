<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;

final class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = $this->seedRoles();
        $users = User::query()->select("id")->limit(24)->get();

        if ($users->count() < 4) {
            return;
        }

        $statuses = ["active", "active", "active", "draft", "suspended"];
        $sources = ["manual", "self_registered", "import", "parsed"];

        for ($i = 0; $i < 8; $i += 1) {
            $owner = $users[$i % $users->count()];
            $createdBy = $users[($i + 1) % $users->count()];
            $claimed = $i % 3 !== 0;

            $organizationData = [
                "name" => sprintf("Организация %d", $i + 1),
                "description" => sprintf("Тестовая организация %d", $i + 1),
                "address" => sprintf("г. Москва, ул. Тестовая, д. %d", $i + 1),
                "phone" => sprintf("+7999000%04d", $i + 101),
                "email" => sprintf("org%d@example.com", $i + 1),
                "user_id" => (string) $owner->id,
            ];

            if (Schema::hasColumn("organizations", "status")) {
                $organizationData["status"] = $statuses[$i % count($statuses)];
            }
            if (Schema::hasColumn("organizations", "source_type")) {
                $organizationData["source_type"] = $sources[$i % count($sources)];
            }
            if (Schema::hasColumn("organizations", "ownership_status")) {
                $organizationData["ownership_status"] = $claimed ? "claimed" : "unclaimed";
            }
            if (Schema::hasColumn("organizations", "owner_user_id")) {
                $organizationData["owner_user_id"] = $claimed ? (string) $owner->id : null;
            }
            if (Schema::hasColumn("organizations", "created_by_user_id")) {
                $organizationData["created_by_user_id"] = (string) $createdBy->id;
            }
            if (Schema::hasColumn("organizations", "claimed_at")) {
                $organizationData["claimed_at"] = $claimed ? now()->subDays($i + 1) : null;
            }

            $organization = Organization::query()->create($organizationData);

            if ($claimed) {
                OrganizationMember::query()->updateOrCreate(
                    [
                        "organization_id" => (string) $organization->id,
                        "user_id" => (string) $owner->id,
                    ],
                    $this->buildMemberAttributes(
                        roleId: (string) $roles["owner"]->id,
                        roleCode: "owner",
                        status: "active",
                        invitedByUserId: (string) $createdBy->id,
                        joinedAtDaysAgo: $i + 1,
                    ),
                );
            }

            $memberA = $users[($i + 2) % $users->count()];
            $memberB = $users[($i + 3) % $users->count()];
            $pendingUser = $users[($i + 4) % $users->count()];

            OrganizationMember::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $memberA->id,
                ],
                $this->buildMemberAttributes(
                    roleId: (string) $roles["admin"]->id,
                    roleCode: "admin",
                    status: "active",
                    invitedByUserId: (string) $createdBy->id,
                    joinedAtDaysAgo: max($i, 1),
                ),
            );

            OrganizationMember::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $memberB->id,
                ],
                $this->buildMemberAttributes(
                    roleId: (string) $roles["member"]->id,
                    roleCode: "member",
                    status: "active",
                    invitedByUserId: (string) $memberA->id,
                    joinedAtDaysAgo: max($i - 1, 1),
                ),
            );

            if (Schema::hasTable("organization_join_requests")) {
                OrganizationJoinRequest::query()->updateOrCreate(
                    [
                        "organization_id" => (string) $organization->id,
                        "user_id" => (string) $pendingUser->id,
                        "status" => "pending",
                    ],
                    [
                        "message" => "Хочу вступить в организацию",
                        "review_note" => null,
                        "reviewed_by_user_id" => null,
                        "reviewed_at" => null,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, OrganizationRole>
     */
    private function seedRoles(): array
    {
        $codes = ["owner", "admin", "manager", "member"];
        $roles = [];

        foreach ($codes as $code) {
            $roles[$code] = OrganizationRole::query()->firstOrCreate(["code" => $code]);
        }

        return $roles;
    }

    private function buildMemberAttributes(
        string $roleId,
        string $roleCode,
        string $status,
        string $invitedByUserId,
        int $joinedAtDaysAgo,
    ): array {
        $attributes = [];

        if (Schema::hasColumn("organization_users", "role_id")) {
            $attributes["role_id"] = $roleId;
        }
        if (Schema::hasColumn("organization_users", "role_code")) {
            $attributes["role_code"] = $roleCode;
        }
        if (Schema::hasColumn("organization_users", "status")) {
            $attributes["status"] = $status;
        }
        if (Schema::hasColumn("organization_users", "invited_by_user_id")) {
            $attributes["invited_by_user_id"] = $invitedByUserId;
        }
        if (Schema::hasColumn("organization_users", "joined_at")) {
            $attributes["joined_at"] = now()->subDays($joinedAtDaysAgo);
        }

        return $attributes;
    }
}
