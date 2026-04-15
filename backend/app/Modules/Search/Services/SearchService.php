<?php

declare(strict_types=1);

namespace Modules\Search\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Activities\Http\Resources\ActivityFeedItemResource;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\Organization;
use Modules\Search\Contracts\SearchEngineInterface;

final class SearchService
{
    public function __construct(private readonly SearchEngineInterface $searchEngine) {}

    /**
     * @return array{
     *   queries: array<int, string>,
     *   entities: array<int, array<string, mixed>>,
     *   recent: array<int, string>
     * }
     */
    public function suggest(string $query, int $limit = 8): array
    {
        if (!$this->searchEnabled()) {
            return [
                "queries" => [],
                "entities" => [],
                "recent" => [],
            ];
        }

        $query = trim($query);
        if ($query === "") {
            return [
                "queries" => $this->popularQueries($limit),
                "entities" => [],
                "recent" => [],
            ];
        }

        if (mb_strlen($query) < 2) {
            return [
                "queries" => [],
                "entities" => [],
                "recent" => [],
            ];
        }

        return [
            "queries" => $this->querySuggestions($query, $limit),
            "entities" => $this->entitySuggestions($query, $limit),
            "recent" => [],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function search(string $query, array $filters = []): array
    {
        if (!$this->searchEnabled()) {
            return [
                "query" => trim($query),
                "items" => [],
                "pagination" => [
                    "total" => 0,
                    "per_page" => max(1, min(48, (int) ($filters["per_page"] ?? 12))),
                    "current_page" => max(1, (int) ($filters["page"] ?? 1)),
                    "last_page" => 1,
                ],
                "facets" => [
                    "categories" => [],
                    "organizations" => [],
                    "cities" => [],
                ],
            ];
        }

        $query = trim($query);
        $perPage = max(1, min(48, (int) ($filters["per_page"] ?? 12)));
        $page = max(1, (int) ($filters["page"] ?? 1));

        $engineResults = $this->searchThroughEngine($query, $filters);
        if ($engineResults !== null) {
            return $engineResults;
        }

        if (!$this->sqlFallbackEnabled()) {
            return [
                "query" => $query,
                "items" => [],
                "pagination" => [
                    "total" => 0,
                    "per_page" => $perPage,
                    "current_page" => $page,
                    "last_page" => 1,
                ],
                "facets" => [
                    "categories" => [],
                    "organizations" => [],
                    "cities" => [],
                ],
            ];
        }

        $paginator = $this->searchThroughSql($query, $filters, $perPage, $page);
        $collection = $paginator->getCollection();

        return [
            "query" => $query,
            "items" => ActivityFeedItemResource::collection($collection)->resolve(),
            "pagination" => [
                "total" => $paginator->total(),
                "per_page" => $paginator->perPage(),
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
            ],
            "facets" => $this->buildFacets($query, $filters),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function popularQueries(int $limit): array
    {
        return Activity::query()
            ->where("status", "published")
            ->orderByDesc("is_featured")
            ->orderByDesc("published_at")
            ->orderByDesc("created_at")
            ->limit($limit)
            ->pluck("name")
            ->filter(fn($value) => is_string($value) && trim($value) !== "")
            ->map(fn($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function querySuggestions(string $query, int $limit): array
    {
        $suggestions = collect();
        if ($this->searchEngine->isAvailable()) {
            $suggestions = collect(
                $this->searchEngine->suggest("activities", $query, [
                    "limit" => $limit,
                    "field" => "name",
                ]),
            );
        }

        $fallback = collect(
            Activity::query()
                ->where("status", "published")
                ->orderByDesc("is_featured")
                ->orderByDesc("published_at")
                ->limit(100)
                ->get(["name", "slug", "short_description"])
                ->filter(
                    fn(Activity $activity): bool => $this->matchesText($activity->name, $query) ||
                        $this->matchesText($activity->slug, $query) ||
                        $this->matchesText($activity->short_description, $query),
                )
                ->sortBy(fn(Activity $activity): int => $this->textRank($activity->name, $query))
                ->take($limit)
                ->pluck("name")
                ->all(),
        );

        return $suggestions
            ->merge($fallback)
            ->filter(fn($value) => is_string($value) && trim($value) !== "")
            ->map(fn($value) => trim((string) $value))
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entitySuggestions(string $query, int $limit): array
    {
        $entityLimit = max(2, (int) ceil($limit / 2));

        $categories = collect(
            Category::query()
                ->with(["parent:id,name,slug"])
                ->where("is_active", true)
                ->orderBy("sort_order")
                ->orderBy("name")
                ->limit(100)
                ->get()
                ->filter(
                    fn(Category $category): bool => $this->matchesText($category->name, $query),
                )
                ->sortBy(fn(Category $category): int => $this->textRank($category->name, $query))
                ->take($entityLimit)
                ->map(function (Category $category): array {
                    $parent = $category->parent;
                    $url = $parent
                        ? "/catalog/{$parent->slug}/{$category->slug}"
                        : "/catalog/{$category->slug}";

                    return [
                        "id" => (string) $category->id,
                        "type" => "category",
                        "label" => $category->name,
                        "subtitle" => $parent?->name,
                        "url" => $url,
                    ];
                })
                ->all(),
        );

        $organizations = collect(
            Organization::query()
                ->orderByDesc("created_at")
                ->limit(100)
                ->get()
                ->filter(
                    fn(Organization $organization): bool => $this->matchesText(
                        $organization->name,
                        $query,
                    ),
                )
                ->sortBy(
                    fn(Organization $organization): int => $this->textRank(
                        $organization->name,
                        $query,
                    ),
                )
                ->take($entityLimit)
                ->map(function (Organization $organization) use ($query): array {
                    return [
                        "id" => (string) $organization->id,
                        "type" => "organization",
                        "label" => $organization->name,
                        "subtitle" => "Организация",
                        "url" =>
                            "/catalog?q=" .
                            urlencode($query) .
                            "&organization_id=" .
                            urlencode((string) $organization->id),
                    ];
                })
                ->all(),
        );

        $activities = collect(
            Activity::query()
                ->with([
                    "primaryCategory.parent:id,name,slug",
                    "primaryCategory:id,name,slug,parent_id",
                ])
                ->where("status", "published")
                ->orderByDesc("is_featured")
                ->orderByDesc("published_at")
                ->limit(100)
                ->get()
                ->filter(
                    fn(Activity $activity): bool => $this->matchesText($activity->name, $query),
                )
                ->sortBy(fn(Activity $activity): int => $this->textRank($activity->name, $query))
                ->take($entityLimit)
                ->map(function (Activity $activity): array {
                    $category = $activity->primaryCategory->first();
                    $parent = $category?->parent;

                    return [
                        "id" => (string) $activity->id,
                        "type" => "activity",
                        "label" => $activity->name,
                        "subtitle" => $category?->name,
                        "url" => $this->activityUrl($activity, $parent?->slug, $category?->slug),
                    ];
                })
                ->all(),
        );

        return $categories
            ->merge($organizations)
            ->merge($activities)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildFacets(string $query, array $filters): array
    {
        $ids = $this->buildActivitySearchQuery($query, $filters)
            ->limit(200)
            ->pluck("activities.id")
            ->filter(fn($value) => is_string($value) && $value !== "")
            ->values();

        if ($ids->isEmpty()) {
            return [
                "categories" => [],
                "organizations" => [],
                "cities" => [],
            ];
        }

        $categories = Category::query()
            ->select([
                "categories.id",
                "categories.name",
                "categories.slug",
                "categories.parent_id",
            ])
            ->with(["parent:id,name,slug"])
            ->join("activity_categories", "activity_categories.category_id", "=", "categories.id")
            ->whereIn("activity_categories.activity_id", $ids->all())
            ->groupBy("categories.id", "categories.name", "categories.slug", "categories.parent_id")
            ->selectRaw("COUNT(DISTINCT activity_categories.activity_id) as hits")
            ->orderByDesc("hits")
            ->orderBy("categories.name")
            ->limit(8)
            ->get()
            ->map(function (Category $category): array {
                return [
                    "id" => (string) $category->id,
                    "name" => $category->name,
                    "slug" => $category->slug,
                    "parent" => $category->parent
                        ? [
                            "id" => (string) $category->parent->id,
                            "name" => $category->parent->name,
                            "slug" => $category->parent->slug,
                        ]
                        : null,
                    "hits" => (int) ($category->hits ?? 0),
                ];
            })
            ->values()
            ->all();

        $organizations = Organization::query()
            ->select(["organizations.id", "organizations.name"])
            ->join("activities", "activities.organization_id", "=", "organizations.id")
            ->whereIn("activities.id", $ids->all())
            ->groupBy("organizations.id", "organizations.name")
            ->selectRaw("COUNT(DISTINCT activities.id) as hits")
            ->orderByDesc("hits")
            ->orderBy("organizations.name")
            ->limit(8)
            ->get()
            ->map(
                fn(Organization $organization): array => [
                    "id" => (string) $organization->id,
                    "name" => $organization->name,
                    "hits" => (int) ($organization->hits ?? 0),
                ],
            )
            ->values()
            ->all();

        $cities = Activity::query()
            ->join(
                "organization_locations",
                "organization_locations.id",
                "=",
                "activities.location_id",
            )
            ->join("cities", "cities.id", "=", "organization_locations.city_id")
            ->whereIn("activities.id", $ids->all())
            ->groupBy("cities.id", "cities.name")
            ->selectRaw("cities.id, cities.name, COUNT(DISTINCT activities.id) as hits")
            ->orderByDesc("hits")
            ->orderBy("cities.name")
            ->limit(8)
            ->get()
            ->map(
                fn(object $city): array => [
                    "id" => (string) $city->id,
                    "name" => (string) $city->name,
                    "hits" => (int) $city->hits,
                ],
            )
            ->values()
            ->all();

        return [
            "categories" => $categories,
            "organizations" => $organizations,
            "cities" => $cities,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildActivitySearchQuery(string $query, array $filters): Builder
    {
        $builder = Activity::query()
            ->select([
                "id",
                "organization_id",
                "location_id",
                "name",
                "slug",
                "short_description",
                "min_age",
                "max_age",
                "price_from",
                "price_to",
                "currency",
                "is_featured",
                "published_at",
                "created_at",
            ])
            ->with($this->feedRelations())
            ->where("status", "published");

        if ($query !== "") {
            $normalized = $this->normalizedQuery($query);
            $like = "%" . $normalized . "%";
            $containsPatterns = $this->stringVariants($query, "%s");

            $builder->where(function (Builder $searchBuilder) use ($like, $containsPatterns): void {
                $searchBuilder
                    ->whereRaw("LOWER(name) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(slug) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(short_description) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(description) LIKE ?", [$like])
                    ->orWhereHas("organization", function (Builder $organizationBuilder) use (
                        $like,
                    ): void {
                        $organizationBuilder->whereRaw("LOWER(name) LIKE ?", [$like]);
                    })
                    ->orWhereHas("location", function (Builder $locationBuilder) use ($like): void {
                        $locationBuilder->whereRaw("LOWER(address) LIKE ?", [$like]);
                    })
                    ->orWhereHas("primaryCategory", function (Builder $categoryBuilder) use (
                        $like,
                    ): void {
                        $categoryBuilder
                            ->whereRaw("LOWER(name) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(slug) LIKE ?", [$like]);
                    });

                foreach ($containsPatterns as $pattern) {
                    $searchBuilder
                        ->orWhere("name", "like", $pattern)
                        ->orWhere("slug", "like", $pattern)
                        ->orWhere("short_description", "like", $pattern)
                        ->orWhere("description", "like", $pattern);
                }
            });
        }

        if (!empty($filters["organization_id"])) {
            $builder->where("organization_id", (string) $filters["organization_id"]);
        }

        if (!empty($filters["category_id"])) {
            $categoryId = (string) $filters["category_id"];
            $builder->whereHas("primaryCategory", function (Builder $categoryBuilder) use (
                $categoryId,
            ): void {
                $categoryBuilder->where("categories.id", $categoryId);
            });
        }

        if (!empty($filters["root_category_id"])) {
            $rootCategoryId = (string) $filters["root_category_id"];
            $builder->whereHas("primaryCategory", function (Builder $categoryBuilder) use (
                $rootCategoryId,
            ): void {
                $categoryBuilder->where("categories.parent_id", $rootCategoryId);
            });
        }

        if (!empty($filters["city_id"])) {
            $cityId = (string) $filters["city_id"];
            $builder->whereHas("location", function (Builder $locationBuilder) use ($cityId): void {
                $locationBuilder->where("city_id", $cityId);
            });
        }

        if (
            array_key_exists("is_featured", $filters) &&
            $filters["is_featured"] !== null &&
            $filters["is_featured"] !== ""
        ) {
            $builder->where(
                "is_featured",
                filter_var($filters["is_featured"], FILTER_VALIDATE_BOOL),
            );
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function searchThroughEngine(string $query, array $filters): ?array
    {
        if (!$this->searchEngine->isAvailable() || !$this->canUseEngineSearch($query, $filters)) {
            return null;
        }

        $perPage = max(1, min(48, (int) ($filters["per_page"] ?? 12)));
        $page = max(1, (int) ($filters["page"] ?? 1));
        $offset = ($page - 1) * $perPage;
        $enginePayload = $this->searchEngine->search("activities", $query, [
            "body" => $this->buildEngineBody($query, $filters, $perPage, $offset),
        ]);

        $ids = collect($enginePayload["items"] ?? [])
            ->map(fn($item) => is_array($item) ? (string) ($item["id"] ?? "") : "")
            ->filter(fn(string $id) => $id !== "")
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $items = Activity::query()
            ->select([
                "id",
                "organization_id",
                "location_id",
                "name",
                "slug",
                "short_description",
                "min_age",
                "max_age",
                "price_from",
                "price_to",
                "currency",
                "is_featured",
                "published_at",
                "created_at",
            ])
            ->with($this->feedRelations())
            ->where("status", "published")
            ->whereIn("id", $ids->all())
            ->get()
            ->sortBy(fn(Activity $activity): int => (int) $ids->search((string) $activity->id))
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        $aggregations = is_array($enginePayload["meta"]["aggregations"] ?? null)
            ? $enginePayload["meta"]["aggregations"]
            : [];

        return [
            "query" => $query,
            "items" => ActivityFeedItemResource::collection($items)->resolve(),
            "pagination" => [
                "total" => max($items->count(), (int) ($enginePayload["total"] ?? $items->count())),
                "per_page" => $perPage,
                "current_page" => $page,
                "last_page" => max(
                    1,
                    (int) ceil(
                        max($items->count(), (int) ($enginePayload["total"] ?? $items->count())) /
                            $perPage,
                    ),
                ),
            ],
            "facets" => $this->buildEngineFacets($aggregations),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function searchThroughSql(
        string $query,
        array $filters,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $resultsQuery = $this->buildActivitySearchQuery($query, $filters);
        $this->applySort($resultsQuery, $query, $filters);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $resultsQuery->paginate($perPage, ["*"], "page", $page);
        $paginator->getCollection()->loadMissing($this->feedRelations());

        return $paginator;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applySort(Builder $builder, string $query, array $filters): void
    {
        $sortBy = trim((string) ($filters["sort_by"] ?? ""));
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        if ($sortBy === "name") {
            $builder->orderBy("name", $sortDir)->orderByDesc("published_at");

            return;
        }

        if ($sortBy === "published_at" || $sortBy === "created_at") {
            $builder->orderBy($sortBy, $sortDir)->orderBy("name");

            return;
        }

        if ($query !== "") {
            $normalized = $this->normalizedQuery($query);
            $like = "%" . $normalized . "%";
            $prefix = $normalized . "%";

            $builder
                ->orderByRaw(
                    "CASE
                        WHEN LOWER(name) = ? THEN 0
                        WHEN LOWER(name) LIKE ? THEN 1
                        WHEN LOWER(name) LIKE ? THEN 2
                        ELSE 3
                    END",
                    [$normalized, $prefix, $like],
                )
                ->orderByDesc("is_featured")
                ->orderByDesc("published_at")
                ->orderByDesc("created_at")
                ->orderBy("name");

            return;
        }

        $builder->orderByDesc("published_at")->orderByDesc("created_at")->orderBy("name");
    }

    /**
     * @return array<int, string>
     */
    private function feedRelations(): array
    {
        return [
            "organization:id,name",
            "location:id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id",
            "primaryCategory.parent:id,name,slug",
            "cover:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
        ];
    }

    private function normalizedQuery(string $query): string
    {
        return mb_strtolower(trim($query), "UTF-8");
    }

    /**
     * @return array<int, string>
     */
    private function stringVariants(string $query, string $mask = "%s"): array
    {
        $trimmed = trim($query);
        if ($trimmed === "") {
            return [];
        }

        $variants = [
            sprintf($mask, $trimmed),
            sprintf($mask, $this->normalizedQuery($trimmed)),
            sprintf($mask, mb_strtoupper($trimmed, "UTF-8")),
            sprintf($mask, mb_convert_case($trimmed, MB_CASE_TITLE, "UTF-8")),
        ];

        return array_values(array_unique($variants));
    }

    private function activityUrl(
        Activity $activity,
        ?string $rootSlug = null,
        ?string $leafSlug = null,
    ): string {
        $publicKey = sprintf("%s-%s", (string) $activity->slug, (string) $activity->id);

        if ($rootSlug && $leafSlug) {
            return "/{$rootSlug}/{$leafSlug}/{$publicKey}";
        }

        if ($leafSlug) {
            return "/{$leafSlug}/{$publicKey}";
        }

        return "/activities/{$publicKey}";
    }

    private function matchesText(?string $value, string $query): bool
    {
        $subject = trim((string) $value);
        $needle = trim($query);

        if ($subject === "" || $needle === "") {
            return false;
        }

        return mb_stripos($subject, $needle, 0, "UTF-8") !== false;
    }

    private function textRank(?string $value, string $query): int
    {
        $subject = $this->normalizedQuery((string) $value);
        $needle = $this->normalizedQuery($query);

        if ($subject === "" || $needle === "") {
            return 3;
        }

        if ($subject === $needle) {
            return 0;
        }

        if (str_starts_with($subject, $needle)) {
            return 1;
        }

        if (str_contains($subject, $needle)) {
            return 2;
        }

        return 3;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function canUseEngineSearch(string $query, array $filters): bool
    {
        return trim($query) !== "" || $this->hasStructuredFilters($filters);
    }

    private function searchEnabled(): bool
    {
        return (bool) config("search.enabled", true);
    }

    private function sqlFallbackEnabled(): bool
    {
        return (bool) config("search.sql_fallback_enabled", true);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildEngineBody(string $query, array $filters, int $limit, int $offset): array
    {
        $must = [];
        $filterClauses = [];

        if (trim($query) !== "") {
            $must[] = [
                "multi_match" => [
                    "query" => $query,
                    "fields" => [
                        "name^5",
                        "slug^4",
                        "short_description^2",
                        "description",
                        "organization.name^2",
                        "category.name^2",
                        "root_category.name^2",
                        "city.name",
                        "location.address",
                    ],
                    "type" => "best_fields",
                    "operator" => "or",
                    "fuzziness" => "AUTO",
                ],
            ];
        }

        foreach (["organization_id", "category_id", "root_category_id", "city_id"] as $key) {
            $value = trim((string) ($filters[$key] ?? ""));
            if ($value !== "") {
                $filterClauses[] = [
                    "term" => [
                        $key => $value,
                    ],
                ];
            }
        }

        if (
            array_key_exists("is_featured", $filters) &&
            $filters["is_featured"] !== null &&
            $filters["is_featured"] !== ""
        ) {
            $filterClauses[] = [
                "term" => [
                    "is_featured" => filter_var($filters["is_featured"], FILTER_VALIDATE_BOOL),
                ],
            ];
        }

        return [
            "from" => $offset,
            "size" => $limit,
            "track_total_hits" => true,
            "_source" => true,
            "query" => [
                "bool" => [
                    "must" => $must === [] ? [["match_all" => (object) []]] : $must,
                    "filter" => $filterClauses,
                ],
            ],
            "sort" => $this->buildEngineSort($query, $filters),
            "aggs" => [
                "categories" => [
                    "terms" => [
                        "field" => "category_id",
                        "size" => 8,
                    ],
                    "aggs" => [
                        "label" => [
                            "top_hits" => [
                                "_source" => [
                                    "includes" => [
                                        "category.id",
                                        "category.name",
                                        "category.slug",
                                        "root_category.id",
                                        "root_category.name",
                                        "root_category.slug",
                                    ],
                                ],
                                "size" => 1,
                            ],
                        ],
                    ],
                ],
                "organizations" => [
                    "terms" => [
                        "field" => "organization_id",
                        "size" => 8,
                    ],
                    "aggs" => [
                        "label" => [
                            "top_hits" => [
                                "_source" => [
                                    "includes" => ["organization.id", "organization.name"],
                                ],
                                "size" => 1,
                            ],
                        ],
                    ],
                ],
                "cities" => [
                    "terms" => [
                        "field" => "city_id",
                        "size" => 8,
                    ],
                    "aggs" => [
                        "label" => [
                            "top_hits" => [
                                "_source" => [
                                    "includes" => ["city.id", "city.name"],
                                ],
                                "size" => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function buildEngineSort(string $query, array $filters): array
    {
        $sortBy = trim((string) ($filters["sort_by"] ?? ""));
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc"));
        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        if ($sortBy === "name") {
            return [
                ["name.keyword" => ["order" => $sortDir]],
                ["published_at" => ["order" => "desc"]],
            ];
        }

        if ($sortBy === "published_at" || $sortBy === "created_at") {
            return [[$sortBy => ["order" => $sortDir]], ["name.keyword" => ["order" => "asc"]]];
        }

        if (trim($query) !== "") {
            return [
                ["_score" => ["order" => "desc"]],
                ["is_featured" => ["order" => "desc"]],
                ["published_at" => ["order" => "desc"]],
                ["created_at" => ["order" => "desc"]],
            ];
        }

        return [
            ["is_featured" => ["order" => "desc"]],
            ["published_at" => ["order" => "desc"]],
            ["created_at" => ["order" => "desc"]],
        ];
    }

    /**
     * @param array<string, mixed> $aggregations
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildEngineFacets(array $aggregations): array
    {
        return [
            "categories" => $this->mapCategoryBuckets($aggregations["categories"]["buckets"] ?? []),
            "organizations" => $this->mapOrganizationBuckets(
                $aggregations["organizations"]["buckets"] ?? [],
            ),
            "cities" => $this->mapCityBuckets($aggregations["cities"]["buckets"] ?? []),
        ];
    }

    /**
     * @param mixed $buckets
     * @return array<int, array<string, mixed>>
     */
    private function mapCategoryBuckets(mixed $buckets): array
    {
        if (!is_array($buckets)) {
            return [];
        }

        $items = [];
        foreach ($buckets as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }

            $source = $bucket["label"]["hits"]["hits"][0]["_source"] ?? null;
            if (!is_array($source) || !is_array($source["category"] ?? null)) {
                continue;
            }

            $category = $source["category"];
            $root = is_array($source["root_category"] ?? null) ? $source["root_category"] : null;
            $items[] = [
                "id" => (string) ($category["id"] ?? ""),
                "name" => (string) ($category["name"] ?? ""),
                "slug" => (string) ($category["slug"] ?? ""),
                "parent" => $root
                    ? [
                        "id" => (string) ($root["id"] ?? ""),
                        "name" => (string) ($root["name"] ?? ""),
                        "slug" => (string) ($root["slug"] ?? ""),
                    ]
                    : null,
                "hits" => (int) ($bucket["doc_count"] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param mixed $buckets
     * @return array<int, array<string, mixed>>
     */
    private function mapOrganizationBuckets(mixed $buckets): array
    {
        if (!is_array($buckets)) {
            return [];
        }

        $items = [];
        foreach ($buckets as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }

            $source = $bucket["label"]["hits"]["hits"][0]["_source"] ?? null;
            if (!is_array($source) || !is_array($source["organization"] ?? null)) {
                continue;
            }

            $organization = $source["organization"];
            $items[] = [
                "id" => (string) ($organization["id"] ?? ""),
                "name" => (string) ($organization["name"] ?? ""),
                "hits" => (int) ($bucket["doc_count"] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param mixed $buckets
     * @return array<int, array<string, mixed>>
     */
    private function mapCityBuckets(mixed $buckets): array
    {
        if (!is_array($buckets)) {
            return [];
        }

        $items = [];
        foreach ($buckets as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }

            $source = $bucket["label"]["hits"]["hits"][0]["_source"] ?? null;
            if (!is_array($source) || !is_array($source["city"] ?? null)) {
                continue;
            }

            $city = $source["city"];
            $items[] = [
                "id" => (string) ($city["id"] ?? ""),
                "name" => (string) ($city["name"] ?? ""),
                "hits" => (int) ($bucket["doc_count"] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function hasStructuredFilters(array $filters): bool
    {
        foreach (
            ["organization_id", "category_id", "root_category_id", "city_id", "is_featured"]
            as $key
        ) {
            $value = $filters[$key] ?? null;
            if ($value !== null && (string) $value !== "") {
                return true;
            }
        }

        return false;
    }
}
