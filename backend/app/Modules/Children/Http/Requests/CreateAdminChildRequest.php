<?php

declare(strict_types=1);

namespace Modules\Children\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdminChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "user_id" => ["required", "uuid", "exists:users,id"],
            "first_name" => ["required", "string", "max:255"],
            "last_name" => ["required", "string", "max:255"],
            "middle_name" => ["nullable", "string", "max:255"],
            "gender" => ["nullable", "string", "in:male,female"],
            "birth_date" => ["nullable", "date"],
        ];
    }
}
