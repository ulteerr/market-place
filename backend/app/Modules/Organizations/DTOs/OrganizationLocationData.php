<?php

declare(strict_types=1);

namespace Modules\Organizations\DTOs;

final readonly class OrganizationLocationData
{
    /**
     * @param array<int, OrganizationMetroConnectionData> $metroConnections
     */
    public function __construct(
        public ?string $countryId,
        public ?string $regionId,
        public ?string $cityId,
        public ?string $districtId,
        public ?string $address,
        public ?float $lat,
        public ?float $lng,
        public array $metroConnections,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $metroConnectionsRaw = $data["metro_connections"] ?? [];
        if (!is_array($metroConnectionsRaw)) {
            $metroConnectionsRaw = [];
        }

        $metroConnections = [];
        foreach ($metroConnectionsRaw as $metroConnection) {
            if (!is_array($metroConnection)) {
                continue;
            }

            $metroConnections[] = OrganizationMetroConnectionData::fromArray($metroConnection);
        }

        return new self(
            countryId: self::toNullableString($data["country_id"] ?? null),
            regionId: self::toNullableString($data["region_id"] ?? null),
            cityId: self::toNullableString($data["city_id"] ?? null),
            districtId: self::toNullableString($data["district_id"] ?? null),
            address: self::toNullableString($data["address"] ?? null),
            lat: self::toNullableFloat($data["lat"] ?? null),
            lng: self::toNullableFloat($data["lng"] ?? null),
            metroConnections: $metroConnections,
        );
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
