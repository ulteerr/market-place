<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;

final class BirthDateRules
{
    /**
     * @return array<int, mixed>
     */
    public static function forUsers(?int $minAgeYears = null, ?bool $disallowFuture = null): array
    {
        $resolvedMinAge =
            $minAgeYears ?? self::normalizeInt(config("birth-date.users.min_age_years"));
        $resolvedDisallowFuture =
            $disallowFuture ?? (bool) config("birth-date.users.disallow_future", true);

        return self::base($resolvedDisallowFuture, $resolvedMinAge);
    }

    /**
     * @param Closure(): (DateTimeInterface|string|null)|null $parentBirthDateResolver
     * @return array<int, mixed>
     */
    public static function forChildren(
        ?Closure $parentBirthDateResolver = null,
        ?int $minParentAgeGapYears = null,
        ?bool $disallowFuture = null,
        ?bool $requireParentBirthDate = null,
    ): array {
        $resolvedGapYears =
            $minParentAgeGapYears ??
            self::normalizeInt(config("birth-date.children.min_parent_age_gap_years"));
        $resolvedDisallowFuture =
            $disallowFuture ?? (bool) config("birth-date.children.disallow_future", true);
        $resolvedRequireParentBirthDate =
            $requireParentBirthDate ??
            (bool) config("birth-date.children.require_parent_birth_date", false);

        $rules = self::base($resolvedDisallowFuture, null);

        if ($resolvedGapYears === null || $resolvedGapYears < 1) {
            return $rules;
        }

        $rules[] = function (string $attribute, mixed $value, Closure $fail) use (
            $parentBirthDateResolver,
            $resolvedGapYears,
            $resolvedRequireParentBirthDate,
        ): void {
            $birthDate = self::toDate($value);
            if (!$birthDate) {
                return;
            }

            $parentBirthDateRaw = $parentBirthDateResolver ? $parentBirthDateResolver() : null;
            $parentBirthDate = self::toDate($parentBirthDateRaw);

            if (!$parentBirthDate) {
                if ($resolvedRequireParentBirthDate) {
                    $fail(
                        "The {$attribute} cannot be validated because parent birth date is missing.",
                    );
                }
                return;
            }

            $minimumChildDate = $parentBirthDate->addYears($resolvedGapYears);
            if ($birthDate->lt($minimumChildDate)) {
                $fail(
                    "The {$attribute} must be at least {$resolvedGapYears} years after parent birth date.",
                );
            }
        };

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private static function base(bool $disallowFuture, ?int $minAgeYears): array
    {
        $rules = ["nullable", "date"];
        if ($disallowFuture) {
            $rules[] = "before_or_equal:today";
        }

        if ($minAgeYears !== null && $minAgeYears > 0) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) use (
                $minAgeYears,
            ): void {
                $birthDate = self::toDate($value);
                if (!$birthDate) {
                    return;
                }

                $minimumAllowedBirthDate = CarbonImmutable::today()->subYears($minAgeYears);
                if ($birthDate->gt($minimumAllowedBirthDate)) {
                    $fail("The {$attribute} must be at least {$minAgeYears} years ago.");
                }
            };
        }

        return $rules;
    }

    private static function normalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === "") {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private static function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === "") {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return CarbonImmutable::instance($value)->startOfDay();
            }

            if (is_string($value)) {
                return CarbonImmutable::parse($value)->startOfDay();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
