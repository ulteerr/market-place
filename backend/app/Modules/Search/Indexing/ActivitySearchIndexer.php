<?php

declare(strict_types=1);

namespace Modules\Search\Indexing;

use Modules\Activities\Models\Activity;
use Modules\Search\Contracts\SearchEngineInterface;

final class ActivitySearchIndexer
{
    private const INDEX = "activities";

    public function __construct(
        private readonly SearchEngineInterface $searchEngine,
        private readonly ActivitySearchDocumentFactory $documentFactory,
    ) {}

    public function ensureIndex(): bool
    {
        return $this->searchEngine->ensureIndex(self::INDEX, $this->definition());
    }

    public function sync(Activity $activity): bool
    {
        if (!$this->searchEngine->isAvailable()) {
            return false;
        }

        if ((string) $activity->status !== "published") {
            return $this->delete((string) $activity->id);
        }

        $this->ensureIndex();

        return $this->searchEngine->upsertDocuments(self::INDEX, [
            $this->documentFactory->make($activity),
        ]);
    }

    /**
     * @param iterable<Activity> $activities
     */
    public function syncMany(iterable $activities): bool
    {
        if (!$this->searchEngine->isAvailable()) {
            return false;
        }

        $this->ensureIndex();

        $documents = [];
        foreach ($activities as $activity) {
            if ((string) $activity->status !== "published") {
                continue;
            }

            $documents[] = $this->documentFactory->make($activity);
        }

        if ($documents === []) {
            return true;
        }

        return $this->searchEngine->upsertDocuments(self::INDEX, $documents);
    }

    public function delete(string $activityId): bool
    {
        if (!$this->searchEngine->isAvailable()) {
            return false;
        }

        $this->ensureIndex();

        return $this->searchEngine->deleteDocuments(self::INDEX, [$activityId]);
    }

    /**
     * @param array<int, string> $activityIds
     */
    public function deleteMany(array $activityIds): bool
    {
        if (!$this->searchEngine->isAvailable()) {
            return false;
        }

        $ids = array_values(array_filter(array_map("trim", $activityIds), fn($id) => $id !== ""));
        if ($ids === []) {
            return true;
        }

        $this->ensureIndex();

        return $this->searchEngine->deleteDocuments(self::INDEX, $ids);
    }

    private function definition(): array
    {
        return [
            "settings" => [
                "analysis" => [
                    "normalizer" => [
                        "lowercase_normalizer" => [
                            "type" => "custom",
                            "filter" => ["lowercase", "asciifolding"],
                        ],
                    ],
                ],
            ],
            "mappings" => [
                "dynamic" => false,
                "properties" => [
                    "id" => ["type" => "keyword"],
                    "name" => [
                        "type" => "text",
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "normalizer" => "lowercase_normalizer",
                            ],
                        ],
                    ],
                    "slug" => [
                        "type" => "keyword",
                        "normalizer" => "lowercase_normalizer",
                    ],
                    "short_description" => ["type" => "text"],
                    "description" => ["type" => "text"],
                    "organization_id" => ["type" => "keyword"],
                    "organization" => [
                        "properties" => [
                            "id" => ["type" => "keyword"],
                            "name" => [
                                "type" => "text",
                                "fields" => [
                                    "keyword" => [
                                        "type" => "keyword",
                                        "normalizer" => "lowercase_normalizer",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "location_id" => ["type" => "keyword"],
                    "location" => [
                        "properties" => [
                            "id" => ["type" => "keyword"],
                            "address" => ["type" => "text"],
                        ],
                    ],
                    "city_id" => ["type" => "keyword"],
                    "city" => [
                        "properties" => [
                            "id" => ["type" => "keyword"],
                            "name" => [
                                "type" => "text",
                                "fields" => [
                                    "keyword" => [
                                        "type" => "keyword",
                                        "normalizer" => "lowercase_normalizer",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "category_id" => ["type" => "keyword"],
                    "category" => [
                        "properties" => [
                            "id" => ["type" => "keyword"],
                            "name" => [
                                "type" => "text",
                                "fields" => [
                                    "keyword" => [
                                        "type" => "keyword",
                                        "normalizer" => "lowercase_normalizer",
                                    ],
                                ],
                            ],
                            "slug" => [
                                "type" => "keyword",
                                "normalizer" => "lowercase_normalizer",
                            ],
                        ],
                    ],
                    "root_category_id" => ["type" => "keyword"],
                    "root_category" => [
                        "properties" => [
                            "id" => ["type" => "keyword"],
                            "name" => [
                                "type" => "text",
                                "fields" => [
                                    "keyword" => [
                                        "type" => "keyword",
                                        "normalizer" => "lowercase_normalizer",
                                    ],
                                ],
                            ],
                            "slug" => [
                                "type" => "keyword",
                                "normalizer" => "lowercase_normalizer",
                            ],
                        ],
                    ],
                    "status" => ["type" => "keyword"],
                    "is_featured" => ["type" => "boolean"],
                    "published_at" => ["type" => "date"],
                    "created_at" => ["type" => "date"],
                    "updated_at" => ["type" => "date"],
                ],
            ],
        ];
    }
}
