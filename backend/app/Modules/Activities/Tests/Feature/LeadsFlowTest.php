<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\Lead;
use Modules\Categories\Models\Category;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Users\Models\AccessPermission;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LeadsFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_submit_self_lead(): void
    {
        [$activity] = $this->createActivityContext();
        $user = User::factory()->create();
        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_SELF,
                "contact_channels" => ["phone"],
                "contact_payload" => ["phone" => "+79990000000"],
                "message" => "Перезвоните вечером.",
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.activity_id", (string) $activity->id)
            ->assertJsonPath("data.user_id", (string) $user->id)
            ->assertJsonPath("data.status", Lead::STATUS_NEW);

        $this->assertDatabaseHas("leads", [
            "activity_id" => (string) $activity->id,
            "user_id" => (string) $user->id,
            "request_for_type" => Lead::REQUEST_FOR_SELF,
            "status" => Lead::STATUS_NEW,
        ]);
    }

    #[Test]
    public function authenticated_user_can_submit_chat_only_lead_without_message_or_contact_payload(): void
    {
        [$activity] = $this->createActivityContext();
        $user = User::factory()->create();
        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_SELF,
                "contact_channels" => ["chat"],
            ])
            ->assertStatus(201)
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.user_id", (string) $user->id)
            ->assertJsonPath("data.message", null);

        $this->assertDatabaseHas("leads", [
            "activity_id" => (string) $activity->id,
            "user_id" => (string) $user->id,
            "request_for_type" => Lead::REQUEST_FOR_SELF,
            "message" => null,
        ]);
    }

    #[Test]
    public function authenticated_user_can_submit_child_lead_only_for_own_child(): void
    {
        [$activity] = $this->createActivityContext();
        $user = User::factory()->create();
        $ownChild = Child::factory()->create(["user_id" => (string) $user->id]);
        $foreignChild = Child::factory()->create();
        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_CHILD,
                "child_id" => (string) $ownChild->id,
                "contact_channels" => ["telegram"],
                "contact_payload" => ["telegram" => "@owner_handle"],
            ])
            ->assertStatus(201)
            ->assertJsonPath("data.child_id", (string) $ownChild->id);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_CHILD,
                "child_id" => (string) $foreignChild->id,
                "contact_channels" => ["telegram"],
                "contact_payload" => ["telegram" => "@owner_handle"],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["child_id"]);
    }

    #[Test]
    public function lead_submission_requires_reachable_contact_for_non_chat_scenarios(): void
    {
        [$activity] = $this->createActivityContext();
        $user = User::factory()->create();
        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_SELF,
                "contact_channels" => ["phone"],
                "contact_payload" => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["contact_payload"]);
    }

    #[Test]
    public function lead_submission_rejects_invalid_contact_channel_values(): void
    {
        [$activity] = $this->createActivityContext();
        $user = User::factory()->create();
        $auth = $this->actingAsUser($user);

        $this->withHeaders($auth["headers"])
            ->postJson("/api/activities/{$activity->id}/leads", [
                "request_for_type" => Lead::REQUEST_FOR_SELF,
                "contact_channels" => ["email"],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["contact_channels.0"]);
    }

    #[Test]
    public function organization_and_admin_can_list_and_update_lead_statuses(): void
    {
        [$activity, $organization] = $this->createActivityContext();
        $adminRole = Role::factory()->admin()->create();

        $manager = User::factory()->create();
        $manager->roles()->sync([$adminRole->id]);
        $organization->update(["owner_user_id" => (string) $manager->id]);

        $lead = Lead::factory()
            ->forActivity($activity)
            ->create([
                "status" => Lead::STATUS_NEW,
            ]);

        $auth = $this->actingAsUser($manager);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/organizations/{$organization->id}/activity-leads")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.data.0.id", (string) $lead->id);

        $this->withHeaders($auth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/activity-leads/{$lead->id}/status",
                [
                    "status" => Lead::STATUS_CONTACTED,
                ],
            )
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.status", Lead::STATUS_CONTACTED);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/activity-leads")
            ->assertOk()
            ->assertJsonPath("data.data.0.id", (string) $lead->id);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/activity-leads/{$lead->id}/status", [
                "status" => Lead::STATUS_REGISTERED,
            ])
            ->assertOk()
            ->assertJsonPath("data.status", Lead::STATUS_REGISTERED);
    }

    #[Test]
    public function organization_and_admin_status_updates_reject_invalid_status(): void
    {
        [$activity, $organization] = $this->createActivityContext();
        $adminRole = Role::factory()->admin()->create();

        $manager = User::factory()->create();
        $manager->roles()->sync([$adminRole->id]);
        $organization->update(["owner_user_id" => (string) $manager->id]);

        $lead = Lead::factory()
            ->forActivity($activity)
            ->create([
                "status" => Lead::STATUS_NEW,
            ]);

        $auth = $this->actingAsUser($manager);

        $this->withHeaders($auth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/activity-leads/{$lead->id}/status",
                [
                    "status" => "invalid-status",
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(["status"]);

        $this->withHeaders($auth["headers"])
            ->patchJson("/api/admin/activity-leads/{$lead->id}/status", [
                "status" => "invalid-status",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["status"]);
    }

    #[Test]
    public function lead_read_and_update_routes_require_explicit_lead_permissions(): void
    {
        [$activity, $organization] = $this->createActivityContext();

        $lead = Lead::factory()
            ->forActivity($activity)
            ->create([
                "status" => Lead::STATUS_NEW,
            ]);

        $restrictedAdmin = User::factory()->create();
        $restrictedAdminRole = Role::factory()->create([
            "code" => "restricted-admin",
            "label" => "Restricted Admin",
        ]);
        $panelAccess = AccessPermission::query()->create([
            "code" => "admin.panel.access",
            "scope" => "admin",
            "label" => "Доступ в админ-панель",
        ]);
        $restrictedAdminRole->permissions()->sync([$panelAccess->id]);
        $restrictedAdmin->roles()->sync([$restrictedAdminRole->id]);

        $restrictedOrgUser = User::factory()->create();
        $organization->update(["owner_user_id" => (string) $restrictedOrgUser->id]);

        $adminAuth = $this->actingAsUser($restrictedAdmin);
        $orgAuth = $this->actingAsUser($restrictedOrgUser);

        $this->withHeaders($adminAuth["headers"])
            ->getJson("/api/admin/activity-leads")
            ->assertForbidden();

        $this->withHeaders($adminAuth["headers"])
            ->patchJson("/api/admin/activity-leads/{$lead->id}/status", [
                "status" => Lead::STATUS_CONTACTED,
            ])
            ->assertForbidden();

        $this->withHeaders($orgAuth["headers"])
            ->getJson("/api/organizations/{$organization->id}/activity-leads")
            ->assertForbidden();

        $this->withHeaders($orgAuth["headers"])
            ->patchJson(
                "/api/organizations/{$organization->id}/activity-leads/{$lead->id}/status",
                [
                    "status" => Lead::STATUS_CONTACTED,
                ],
            )
            ->assertForbidden();
    }

    private function createActivityContext(): array
    {
        $organization = Organization::factory()->create([
            "name" => "Организация спорта",
            "status" => "active",
        ]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
        ]);
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "short_description" => "Секция футбола.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        return [$activity, $organization, $location, $root, $leaf];
    }
}
