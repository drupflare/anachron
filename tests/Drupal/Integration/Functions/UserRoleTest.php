<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Functions;

use Drupal\user\RoleInterface;
use Drupflare\Anachron\Drupal\Entity\Role;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;

final class UserRoleTest extends IntegrationTestCase
{
	protected static $modules = ['system', 'user'];

	protected function setUp(): void
	{
		parent::setUp();
		$this->installConfig(['user']);
		Role::create(['id' => 'alpha', 'label' => 'Gamma'])->save();
		Role::create(['id' => 'delta', 'label' => 'Beta'])->save();
	}

	public function testLoadByNameMatchesLabel(): void
	{
		$role = user_role_load_by_name('Beta');
		self::assertInstanceOf(RoleInterface::class, $role);
		self::assertSame('delta', $role->id());
	}

	public function testLoadByNameDoesNotMatchMachineId(): void
	{
		self::assertFalse(user_role_load_by_name('alpha'));
	}

	public function testLoadByNameMissingReturnsFalse(): void
	{
		self::assertFalse(user_role_load_by_name('Omega'));
	}
}
