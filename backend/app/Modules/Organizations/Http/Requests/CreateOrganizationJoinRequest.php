<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Closure;
use Illuminate\Validation\Rule;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Users\Models\User;

final class CreateOrganizationJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "subject_type" => [
                "required",
                "string",
                Rule::in([
                    OrganizationJoinRequest::SUBJECT_TYPE_USER,
                    OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                ]),
            ],
            "subject_id" => [
                "required",
                "uuid",
                function (string $attribute, mixed $value, Closure $fail): void {
                    $subjectId = trim((string) $value);
                    $subjectType = trim((string) $this->input("subject_type"));

                    if ($subjectType === OrganizationJoinRequest::SUBJECT_TYPE_USER) {
                        if (!User::query()->whereKey($subjectId)->exists()) {
                            $fail("Selected user does not exist.");
                        }

                        return;
                    }

                    if ($subjectType === OrganizationJoinRequest::SUBJECT_TYPE_CHILD) {
                        if (!Child::query()->whereKey($subjectId)->exists()) {
                            $fail("Selected child does not exist.");
                        }
                    }
                },
            ],
            "message" => ["nullable", "string", "max:2000"],
        ];
    }
}
