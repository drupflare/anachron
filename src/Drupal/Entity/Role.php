<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Entity;

use Drupal\user\Entity\Role as CoreRole;

class Role extends CoreRole
{
	public function __toString()
	{
		return $this->label;
	}

	public function __set($name, $value): void
	{
		match ($name) {
			'rid' => ($this->id = $value),
			'name' => ($this->label = $value),
			default => parent::__set($name, $value),
		};
	}

	public function __get($name): mixed
	{
		return match ($name) {
			'rid' => $this->id,
			'name' => $this->label,
			default => parent::__get($name),
		};
	}

	public function __isset($name): bool
	{
		return match ($name) {
			'rid', 'name' => true,
			default => (bool) parent::__isset($name),
		};
	}
}
