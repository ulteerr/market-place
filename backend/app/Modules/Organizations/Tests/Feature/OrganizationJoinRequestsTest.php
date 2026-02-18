<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Users\Models\User;
use Tests\TestCase;

final class OrganizationJoinRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_join_request(): void
    {
        $organization = Organization::query()->create([
            "name" => "Test organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "message" => "Хочу вступить",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Join request submitted");

        $this->assertDatabaseHas("organization_join_requests", [
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $auth["user"]->id,
            "status" => "pending",
        ]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests/my")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data");
    }

    public function test_owner_can_list_and_approve_join_request(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::query()->create([
            "name" => "Owned organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::query()->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $applicant->id,
            "status" => "pending",
            "message" => "Please accept me",
        ]);

        $ownerAuth = $this->actingAsUser($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data");

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/approve",
                [
                    "role_code" => "manager",
                    "review_note" => "Approved by owner",
                ],
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Join request approved");

        $this->assertDatabaseHas("organization_join_requests", [
            "id" => (string) $request->id,
            "status" => "approved",
            "reviewed_by_user_id" => (string) $owner->id,
        ]);

        $this->assertDatabaseHas("organization_users", [
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $applicant->id,
            "role_code" => "manager",
            "status" => "active",
        ]);
    }

    public function test_non_owner_cannot_review_join_request(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::query()->create([
            "name" => "Protected organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::query()->create([
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $applicant->id,
            "status" => "pending",
        ]);

        $reviewerAuth = $this->actingAsUser($reviewer);

        $this->withHeaders($reviewerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests")
            ->assertForbidden();

        $this->withHeaders($reviewerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/reject",
                [
                    "review_note" => "No access",
                ],
            )
            ->assertForbidden();
    }
}
