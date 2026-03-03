<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class UpdateAdminRegionRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "country_id" => ["sometimes", "uuid", "exists:countries,id"],
        ];
    }
}
