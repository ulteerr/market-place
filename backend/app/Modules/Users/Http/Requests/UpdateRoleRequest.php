<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class UpdateRoleRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        $id = (string) $this->route("id");

        return [
            "code" => ["sometimes", "string", "max:50", "unique:roles,code," . $id],
            "label" => ["nullable", "string", "max:255"],
            "permissions" => ["sometimes", "array"],
            "permissions.*" => ["string", "distinct", "exists:access_permissions,code"],
        ];
    }
}
