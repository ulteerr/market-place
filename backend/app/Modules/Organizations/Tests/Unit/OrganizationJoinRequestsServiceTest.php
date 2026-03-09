<?php

declare(strict_types=1);

namespace Modules\Organizations\Tests\Unit;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Repositories\OrganizationJoinRequestsRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Organizations\Services\OrganizationJoinRequestsService;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OrganizationJoinRequestsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function submit_fails_when_user_subject_is_not_actor(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $anotherUser = User::factory()->create();

        $repo = Mockery::mock(OrganizationJoinRequestsRepositoryInterface::class);
        $repo->shouldNotReceive("create");
        $repo->shouldNotReceive("findPendingByOrganizationAndSubject");

        $organizationsRepo = Mockery::mock(OrganizationsRepositoryInterface::class);
        $organizationsRepo
            ->shouldReceive("findById")
            ->once()
            ->with((string) $organization->id)
            ->andReturn($organization);

        $service = new OrganizationJoinRequestsService($repo, $organizationsRepo);

        $this->expectException(ValidationException::class);
        $service->submit(
            (string) $organization->id,
            $actor,
            OrganizationJoinRequest::SUBJECT_TYPE_USER,
            (string) $anotherUser->id,
            "Invalid self request",
        );
    }

    #[Test]
    public function submit_fails_when_child_does_not_belong_to_actor(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $childOwner = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => (string) $childOwner->id,
        ]);

        $repo = Mockery::mock(OrganizationJoinRequestsRepositoryInterface::class);
        $repo->shouldNotReceive("create");
        $repo->shouldNotReceive("findPendingByOrganizationAndSubject");

        $organizationsRepo = Mockery::mock(OrganizationsRepositoryInterface::class);
        $organizationsRepo
            ->shouldReceive("findById")
            ->once()
            ->with((string) $organization->id)
            ->andReturn($organization);

        $service = new OrganizationJoinRequestsService($repo, $organizationsRepo);

        $this->expectException(ValidationException::class);
        $service->submit(
            (string) $organization->id,
            $actor,
            OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            (string) $child->id,
            "Invalid child request",
        );
    }

    #[Test]
    public function list_for_organization_fails_for_outsider(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $organization = Organization::factory()->create([
            "owner_user_id" => (string) $owner->id,
        ]);

        $repo = Mockery::mock(OrganizationJoinRequestsRepositoryInterface::class);
        $repo->shouldNotReceive("paginateForOrganization");

        $organizationsRepo = Mockery::mock(OrganizationsRepositoryInterface::class);
        $organizationsRepo
            ->shouldReceive("findById")
            ->once()
            ->with((string) $organization->id)
            ->andReturn($organization);

        $service = new OrganizationJoinRequestsService($repo, $organizationsRepo);

        $this->expectException(AuthorizationException::class);
        $service->listForOrganization((string) $organization->id, $outsider);
    }

    #[Test]
    public function list_for_organization_allows_user_with_org_members_read_permission(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $adminRole = Role::factory()->admin()->create();
        $orgAdmin->roles()->syncWithoutDetaching([$adminRole->id]);
        $organization = Organization::factory()->create([
            "owner_user_id" => (string) $owner->id,
        ]);

        $expectedPaginator = new LengthAwarePaginator(new Collection(), 0, 20, 1);

        $repo = Mockery::mock(OrganizationJoinRequestsRepositoryInterface::class);
        $repo
            ->shouldReceive("paginateForOrganization")
            ->once()
            ->with((string) $organization->id, 20, [])
            ->andReturn($expectedPaginator);

        $organizationsRepo = Mockery::mock(OrganizationsRepositoryInterface::class);
        $organizationsRepo
            ->shouldReceive("findById")
            ->once()
            ->with((string) $organization->id)
            ->andReturn($organization);

        $service = new OrganizationJoinRequestsService($repo, $organizationsRepo);
        $result = $service->listForOrganization((string) $organization->id, $orgAdmin, 20, []);

        $this->assertSame($expectedPaginator, $result);
    }
}
