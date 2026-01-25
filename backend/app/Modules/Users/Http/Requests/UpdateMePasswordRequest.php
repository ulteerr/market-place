<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\PasswordRules;
use Modules\Users\Validation\UserProfileRules;

final class UpdateMePasswordRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return ['password' => array_merge(
			['required'],
			PasswordRules::default()
		)];
	}
}
