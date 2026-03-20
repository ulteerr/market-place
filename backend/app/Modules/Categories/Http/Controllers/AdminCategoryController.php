<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Controllers;

use App\Shared\Http\Responses\StatusResponseFactory;
use App\Shared\Http\Controllers\AdminCrudController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Categories\Http\Requests\CreateAdminCategoryRequest;
use Modules\Categories\Http\Requests\UpdateAdminCategoryRequest;
use Modules\Categories\Http\Responses\CategoryResponseFactory;
use Modules\Categories\Models\Category;
use Modules\Categories\Services\CategoriesService;

final class AdminCategoryController extends AdminCrudController
{
    public function __construct(private readonly CategoriesService $categoriesService) {}

    protected function service(): object
    {
        return $this->categoriesService;
    }

    protected function createRequestClass(): string
    {
        return CreateAdminCategoryRequest::class;
    }

    protected function updateRequestClass(): string
    {
        return UpdateAdminCategoryRequest::class;
    }

    protected function responseFactory(): ?string
    {
        return CategoryResponseFactory::class;
    }

    protected function createMethod(): string
    {
        return "create";
    }

    protected function findMethod(): string
    {
        return "findById";
    }

    protected function updateMethod(): string
    {
        return "update";
    }

    protected function policyModelClass(): ?string
    {
        return Category::class;
    }

    public function tree(Request $request): JsonResponse
    {
        $onlyActive = filter_var($request->query("only_active", true), FILTER_VALIDATE_BOOL);
        $items = $this->categoriesService->getTree($onlyActive);

        return CategoryResponseFactory::collection($items);
    }

    public function slugPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "parent_id" => ["nullable", "uuid", "exists:categories,id"],
            "ignore_id" => ["nullable", "uuid", "exists:categories,id"],
        ]);

        $slug = $this->categoriesService->previewSlug(
            (string) $validated["name"],
            isset($validated["parent_id"]) ? (string) $validated["parent_id"] : null,
            isset($validated["ignore_id"]) ? (string) $validated["ignore_id"] : null,
        );

        return StatusResponseFactory::success([
            "slug" => $slug,
        ]);
    }
}
