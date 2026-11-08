<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Plugin\Field\FieldType;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * A decorated item whose inner item provides options, so `instanceof OptionsProviderInterface`
 * still holds; the allowed-values validator asks exactly that.
 */
final class DecoratedOptionsFieldItem extends DecoratedFieldItem implements OptionsProviderInterface
{
	public function getPossibleValues(?AccountInterface $account = null)
	{
		return $this->provider()->getPossibleValues($account);
	}

	public function getPossibleOptions(?AccountInterface $account = null)
	{
		return $this->provider()->getPossibleOptions($account);
	}

	public function getSettableValues(?AccountInterface $account = null)
	{
		return $this->provider()->getSettableValues($account);
	}

	public function getSettableOptions(?AccountInterface $account = null)
	{
		return $this->provider()->getSettableOptions($account);
	}

	private function provider(): OptionsProviderInterface
	{
		assert($this->inner instanceof OptionsProviderInterface);
		return $this->inner;
	}
}
