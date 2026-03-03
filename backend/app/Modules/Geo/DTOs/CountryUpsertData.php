<?php

declare(strict_types=1);

namespace Modules\Geo\DTOs;

final readonly class CountryUpsertData
{
    public function __construct(
        public ?string $name,
        public ?string $isoCode,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $isoCode = self::toNullableString($data["iso_code"] ?? null);
        if ($isoCode !== null && $isoCode !== "") {
            $isoCode = strtoupper($isoCode);
        }

        return new self(
            name: self::toNullableString($data["name"] ?? null),
            isoCode: $isoCode,
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
                "iso_code" => $this->isoCode,
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
