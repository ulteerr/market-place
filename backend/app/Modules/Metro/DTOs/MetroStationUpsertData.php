<?php

declare(strict_types=1);

namespace Modules\Metro\DTOs;

final readonly class MetroStationUpsertData
{
    public function __construct(
        public ?string $name,
        public ?string $externalId,
        public ?string $lineId,
        public ?float $geoLat,
        public ?float $geoLon,
        public ?bool $isClosed,
        public ?string $metroLineId,
        public ?string $cityId,
        public ?string $source,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::toNullableString($data["name"] ?? null),
            externalId: self::toNullableString($data["external_id"] ?? null),
            lineId: self::toNullableString($data["line_id"] ?? null),
            geoLat: self::toNullableFloat($data["geo_lat"] ?? null),
            geoLon: self::toNullableFloat($data["geo_lon"] ?? null),
            isClosed: isset($data["is_closed"]) ? (bool) $data["is_closed"] : null,
            metroLineId: self::toNullableString($data["metro_line_id"] ?? null),
            cityId: self::toNullableString($data["city_id"] ?? null),
            source: self::toNullableString($data["source"] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            "name" => $this->name,
            "external_id" => $this->externalId,
            "line_id" => $this->lineId,
            "geo_lat" => $this->geoLat,
            "geo_lon" => $this->geoLon,
            "is_closed" => $this->isClosed,
            "metro_line_id" => $this->metroLineId,
            "city_id" => $this->cityId,
            "source" => $this->source,
        ];

        return array_filter($result, fn(mixed $v): bool => $v !== null);
    }

    private static function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === "" ? null : $normalized;
    }

    private static function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === "") {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
