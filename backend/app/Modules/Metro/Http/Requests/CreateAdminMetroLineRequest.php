<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class CreateAdminMetroLineRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "external_id" => ["nullable", "string", "max:255"],
            "line_id" => ["nullable", "string", "max:255"],
            "color" => ["nullable", "string", "max:32"],
            "city_id" => ["required", "uuid", "exists:cities,id"],
            "source" => ["required", "string", "max:255"],
        ];
    }
}
