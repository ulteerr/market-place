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

    protected function paginateMethod(): string
    {
        return 'paginate';
    }

    protected function createMethod(): string
    {
        return 'create';
    }

    protected function findMethod(): string
    {
        return 'findById';
    }

    protected function updateMethod(): string
    {
        return 'update';
    }

    protected function deleteMethod(): string
    {
        return 'delete';
    }

    protected function updateArguments(string $id, array $data): array
    {
        return [$id, $data];
    }

    protected function createItem(array $data): mixed
    {
        $method = $this->createMethod();

        return $this->service()->{$method}($data);
    }

    protected function findItem(string $id): mixed
    {
        $method = $this->findMethod();

        return $this->service()->{$method}($id);
    }

    protected function updateItem(string $id, array $data): mixed
    {
        $method = $this->updateMethod();
        $args = $this->updateArguments($id, $data);

        return $this->service()->{$method}(...$args);
    }

    protected function deleteItem(string $id): void
    {
        $method = $this->deleteMethod();
        $this->service()->{$method}($id);
    }

    public function index(): JsonResponse
    {
        $method = $this->paginateMethod();
        $items = $this->service()->{$method}(20);

        return StatusResponseFactory::success($items);
    }

    public function store(): JsonResponse
    {
        $requestClass = $this->createRequestClass();
        $request = app($requestClass);

        $item = $this->createItem(
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
        $item = $this->findItem($id);

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

        $item = $this->updateItem(
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
        $this->deleteItem($id);

        return StatusResponseFactory::ok(
            'Deleted successfully'
        );
    }
}
