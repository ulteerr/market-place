<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Activities\Models\Lead;

final class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "status" => [
                "required",
                "string",
                Rule::in([
                    Lead::STATUS_NEW,
                    Lead::STATUS_IN_PROGRESS,
                    Lead::STATUS_CONTACTED,
                    Lead::STATUS_REGISTERED,
                    Lead::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}
