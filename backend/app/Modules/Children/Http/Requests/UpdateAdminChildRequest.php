<?php

declare(strict_types=1);

namespace Modules\Children\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;
use App\Shared\Validation\BirthDateRules;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class UpdateAdminChildRequest extends CrudRequest
{
    protected function ruleset(): array
    {
        return [
            "user_id" => ["sometimes", "uuid", "exists:users,id"],
            "first_name" => ["sometimes", "string", "max:255"],
            "last_name" => ["sometimes", "string", "max:255"],
            "middle_name" => ["nullable", "string", "max:255"],
            "gender" => ["nullable", "string", "in:male,female"],
            "birth_date" => BirthDateRules::forChildren(function () {
                $userId = trim((string) $this->input("user_id", ""));
                if ($userId === "") {
                    $childId = trim((string) $this->route("id", ""));
                    if ($childId !== "") {
                        $userId =
                            (string) (Child::query()->whereKey($childId)->value("user_id") ?? "");
                    }
                }

                if ($userId === "") {
                    return null;
                }

                return User::query()->whereKey($userId)->value("birth_date");
            }),
        ];
    }
}
