<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "country_id" => ["sometimes", "uuid", "exists:countries,id"],
        ];
    }
}
