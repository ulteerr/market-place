<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Children\Models\Child;
use Modules\Geo\Models\City;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationClient;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Models\OrganizationLocation;
use Modules\Organizations\Models\OrganizationUser;
use Modules\Users\Models\User;

final class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->select("id")->limit(24)->get();
        $children = Child::query()
            ->select(["id", "user_id"])
            ->limit(24)
            ->get();

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
                "phone" => sprintf("+7999000%04d", $i + 101),
                "email" => sprintf("org%d@example.com", $i + 1),
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

            $city = City::query()
                ->with(["region.country", "districts"])
                ->inRandomOrder()
                ->first();

            OrganizationLocation::query()->create([
                "organization_id" => (string) $organization->id,
                "country_id" => $city?->country_id ?? $city?->region?->country_id,
                "region_id" => $city?->region_id,
                "city_id" => $city?->id,
                "district_id" => $city?->districts?->first()?->id,
                "address" => sprintf("г. Москва, ул. Тестовая, д. %d", $i + 1),
                "lat" => 55.7 + $i / 100,
                "lng" => 37.5 + $i / 100,
            ]);

            if ($claimed) {
                OrganizationUser::query()->updateOrCreate(
                    [
                        "organization_id" => (string) $organization->id,
                        "user_id" => (string) $owner->id,
                    ],
                    $this->buildMemberAttributes(
                        position: "Owner",
                        status: "active",
                        invitedByUserId: (string) $createdBy->id,
                        joinedAtDaysAgo: $i + 1,
                    ),
                );
            }

            $memberA = $users[($i + 2) % $users->count()];
            $memberB = $users[($i + 3) % $users->count()];
            $pendingUser = $users[($i + 4) % $users->count()];
            $clientUser = $users[($i + 5) % $users->count()];

            OrganizationUser::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $memberA->id,
                ],
                $this->buildMemberAttributes(
                    position: "Administrator",
                    status: "active",
                    invitedByUserId: (string) $createdBy->id,
                    joinedAtDaysAgo: max($i, 1),
                ),
            );

            OrganizationUser::query()->updateOrCreate(
                [
                    "organization_id" => (string) $organization->id,
                    "user_id" => (string) $memberB->id,
                ],
                $this->buildMemberAttributes(
                    position: "Coach",
                    status: "active",
                    invitedByUserId: (string) $memberA->id,
                    joinedAtDaysAgo: max($i - 1, 1),
                ),
            );

            if (Schema::hasTable("organization_clients")) {
                OrganizationClient::query()->updateOrCreate(
                    [
                        "organization_id" => (string) $organization->id,
                        "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                        "subject_id" => (string) $clientUser->id,
                    ],
                    [
                        "status" => "active",
                        "added_by_user_id" => (string) $createdBy->id,
                        "joined_at" => now()->subDays(max($i, 1)),
                    ],
                );

                if ($children->isNotEmpty()) {
                    $child = $children[$i % $children->count()];
                    OrganizationClient::query()->updateOrCreate(
                        [
                            "organization_id" => (string) $organization->id,
                            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                            "subject_id" => (string) $child->id,
                        ],
                        [
                            "status" => "active",
                            "added_by_user_id" => (string) $createdBy->id,
                            "joined_at" => now()->subDays(max($i - 1, 1)),
                        ],
                    );
                }
            }

            if (Schema::hasTable("organization_join_requests")) {
                OrganizationJoinRequest::query()->updateOrCreate(
                    [
                        "organization_id" => (string) $organization->id,
                        "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                        "subject_id" => (string) $pendingUser->id,
                        "requested_by_user_id" => (string) $pendingUser->id,
                        "status" => "pending",
                    ],
                    [
                        "message" => "Хочу вступить в организацию",
                        "review_note" => null,
                        "reviewed_by_user_id" => null,
                        "reviewed_at" => null,
                    ],
                );

                if ($children->isNotEmpty()) {
                    $child = $children[($i + 1) % $children->count()];
                    OrganizationJoinRequest::query()->updateOrCreate(
                        [
                            "organization_id" => (string) $organization->id,
                            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                            "subject_id" => (string) $child->id,
                            "requested_by_user_id" => (string) $child->user_id,
                            "status" => "pending",
                        ],
                        [
                            "message" => "Прошу принять ребенка в организацию",
                            "review_note" => null,
                            "reviewed_by_user_id" => null,
                            "reviewed_at" => null,
                        ],
                    );
                }
            }
        }
    }

    private function buildMemberAttributes(
        string $position,
        string $status,
        string $invitedByUserId,
        int $joinedAtDaysAgo,
    ): array {
        $attributes = [];

        if (Schema::hasColumn("organization_users", "position")) {
            $attributes["position"] = $position;
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
