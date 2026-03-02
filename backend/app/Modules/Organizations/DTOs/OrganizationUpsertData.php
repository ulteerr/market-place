<?php

declare(strict_types=1);

namespace Modules\Organizations\DTOs;

use Illuminate\Support\Arr;

final readonly class OrganizationUpsertData
{
    /**
     * @param array<string, mixed> $organizationAttributes
     * @param array<int, OrganizationLocationData> $locations
     */
    public function __construct(
        public array $organizationAttributes,
        public array $locations,
        public bool $hasLocations,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $hasLocations = array_key_exists("locations", $data);
        $locationsRaw = $data["locations"] ?? [];
        if (!is_array($locationsRaw)) {
            $locationsRaw = [];
        }

        $locations = [];
        foreach ($locationsRaw as $location) {
            if (!is_array($location)) {
                continue;
            }

            $locations[] = OrganizationLocationData::fromArray($location);
        }

        return new self(
            organizationAttributes: Arr::except($data, ["locations"]),
            locations: $locations,
            hasLocations: $hasLocations,
        );
    }
}
