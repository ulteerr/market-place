<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminOrganizationRequest extends FormRequest
{
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
            "locations" => ["sometimes", "array"],
            "locations.*.country_id" => ["nullable", "uuid", "exists:countries,id"],
            "locations.*.region_id" => ["nullable", "uuid", "exists:regions,id"],
            "locations.*.city_id" => ["nullable", "uuid", "exists:cities,id"],
            "locations.*.district_id" => ["nullable", "uuid", "exists:districts,id"],
            "locations.*.address" => ["nullable", "string", "max:255"],
            "locations.*.lat" => ["nullable", "numeric", "between:-90,90"],
            "locations.*.lng" => ["nullable", "numeric", "between:-180,180"],
        ];
    }
}
