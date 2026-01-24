<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Users\Http\Requests\UpdateMeRequest;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Services\UsersService;

final class MeController extends Controller
{
    public function __construct(
        private readonly UsersService $usersService
    ) {}
    
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'user'   => new UserResource($request->user()),
        ]);
    }
    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $this->usersService->updateUser(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status' => 'ok',
            'user'   => new UserResource($user),
        ]);
    }
}
