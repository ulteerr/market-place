<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class UpdateAdminMetroLineRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "external_id" => ["sometimes", "nullable", "string", "max:255"],
            "line_id" => ["sometimes", "nullable", "string", "max:255"],
            "color" => ["sometimes", "nullable", "string", "max:32"],
            "city_id" => ["sometimes", "uuid", "exists:cities,id"],
            "source" => ["sometimes", "string", "max:255"],
        ];
    }
}
