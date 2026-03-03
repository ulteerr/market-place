<?php

declare(strict_types=1);

namespace Modules\Metro\DTOs;

final readonly class MetroLineUpsertData
{
    public function __construct(
        public ?string $name,
        public ?string $externalId,
        public ?string $lineId,
        public ?string $color,
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
            color: self::toNullableString($data["color"] ?? null),
            cityId: self::toNullableString($data["city_id"] ?? null),
            source: self::toNullableString($data["source"] ?? null),
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
                "external_id" => $this->externalId,
                "line_id" => $this->lineId,
                "color" => $this->color,
                "city_id" => $this->cityId,
                "source" => $this->source,
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
