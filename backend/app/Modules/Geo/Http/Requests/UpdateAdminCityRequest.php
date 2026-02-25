<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "country_id" => ["nullable", "uuid", "exists:countries,id"],
            "region_id" => ["nullable", "uuid", "exists:regions,id"],
        ];
    }
}
