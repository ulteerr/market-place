<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrganizationUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "user_id" => ["required", "uuid", "exists:users,id"],
            "position" => ["nullable", "string", "max:120"],
            "status" => ["nullable", "string", "in:active,invited,blocked"],
        ];
    }
}
