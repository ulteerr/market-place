<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class CreateAdminDistrictRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "city_id" => ["required", "uuid", "exists:cities,id"],
        ];
    }
}
