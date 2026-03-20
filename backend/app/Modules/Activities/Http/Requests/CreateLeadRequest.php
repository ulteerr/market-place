<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Activities\Models\Lead;

final class CreateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "request_for_type" => [
                "required",
                "string",
                Rule::in([Lead::REQUEST_FOR_SELF, Lead::REQUEST_FOR_CHILD]),
            ],
            "child_id" => ["nullable", "uuid", "exists:children,id"],
            "contact_channels" => ["required", "array", "min:1"],
            "contact_channels.*" => [
                "string",
                Rule::in(["any", "chat", "phone", "telegram", "whatsapp", "max"]),
            ],
            "contact_payload" => ["nullable", "array"],
            "contact_payload.phone" => ["nullable", "string", "max:64"],
            "contact_payload.telegram" => ["nullable", "string", "max:255"],
            "contact_payload.whatsapp" => ["nullable", "string", "max:255"],
            "contact_payload.max" => ["nullable", "string", "max:255"],
            "message" => ["nullable", "string", "max:2000"],
        ];
    }
}
