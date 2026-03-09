<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActionLog\Models\ActionLog;
use Modules\Children\Models\Child;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Services\OrganizationJoinRequestsService;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Tests\TestCase;

final class OrganizationJoinRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_join_request(): void
    {
        $organization = Organization::factory()->create([
            "name" => "Test organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                "subject_id" => (string) $auth["user"]->id,
                "message" => "Хочу вступить",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Join request submitted");

        $this->assertDatabaseHas("organization_join_requests", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $auth["user"]->id,
            "requested_by_user_id" => (string) $auth["user"]->id,
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

        $organization = Organization::factory()->create([
            "name" => "Owned organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
            "status" => "pending",
            "message" => "Please accept me",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data");

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/approve",
                [
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

        $this->assertDatabaseHas("organization_clients", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "status" => "active",
            "added_by_user_id" => (string) $owner->id,
        ]);
    }

    public function test_non_owner_cannot_review_join_request(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Protected organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
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

    public function test_authenticated_user_can_submit_join_request_for_own_child(): void
    {
        $organization = Organization::factory()->create([
            "name" => "Child request organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $parent = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $parent->id,
        ]);

        $auth = $this->actingAsUser($parent);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                "subject_id" => (string) $child->id,
                "message" => "Request for my child",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Join request submitted");

        $this->assertDatabaseHas("organization_join_requests", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "requested_by_user_id" => (string) $parent->id,
            "status" => "pending",
        ]);
    }

    public function test_user_cannot_submit_join_request_for_another_users_child(): void
    {
        $organization = Organization::factory()->create([
            "name" => "Forbidden child request organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $childOwner = User::factory()->create();
        $actor = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $childOwner->id,
        ]);

        $auth = $this->actingAsUser($actor);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                "subject_id" => (string) $child->id,
                "message" => "Request for someone else's child",
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing("organization_join_requests", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "requested_by_user_id" => (string) $actor->id,
            "status" => "pending",
        ]);
    }

    public function test_cannot_create_duplicate_pending_join_request_for_same_child(): void
    {
        $organization = Organization::factory()->create([
            "name" => "Duplicate child request organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "unclaimed",
        ]);

        $parent = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $parent->id,
        ]);

        OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "requested_by_user_id" => (string) $parent->id,
            "status" => "pending",
        ]);

        $auth = $this->actingAsUser($parent);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                "subject_id" => (string) $child->id,
                "message" => "Duplicate request",
            ])
            ->assertStatus(422);
    }

    public function test_owner_can_approve_child_join_request_without_creating_org_member(): void
    {
        $owner = User::factory()->create();
        $parent = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $parent->id,
        ]);

        $organization = Organization::factory()->create([
            "name" => "Approve child request organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "requested_by_user_id" => (string) $parent->id,
            "status" => "pending",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/approve",
                [
                    "review_note" => "Approved child request",
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

        $this->assertDatabaseMissing("organization_users", [
            "organization_id" => (string) $organization->id,
            "user_id" => (string) $child->id,
        ]);

        $this->assertDatabaseHas("organization_clients", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "status" => "active",
            "added_by_user_id" => (string) $owner->id,
        ]);
    }

    public function test_owner_can_reject_child_join_request(): void
    {
        $owner = User::factory()->create();
        $parent = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $parent->id,
        ]);

        $organization = Organization::factory()->create([
            "name" => "Reject child request organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
            "requested_by_user_id" => (string) $parent->id,
            "status" => "pending",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/reject",
                [
                    "review_note" => "Rejected child request",
                ],
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("message", "Join request rejected");

        $this->assertDatabaseHas("organization_join_requests", [
            "id" => (string) $request->id,
            "status" => "rejected",
            "reviewed_by_user_id" => (string) $owner->id,
            "review_note" => "Rejected child request",
        ]);

        $this->assertDatabaseMissing("organization_clients", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "subject_id" => (string) $child->id,
        ]);
    }

    public function test_owner_can_change_join_request_decision_after_first_review(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Change decision organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
            "status" => "pending",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/approve",
                [
                    "review_note" => "Approved first",
                ],
            )
            ->assertOk()
            ->assertJsonPath("status", "ok");

        $this->assertDatabaseHas("organization_clients", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "status" => "active",
        ]);

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/reject",
                [
                    "review_note" => "Changed decision",
                ],
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.status", "rejected")
            ->assertJsonPath("data.review_note", "Changed decision");

        $this->assertDatabaseHas("organization_join_requests", [
            "id" => (string) $request->id,
            "status" => "rejected",
            "review_note" => "Changed decision",
            "reviewed_by_user_id" => (string) $owner->id,
        ]);

        $this->assertDatabaseMissing("organization_clients", [
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
        ]);
    }

    public function test_list_response_contains_subject_and_actor_blocks(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create([
            "first_name" => "Иван",
            "last_name" => "Петров",
            "middle_name" => "Сергеевич",
            "email" => "ivan.petrov@example.com",
        ]);

        $organization = Organization::factory()->create([
            "name" => "Contract organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
            "status" => "pending",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.subject.type", OrganizationJoinRequest::SUBJECT_TYPE_USER)
            ->assertJsonPath("data.data.0.subject.id", (string) $applicant->id)
            ->assertJsonPath("data.data.0.subject.label", "Петров Иван Сергеевич")
            ->assertJsonPath("data.data.0.requested_by.id", (string) $applicant->id)
            ->assertJsonPath("data.data.0.requested_by.email", "ivan.petrov@example.com")
            ->assertJsonPath("data.data.0.reviewed_by", null);
    }

    public function test_owner_can_list_organization_clients(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Clients organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $request = OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
            "status" => "pending",
        ]);

        $ownerAuth = $this->actingAsOrgAdmin($owner);

        $this->withHeaders($ownerAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/join-requests/{$request->id}/approve",
                [
                    "review_note" => "Approve for clients list",
                ],
            )
            ->assertOk();

        $this->withHeaders($ownerAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/clients")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data.data")
            ->assertJsonPath("data.data.0.subject_type", OrganizationJoinRequest::SUBJECT_TYPE_USER)
            ->assertJsonPath("data.data.0.subject_id", (string) $applicant->id);
    }

    public function test_join_request_write_action_and_change_logs_with_actor_fields(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $applicant = User::factory()->create();

        $organization = Organization::factory()->create([
            "name" => "Audit organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        $applicantAuth = $this->actingAsUser($applicant);

        $createResponse = $this->withHeaders($applicantAuth["headers"])
            ->postJson("/api/organizations/{$organization->id}/join-requests", [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                "subject_id" => (string) $applicant->id,
                "message" => "Audit submit",
            ])
            ->assertStatus(201);

        $joinRequestId = (string) $createResponse->json("data.id");

        $createActionLog = ActionLog::query()
            ->where("model_type", OrganizationJoinRequest::class)
            ->where("model_id", $joinRequestId)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($createActionLog);
        $this->assertSame(
            (string) $applicant->id,
            (string) ($createActionLog->after["requested_by_user_id"] ?? null),
        );

        $createChangeLog = ChangeLog::query()
            ->where("auditable_type", OrganizationJoinRequest::class)
            ->where("auditable_id", $joinRequestId)
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($createChangeLog);
        $this->assertSame(
            (string) $applicant->id,
            (string) ($createChangeLog->after["requested_by_user_id"] ?? null),
        );

        $this->actingAsOrgAdmin($owner);
        app(OrganizationJoinRequestsService::class)->approve(
            (string) $organization->id,
            $joinRequestId,
            $owner,
            "Audit approve",
        );

        $updateActionLog = ActionLog::query()
            ->where("model_type", OrganizationJoinRequest::class)
            ->where("model_id", $joinRequestId)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($updateActionLog);
        $this->assertContains("reviewed_by_user_id", $updateActionLog->changed_fields ?? []);
        $this->assertSame(
            (string) $owner->id,
            (string) ($updateActionLog->after["reviewed_by_user_id"] ?? null),
        );

        $updateChangeLog = ChangeLog::query()
            ->where("auditable_type", OrganizationJoinRequest::class)
            ->where("auditable_id", $joinRequestId)
            ->where("event", "update")
            ->latest("created_at")
            ->first();

        $this->assertNotNull($updateChangeLog);
        $this->assertContains("reviewed_by_user_id", $updateChangeLog->changed_fields ?? []);
        $this->assertSame(
            (string) $owner->id,
            (string) ($updateChangeLog->after["reviewed_by_user_id"] ?? null),
        );
    }

    public function test_super_admin_can_access_organization_lists_without_membership(): void
    {
        $owner = User::factory()->create();
        $superAdmin = User::factory()->create();
        $applicant = User::factory()->create();

        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin->roles()->syncWithoutDetaching([(string) $superAdminRole->id]);

        $organization = Organization::factory()->create([
            "name" => "Super admin access organization",
            "status" => "active",
            "source_type" => "manual",
            "ownership_status" => "claimed",
            "owner_user_id" => (string) $owner->id,
        ]);

        OrganizationJoinRequest::factory()->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => (string) $applicant->id,
            "requested_by_user_id" => (string) $applicant->id,
            "status" => "pending",
        ]);

        $superAdminAuth = $this->actingAsUser($superAdmin);

        $this->withHeaders($superAdminAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/users")
            ->assertOk();

        $this->withHeaders($superAdminAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/clients")
            ->assertOk();

        $this->withHeaders($superAdminAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/join-requests")
            ->assertOk();
    }

    private function actingAsOrgAdmin(User $user): array
    {
        $adminRole = Role::query()->firstOrCreate(
            ["code" => "admin"],
            [
                "name" => "Admin",
                "description" => "Auto-created in organization tests",
            ],
        );
        $user->roles()->syncWithoutDetaching([(string) $adminRole->id]);

        return $this->actingAsUser($user);
    }
}
