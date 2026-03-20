<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;
use Illuminate\Validation\Validator;
use Modules\Categories\Models\Category;
use Modules\Organizations\Models\OrganizationLocation;

abstract class ActivityCrudRequest extends CrudRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $organizationId = $this->normalizedString($this->input("organization_id"));
            $locationId = $this->normalizedString($this->input("location_id"));
            if ($organizationId !== null && $locationId !== null) {
                $location = OrganizationLocation::query()
                    ->select(["id", "organization_id"])
                    ->find($locationId);

                if ($location && (string) $location->organization_id !== $organizationId) {
                    $validator
                        ->errors()
                        ->add("location_id", "Location must belong to the selected organization.");
                }
            }

            $categoryId = $this->normalizedString($this->input("category_id"));
            if ($categoryId !== null) {
                $category = Category::query()
                    ->select(["id", "parent_id"])
                    ->find($categoryId);
                if ($category && $category->parent_id === null) {
                    $validator
                        ->errors()
                        ->add("category_id", "Activity must be assigned to a leaf category.");
                }

                if ($category && $category->parent_id !== null) {
                    $parent = Category::query()
                        ->select(["id", "parent_id"])
                        ->find((string) $category->parent_id);

                    if (!$parent || $parent->parent_id !== null) {
                        $validator
                            ->errors()
                            ->add(
                                "category_id",
                                "Activity category must be a leaf under a root category.",
                            );
                    }
                }
            }

            $this->validateSchedules($validator);
        });
    }

    protected function imageRules(bool $required = false): array
    {
        return array_values(
            array_filter([
                $required ? "required" : "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "max:10240",
            ]),
        );
    }

    protected function scheduleRules(): array
    {
        return [
            "schedules" => ["nullable", "array"],
            "schedules.*.day_of_week" => ["required_with:schedules", "integer", "between:1,7"],
            "schedules.*.start_time" => ["required_with:schedules", "date_format:H:i:s"],
            "schedules.*.end_time" => ["required_with:schedules", "date_format:H:i:s"],
        ];
    }

    private function validateSchedules(Validator $validator): void
    {
        $schedules = $this->input("schedules");
        if (!is_array($schedules)) {
            return;
        }

        $seen = [];
        foreach ($schedules as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $dayOfWeek = isset($row["day_of_week"]) ? (int) $row["day_of_week"] : 0;
            $startTime = $this->normalizedString($row["start_time"] ?? null);
            $endTime = $this->normalizedString($row["end_time"] ?? null);

            if ($startTime === null || $endTime === null) {
                continue;
            }

            if ($startTime >= $endTime) {
                $validator
                    ->errors()
                    ->add(
                        "schedules.$index.end_time",
                        "Schedule end_time must be later than start_time.",
                    );
            }

            $signature = implode("|", [$dayOfWeek, $startTime, $endTime]);
            if (isset($seen[$signature])) {
                $validator
                    ->errors()
                    ->add(
                        "schedules.$index.day_of_week",
                        "Duplicate schedule slot is not allowed.",
                    );
            }

            $seen[$signature] = true;
        }
    }

    private function normalizedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }
}
