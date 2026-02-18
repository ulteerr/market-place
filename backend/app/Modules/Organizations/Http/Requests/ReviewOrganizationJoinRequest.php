<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewOrganizationJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "role_code" => ["sometimes", "string", "in:owner,admin,manager,member"],
            "review_note" => ["nullable", "string", "max:2000"],
        ];
    }
}
