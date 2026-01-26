<?php

declare(strict_types=1);

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\Role;

class RolesSeeder extends Seeder
{
	public function run(): void
	{
		$this->createRoleIfNotExists('participant', 'Участник', true);
		$this->createRoleIfNotExists('admin', 'Администратор', true);
		$this->createRoleIfNotExists('moderator', 'Модератор', false);
	}

	private function createRoleIfNotExists(string $code, string $label, bool $isSystem = false): void
	{
		Role::firstOrCreate(
			['code' => $code],
			[
				'label' => $label,
				'is_system' => $isSystem,
			]
		);
	}
}
