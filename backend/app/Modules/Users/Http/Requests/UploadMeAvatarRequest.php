<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadMeAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "avatar" => ["required", "file", "image", "mimes:jpg,jpeg,png,webp", "max:5120"],
        ];
    }
}
