<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdminRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "country_id" => ["required", "uuid", "exists:countries,id"],
        ];
    }
}
