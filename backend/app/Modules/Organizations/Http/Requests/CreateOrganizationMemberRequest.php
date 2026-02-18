<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "user_id" => ["required", "uuid", "exists:users,id"],
            "role_code" => ["nullable", "string", "in:owner,admin,manager,member"],
            "status" => ["nullable", "string", "in:active,invited,blocked"],
        ];
    }
}
