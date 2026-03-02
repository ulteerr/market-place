<?php

declare(strict_types=1);

namespace Modules\Organizations\DTOs;

final readonly class OrganizationMetroConnectionData
{
    public function __construct(
        public string $metroStationId,
        public string $travelMode,
        public int $durationMinutes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metroStationId: (string) ($data["metro_station_id"] ?? ""),
            travelMode: (string) ($data["travel_mode"] ?? ""),
            durationMinutes: (int) ($data["duration_minutes"] ?? 0),
        );
    }
}
