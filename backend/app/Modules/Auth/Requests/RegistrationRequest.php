<?php
declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'middle_name'=> ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'], // password_confirmation
            'phone'      => ['nullable', 'string', 'max:20'],
        ];
    }
}
