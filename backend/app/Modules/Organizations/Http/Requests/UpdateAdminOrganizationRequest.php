<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organizations\Http\Requests\Concerns\HasOrganizationLocationRules;

final class UpdateAdminOrganizationRequest extends FormRequest
{
    use HasOrganizationLocationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "description" => ["nullable", "string"],
            "phone" => ["nullable", "string", "max:30"],
            "email" => ["nullable", "email", "max:255"],
            "status" => ["nullable", "string", "in:draft,active,suspended,archived"],
            "source_type" => ["nullable", "string", "in:manual,import,parsed,self_registered"],
            "ownership_status" => ["nullable", "string", "in:unclaimed,pending_claim,claimed"],
            "owner_user_id" => ["nullable", "uuid", "exists:users,id"],
            ...$this->organizationLocationRules("sometimes"),
        ];
    }
}
