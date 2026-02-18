<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "role_code" => ["sometimes", "string", "in:owner,admin,manager,member"],
            "status" => ["sometimes", "string", "in:active,invited,blocked"],
        ];
    }
}
