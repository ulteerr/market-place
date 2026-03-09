<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrganizationUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "position" => ["sometimes", "nullable", "string", "max:120"],
            "status" => ["sometimes", "string", "in:active,invited,blocked"],
        ];
    }
}
