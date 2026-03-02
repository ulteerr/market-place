<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

final readonly class EntitySearchFiltersDTO
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public ?string $search,
        public ?string $entitySearch,
        public ?string $countryId,
        public ?string $regionId,
        public ?string $cityId,
        public ?string $metroLineId,
        public array $extra = [],
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public static function fromArray(array $filters): self
    {
        $knownKeys = [
            "search",
            "entity_search",
            "country_id",
            "region_id",
            "city_id",
            "metro_line_id",
        ];

        $extra = [];
        foreach ($filters as $key => $value) {
            if (!in_array($key, $knownKeys, true)) {
                $extra[$key] = $value;
            }
        }

        return new self(
            search: self::toNullableString($filters["search"] ?? null),
            entitySearch: self::toNullableString($filters["entity_search"] ?? null),
            countryId: self::toNullableString($filters["country_id"] ?? null),
            regionId: self::toNullableString($filters["region_id"] ?? null),
            cityId: self::toNullableString($filters["city_id"] ?? null),
            metroLineId: self::toNullableString($filters["metro_line_id"] ?? null),
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->extra,
            "search" => $this->search ?? "",
            "entity_search" => $this->entitySearch ?? "",
            "country_id" => $this->countryId ?? "",
            "region_id" => $this->regionId ?? "",
            "city_id" => $this->cityId ?? "",
            "metro_line_id" => $this->metroLineId ?? "",
        ];
    }

    private static function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === "" ? null : $normalized;
    }
}
