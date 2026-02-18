<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdminOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "address" => ["nullable", "string", "max:255"],
            "phone" => ["nullable", "string", "max:30"],
            "email" => ["nullable", "email", "max:255"],
            "status" => ["nullable", "string", "in:draft,active,suspended,archived"],
            "source_type" => ["nullable", "string", "in:manual,import,parsed,self_registered"],
            "ownership_status" => ["nullable", "string", "in:unclaimed,pending_claim,claimed"],
            "owner_user_id" => ["nullable", "uuid", "exists:users,id"],
        ];
    }
}
