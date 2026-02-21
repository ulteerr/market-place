<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;
use Tests\TestCase;

final class OrganizationMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_members(): void
    {
        $owner = User::factory()->create();
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
            ->postJson("/api/organizations/{$organization->id}/members", [
                "user_id" => (string) $target->id,
                "role_code" => "manager",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok");

        $memberId = (string) $createResponse->json("data.id");

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data");

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson("/api/organizations/{$organization->id}/members/{$memberId}", [
                "role_code" => "admin",
                "status" => "active",
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseHas("organization_users", [
            "id" => $memberId,
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $target->id,
            "role_code" => "admin",
            "status" => "active",
        ]);

        $this->withHeaders($ownerAuth["headers"])
            ->deleteJson("/api/organizations/{$organization->id}/members/{$memberId}")
            ->assertOk();

        $this->assertDatabaseMissing("organization_users", ["id" => $memberId]);
    }

    public function test_org_admin_can_manage_regular_members_but_not_owner_role(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $target = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Admin organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $adminRole = OrganizationRole::factory()->create(["code" => "admin"]);
        $memberRole = OrganizationRole::factory()->create(["code" => "member"]);

        OrganizationMember::factory()->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $orgAdmin->id,
            "role_id" => (string) $adminRole->id,
            "role_code" => "admin",
            "status" => "active",
        ]);

        $adminAuth = $this->actingAsUser($orgAdmin);

        $this->withHeaders($adminAuth["headers"])
            ->postJson("/api/organizations/{$organization->id}/members", [
                "user_id" => (string) $target->id,
                "role_code" => "member",
            ])
            ->assertStatus(201);

        $created = OrganizationMember::query()
            ->where("organization_id", (string) $organization->id)
            ->where("user_id", (string) $target->id)
            ->firstOrFail();

        $this->withHeaders($adminAuth["headers"])
            ->patchJson("/api/organizations/{$organization->id}/members/{$created->id}", [
                "role_code" => "owner",
            ])
            ->assertStatus(422);

        $ownerMember = OrganizationMember::factory()->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $owner->id,
            "role_id" => (string) $memberRole->id,
            "role_code" => "owner",
            "status" => "active",
        ]);

        $this->withHeaders($adminAuth["headers"])
            ->deleteJson("/api/organizations/{$organization->id}/members/{$ownerMember->id}")
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
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertForbidden();
    }
}
