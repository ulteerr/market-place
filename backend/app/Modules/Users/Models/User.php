<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Shared\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Modules\Children\Models\Child;

final class User extends Authenticatable
{
	use Notifiable, HasUuid, HasApiTokens;

	protected $keyType = 'string';
	public $incrementing = false;

	protected $fillable = [
		'first_name',
		'last_name',
		'middle_name',
		'email',
		'password',
		'phone',
	];

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected $casts = [
		'email_verified_at' => 'datetime',
	];

	public function children()
	{
		return $this->hasMany(Child::class, 'user_id');
	}
}
