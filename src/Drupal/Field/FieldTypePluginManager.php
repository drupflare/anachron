<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManager as CoreFieldTypePluginManager;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupflare\Anachron\Drupal\Plugin\Field\FieldType\DecoratedFieldItem;
use Drupflare\Anachron\Drupal\Plugin\Field\FieldType\DecoratedOptionsFieldItem;
use Drupflare\Anachron\Drupal\Switches;

final class FieldTypePluginManager extends CoreFieldTypePluginManager
{
	/**
	 * @param FieldItemListInterface<FieldItemInterface> $items
	 * @param int $index
	 * @param array<int, mixed> $values
	 * @return FieldItemInterface<string|int, mixed>
	 */
	public function createFieldItem(
		FieldItemListInterface $items,
		$index,
		$values = null,
	): FieldItemInterface {
		$item = parent::createFieldItem($items, $index, $values);
		// A wrapper hides every other interface of the item, so core code that asks `instanceof`
		// (the allowed-values validator does) fails on a stock site; Drupal 7 style array access
		// is therefore something a legacy site asks for.
		if (!Switches::on(Switches::LEGACY_FIELD_ACCESS) || $item instanceof EntityReferenceItem) {
			return $item;
		}
		return $item instanceof OptionsProviderInterface
			? new DecoratedOptionsFieldItem($item)
			: new DecoratedFieldItem($item);
	}
}
