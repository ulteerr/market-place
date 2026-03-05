<?php

declare(strict_types=1);

namespace Modules\Children\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;
use App\Shared\Validation\BirthDateRules;
use Modules\Users\Models\User;

final class CreateAdminChildRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "user_id" => ["required", "uuid", "exists:users,id"],
            "first_name" => ["required", "string", "max:255"],
            "last_name" => ["required", "string", "max:255"],
            "middle_name" => ["nullable", "string", "max:255"],
            "gender" => ["nullable", "string", "in:male,female"],
            "birth_date" => BirthDateRules::forChildren(function () {
                $userId = trim((string) $this->input("user_id", ""));
                if ($userId === "") {
                    return null;
                }

                return User::query()->whereKey($userId)->value("birth_date");
            }),
        ];
    }
}
