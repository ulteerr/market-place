<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationMember;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Users\Models\User;
use Tests\TestCase;

final class OrganizationMyAndOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_his_organizations(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $ownedOrganization = Organization::query()->create([
            "name" => "Owned org",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);
        $memberOrganization = Organization::query()->create([
            "name" => "Member org",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
        ]);
        Organization::query()->create([
            "name" => "Other org",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $memberRole = OrganizationRole::query()->firstOrCreate(["code" => "member"]);
        OrganizationMember::query()->create([
            "organization_id" => (string) $memberOrganization->id,
            "user_id" => (string) $owner->id,
            "role_id" => (string) $memberRole->id,
            "role_code" => "member",
            "status" => "active",
        ]);

        $auth = $this->actingAsUser($owner);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/my")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data.data");
    }

    public function test_owner_can_transfer_ownership(): void
    {
        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $organization = Organization::query()->create([
            "name" => "Transfer org",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $oldOwner->id,
        ]);

        $memberRole = OrganizationRole::query()->firstOrCreate(["code" => "member"]);
        OrganizationMember::query()->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $newOwner->id,
            "role_id" => (string) $memberRole->id,
            "role_code" => "member",
            "status" => "active",
        ]);

        $auth = $this->actingAsUser($oldOwner);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/organizations/{$organization->id}/owner/transfer", [
                "target_user_id" => (string) $newOwner->id,
            ])
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Organization ownership transferred");

        $this->assertDatabaseHas("organizations", [
            "id" => (string) $organization->id,
            "owner_user_id" => (string) $newOwner->id,
        ]);

        $this->assertDatabaseHas("organization_users", [
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $newOwner->id,
            "role_code" => "owner",
            "status" => "active",
        ]);
        $this->assertDatabaseHas("organization_users", [
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $oldOwner->id,
            "role_code" => "admin",
            "status" => "active",
        ]);
    }

    public function test_non_owner_cannot_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $organization = Organization::query()->create([
            "name" => "Forbidden transfer",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $auth = $this->actingAsUser($other);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/organizations/{$organization->id}/owner/transfer", [
                "target_user_id" => (string) $other->id,
            ])
            ->assertForbidden();
    }
}
