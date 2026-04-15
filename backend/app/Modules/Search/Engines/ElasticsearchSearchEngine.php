<?php

declare(strict_types=1);

namespace Modules\Search\Engines;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Search\Contracts\SearchEngineInterface;

final class ElasticsearchSearchEngine implements SearchEngineInterface
{
    private ?bool $availability = null;

    /**
     * @param array<string, mixed> $driverConfig
     */
    public function __construct(
        private readonly array $driverConfig,
        private readonly string $indexPrefix,
        private readonly bool $enabled,
    ) {}

    public function isAvailable(): bool
    {
        if ($this->availability !== null) {
            return $this->availability;
        }

        if (!$this->enabled || ($this->driverConfig["enabled"] ?? true) !== true) {
            $this->availability = false;

            return $this->availability;
        }

        try {
            $response = $this->request()
                ->timeout((float) ($this->driverConfig["availability_timeout_seconds"] ?? 0.35))
                ->get($this->healthUrl());

            $this->availability = $response->successful();
        } catch (\Throwable) {
            $this->availability = false;
        }

        return $this->availability;
    }

    public function search(string $index, string $query, array $options = []): array
    {
        if (!$this->isAvailable() || (trim($query) === "" && !is_array($options["body"] ?? null))) {
            return [
                "total" => 0,
                "items" => [],
            ];
        }

        $response = $this->request()->post(
            $this->searchUrl($index),
            $this->resolveSearchBody($query, $options),
        );

        if (!$response->successful()) {
            return [
                "total" => 0,
                "items" => [],
            ];
        }

        $hits = $response->json("hits.hits");
        if (!is_array($hits)) {
            return [
                "total" => 0,
                "items" => [],
            ];
        }

        $items = [];
        foreach ($hits as $hit) {
            $source = is_array($hit) ? $hit["_source"] ?? null : null;
            if (is_array($source)) {
                $items[] = $source;
            }
        }

        $totalValue = $response->json("hits.total.value");
        $total = is_numeric($totalValue) ? (int) $totalValue : count($items);

        return [
            "total" => $total,
            "items" => $items,
            "meta" => [
                "aggregations" => $response->json("aggregations"),
            ],
        ];
    }

    public function suggest(string $index, string $query, array $options = []): array
    {
        if (!$this->isAvailable() || trim($query) === "") {
            return [];
        }

        $size = max(1, (int) ($options["limit"] ?? 8));
        $field = is_string($options["field"] ?? null) ? $options["field"] : "name";

        $response = $this->request()->post($this->searchUrl($index), [
            "size" => $size,
            "_source" => [$field],
            "query" => [
                "match_phrase_prefix" => [
                    $field => [
                        "query" => $query,
                        "max_expansions" => 20,
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            return [];
        }

        $hits = $response->json("hits.hits");
        if (!is_array($hits)) {
            return [];
        }

        $suggestions = [];
        foreach ($hits as $hit) {
            $source = is_array($hit) ? $hit["_source"] ?? null : null;
            $value = is_array($source) ? $source[$field] ?? null : null;

            if (is_string($value) && trim($value) !== "") {
                $suggestions[] = $value;
            }
        }

        return array_values(array_unique($suggestions));
    }

    public function ensureIndex(string $index, array $definition = []): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $indexUrl = $this->indexUrl($index);

        try {
            $exists = $this->request()->head($indexUrl);
            if ($exists->successful()) {
                return true;
            }

            $response = $this->request()->put($indexUrl, $definition);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function upsertDocuments(string $index, array $documents): bool
    {
        if (!$this->isAvailable() || $documents === []) {
            return false;
        }

        $lines = [];
        foreach ($documents as $document) {
            $id = trim((string) ($document["id"] ?? ""));
            if ($id === "") {
                continue;
            }

            $lines[] = json_encode(
                [
                    "index" => [
                        "_index" => $this->resolvedIndex($index),
                        "_id" => $id,
                    ],
                ],
                JSON_UNESCAPED_UNICODE,
            );
            $lines[] = json_encode($document, JSON_UNESCAPED_UNICODE);
        }

        if ($lines === []) {
            return false;
        }

        $response = $this->request()
            ->withHeaders(["Content-Type" => "application/x-ndjson"])
            ->withBody(implode("\n", $lines) . "\n", "application/x-ndjson")
            ->post($this->bulkUrl());

        return $response->successful() && !$response->json("errors", false);
    }

    public function deleteDocuments(string $index, array $ids): bool
    {
        if (!$this->isAvailable() || $ids === []) {
            return false;
        }

        $lines = [];
        foreach ($ids as $id) {
            $id = trim($id);
            if ($id === "") {
                continue;
            }

            $lines[] = json_encode(
                [
                    "delete" => [
                        "_index" => $this->resolvedIndex($index),
                        "_id" => $id,
                    ],
                ],
                JSON_UNESCAPED_UNICODE,
            );
        }

        if ($lines === []) {
            return false;
        }

        $response = $this->request()
            ->withHeaders(["Content-Type" => "application/x-ndjson"])
            ->withBody(implode("\n", $lines) . "\n", "application/x-ndjson")
            ->post($this->bulkUrl());

        return $response->successful() && !$response->json("errors", false);
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()->timeout(
            (float) ($this->driverConfig["timeout_seconds"] ?? 1.5),
        );

        if (($this->driverConfig["verify_tls"] ?? true) === false) {
            $request = $request->withoutVerifying();
        }

        $apiKey = $this->driverConfig["api_key"] ?? null;
        $username = $this->driverConfig["username"] ?? null;
        $password = $this->driverConfig["password"] ?? null;

        if (is_string($apiKey) && $apiKey !== "") {
            return $request->withToken($apiKey);
        }

        if (is_string($username) && $username !== "") {
            return $request->withBasicAuth($username, (string) $password);
        }

        return $request;
    }

    private function searchUrl(string $index): string
    {
        return $this->indexUrl($index) . "/_search";
    }

    private function healthUrl(): string
    {
        $baseUrl = rtrim(
            (string) ($this->driverConfig["base_url"] ?? "http://elasticsearch:9200"),
            "/",
        );

        return "{$baseUrl}/_cluster/health?local=true";
    }

    private function indexUrl(string $index): string
    {
        $baseUrl = rtrim(
            (string) ($this->driverConfig["base_url"] ?? "http://elasticsearch:9200"),
            "/",
        );

        return "{$baseUrl}/" . $this->resolvedIndex($index);
    }

    private function bulkUrl(): string
    {
        $baseUrl = rtrim(
            (string) ($this->driverConfig["base_url"] ?? "http://elasticsearch:9200"),
            "/",
        );

        return "{$baseUrl}/_bulk";
    }

    private function resolvedIndex(string $index): string
    {
        return $this->indexPrefix . trim($index);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function resolveSearchBody(string $query, array $options): array
    {
        if (is_array($options["body"] ?? null)) {
            return $options["body"];
        }

        $size = max(1, (int) ($options["limit"] ?? 20));
        $from = max(0, (int) ($options["offset"] ?? 0));
        $fields = $options["fields"] ?? ["name^4", "slug^2", "short_description", "description"];

        return [
            "from" => $from,
            "size" => $size,
            "track_total_hits" => true,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "multi_match" => [
                                "query" => $query,
                                "fields" => $fields,
                                "type" => "best_fields",
                                "operator" => "or",
                                "fuzziness" => "AUTO",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
