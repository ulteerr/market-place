<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationUser;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Users\Models\UserAccessPermission;
use Tests\TestCase;

final class OrganizationUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_org_members_write_permission_can_manage_members(): void
    {
        $adminRole = Role::factory()->admin()->create();
        $owner = User::factory()->create();
        $owner->roles()->syncWithoutDetaching([$adminRole->id]);
        $target = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Members organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $ownerAuth = $this->actingAsUser($owner);

        $createResponse = $this->withHeaders($ownerAuth["headers"])
            ->postJson("/api/organizations/{$organization->id}/users", [
                "user_id" => (string) $target->id,
                "position" => "Coach",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok");

        $memberId = (string) $createResponse->json("data.id");

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/users")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data");

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson("/api/organizations/{$organization->id}/users/{$memberId}", [
                "position" => "Administrator",
                "status" => "active",
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseHas("organization_users", [
            "id" => $memberId,
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $target->id,
            "position" => "Administrator",
            "status" => "active",
        ]);

        $this->withHeaders($ownerAuth["headers"])
            ->deleteJson("/api/organizations/{$organization->id}/users/{$memberId}")
            ->assertOk();

        $this->assertDatabaseMissing("organization_users", ["id" => $memberId]);
    }

    public function test_user_without_permission_cannot_manage_members(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Admin organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $ownerAuth = $this->actingAsUser($owner);
        $this->withHeaders($ownerAuth["headers"])
            ->postJson("/api/organizations/{$organization->id}/users", [
                "user_id" => (string) $target->id,
                "position" => "Coach",
            ])
            ->assertForbidden();
    }

    public function test_non_member_cannot_manage_organization_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Protected organization members",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $outsiderAuth = $this->actingAsUser($outsider);

        $this->withHeaders($outsiderAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/users")
            ->assertForbidden();
    }

    public function test_org_user_with_permission_can_access_only_related_organizations(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $organizationA = Organization::factory()->create([
            "name" => "Related organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
        ]);
        $organizationB = Organization::factory()->create([
            "name" => "Foreign organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
        ]);

        OrganizationUser::factory()->create([
            "organization_id" => (string) $organizationA->id,
            "user_id" => (string) $user->id,
            "position" => "Coach",
            "status" => "active",
        ]);

        $permission = AccessPermission::query()->firstOrCreate(
            ["code" => "org.members.read"],
            [
                "scope" => "org",
                "label" => "Read organization users",
            ],
        );

        UserAccessPermission::query()->updateOrCreate(
            [
                "user_id" => (string) $user->id,
                "permission_id" => (string) $permission->id,
            ],
            ["allowed" => true],
        );

        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organizationA->id}/users")
            ->assertOk();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organizationB->id}/users")
            ->assertForbidden();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organizationA->id}/members")
            ->assertOk();

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organizationB->id}/members")
            ->assertForbidden();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organizationA->id}/users", [
                "user_id" => (string) $target->id,
                "position" => "Administrator",
            ])
            ->assertForbidden();
    }
}
