<?php

declare(strict_types=1);

namespace Modules\Children\DTOs;

final readonly class ChildUpsertData
{
    public function __construct(
        public ?string $userId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $middleName,
        public ?string $gender,
        public ?string $birthDate,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: self::toNullableString($data["user_id"] ?? null),
            firstName: self::toNullableString($data["first_name"] ?? null),
            lastName: self::toNullableString($data["last_name"] ?? null),
            middleName: self::toNullableString($data["middle_name"] ?? null),
            gender: self::toNullableString($data["gender"] ?? null),
            birthDate: self::toNullableString($data["birth_date"] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            "user_id" => $this->userId,
            "first_name" => $this->firstName,
            "last_name" => $this->lastName,
            "middle_name" => $this->middleName,
            "gender" => $this->gender,
            "birth_date" => $this->birthDate,
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
}
