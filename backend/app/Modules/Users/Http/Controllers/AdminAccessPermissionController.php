<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Users\Http\Resources\AccessPermissionResource;
use Modules\Users\Models\AccessPermission;

final class AdminAccessPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = AccessPermission::query()->orderBy("scope")->orderBy("code")->get();

        return StatusResponseFactory::success([
            "data" => AccessPermissionResource::collection($permissions)->resolve(),
        ]);
    }
}
