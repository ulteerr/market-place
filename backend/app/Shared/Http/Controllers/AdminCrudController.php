<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Shared\Http\Responses\StatusResponseFactory;

abstract class AdminCrudController extends Controller
{
    abstract protected function service(): object;

    abstract protected function createRequestClass(): string;

    abstract protected function updateRequestClass(): string;

    abstract protected function responseFactory(): ?string;

    public function index(): JsonResponse
    {
        $items = $this->service()->paginate(perPage: 20);

        return StatusResponseFactory::success($items);
    }

    public function store(): JsonResponse
    {
        $requestClass = $this->createRequestClass();
        $request = app($requestClass);

        $item = $this->service()->create(
            $request->validated()
        );

        return StatusResponseFactory::successWithMessage(
            'Created successfully',
            $item,
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->service()->findById($id);

        if (!$item) {
            abort(404, 'Not found');
        }

        if ($factory = $this->responseFactory()) {
            return $factory::success($item);
        }

        return StatusResponseFactory::success($item);
    }

    public function update(string $id): JsonResponse
    {
        $requestClass = $this->updateRequestClass();
        $request = app($requestClass);

        $item = $this->service()->update(
            $id,
            $request->validated()
        );

        return StatusResponseFactory::successWithMessage(
            'Updated successfully',
            $item
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service()->delete($id);

        return StatusResponseFactory::ok(
            'Deleted successfully'
        );
    }
}
