<?php

declare(strict_types=1);

namespace Modules\Geo\DTOs;

final readonly class DistrictUpsertData
{
    public function __construct(
        public ?string $name,
        public ?string $cityId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::toNullableString($data["name"] ?? null),
            cityId: self::toNullableString($data["city_id"] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                "name" => $this->name,
                "city_id" => $this->cityId,
            ],
            fn(mixed $v): bool => $v !== null && $v !== "",
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
}
