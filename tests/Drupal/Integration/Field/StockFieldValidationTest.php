<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Field;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupflare\Anachron\Drupal\Plugin\Field\FieldType\DecoratedFieldItem;
use Drupflare\Anachron\Drupal\Plugin\Field\FieldType\DecoratedOptionsFieldItem;
use Drupflare\Anachron\Drupal\Switches;

final class StockFieldValidationTest extends KernelTestBase
{
	protected static $modules = ['user', 'system', 'field', 'entity_test'];

	protected function tearDown(): void
	{
		unset($GLOBALS['conf'][Switches::LEGACY_FIELD_ACCESS]);
		parent::tearDown();
	}

	public function testAStockSiteKeepsItsFieldItemsUnwrapped(): void
	{
		$this->installEntitySchema('user');
		$this->installEntitySchema('entity_test');
		$entity = EntityTest::create(['name' => 'stock']);
		self::assertNotInstanceOf(DecoratedFieldItem::class, $entity->get('name')->first());
		self::assertSame([], $this->fieldViolations($entity));
	}

	public function testTheSwitchWrapsItemsAndTheAllowedValuesValidatorStillSeesOptions(): void
	{
		$GLOBALS['conf'][Switches::LEGACY_FIELD_ACCESS] = true;
		$this->installEntitySchema('user');
		$this->installEntitySchema('entity_test');
		$manager = $this->container->get('plugin.manager.field.field_type');
		self::assertInstanceOf(FieldTypePluginManagerInterface::class, $manager);

		$entity = EntityTest::create(['name' => 'legacy']);
		self::assertInstanceOf(DecoratedFieldItem::class, $entity->get('name')->first());
		self::assertSame([], $this->fieldViolations($entity));

		$options = $entity->get('langcode')->first();
		self::assertInstanceOf(DecoratedOptionsFieldItem::class, $options);
	}

	/**
	 * Validation raises a LogicException when an item hides OptionsProviderInterface; the
	 * user_id reference to a user the test never made is a violation of its own and not what this is about.
	 *
	 * @return string[]
	 */
	private function fieldViolations(EntityTest $entity): array
	{
		$paths = [];
		foreach ($entity->validate() as $violation) {
			$path = $violation->getPropertyPath();
			if (str_starts_with($path, 'name') || str_starts_with($path, 'langcode')) {
				$paths[] = $path;
			}
		}
		return $paths;
	}
}
