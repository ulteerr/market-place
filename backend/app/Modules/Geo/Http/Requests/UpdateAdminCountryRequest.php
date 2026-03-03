<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class UpdateAdminCountryRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "iso_code" => ["nullable", "string", "size:3"],
        ];
    }
}
