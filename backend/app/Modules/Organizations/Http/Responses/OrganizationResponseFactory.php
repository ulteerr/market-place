<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Organizations\Http\Resources\OrganizationResource;
use Modules\Organizations\Models\Organization;

final class OrganizationResponseFactory
{
    public static function success(Organization $organization, int $status = 200): JsonResponse
    {
        $organization->loadMissing([
            "owner:id,first_name,last_name,middle_name,email",
            "locations",
        ]);

        return StatusResponseFactory::success(new OrganizationResource($organization), $status);
    }

    public static function paginated(
        LengthAwarePaginator $organizations,
        int $status = 200,
    ): JsonResponse {
        $collection = $organizations->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing([
                "owner:id,first_name,last_name,middle_name,email",
                "locations",
            ]);
        }

        return StatusResponseFactory::paginated(
            $organizations,
            OrganizationResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Organization $organization,
        int $status = 200,
    ): JsonResponse {
        $organization->loadMissing([
            "owner:id,first_name,last_name,middle_name,email",
            "locations",
        ]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new OrganizationResource($organization))->resolve(),
            $status,
        );
    }
}
