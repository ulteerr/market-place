<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Validation\UserProfileRules;
use Modules\Users\Validation\PasswordRules;
use Modules\Users\Validation\EmailRules;
use Modules\Users\Validation\RolesRules;

final class UpdateAdminUserRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$userId = (string) $this->route('id');

		return array_merge(
			[
				'email'    => EmailRules::sometimesUnique($userId),
				'password' => array_merge(
					['sometimes'],
					PasswordRules::default()
				),
			],
			UserProfileRules::base(),
			RolesRules::optional()
		);
	}
}
