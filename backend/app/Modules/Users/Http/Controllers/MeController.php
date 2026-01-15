<?php
declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Users\Http\Resources\UserResource;

final class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'user'   => new UserResource($request->user()),
        ]);
    }
}
