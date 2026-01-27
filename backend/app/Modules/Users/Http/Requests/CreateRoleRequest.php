<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;

final class CreateRoleRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            'code'  => ['required', 'string', 'max:50', 'unique:roles,code'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
