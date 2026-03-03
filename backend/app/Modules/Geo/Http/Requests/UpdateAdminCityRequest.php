<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class UpdateAdminCityRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "country_id" => ["nullable", "uuid", "exists:countries,id"],
            "region_id" => ["nullable", "uuid", "exists:regions,id"],
        ];
    }
}
