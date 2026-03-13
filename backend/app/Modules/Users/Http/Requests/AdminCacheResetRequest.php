<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminCacheResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "scopes" => ["required", "array", "min:1"],
            "scopes.*" => ["string", "distinct", "in:frontend-ssr,backend"],
        ];
    }
}
