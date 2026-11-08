<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Functions;

use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupflare\Anachron\Drupal\Entity\WrappedContentEntity;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;

final class TaxonomyTest extends IntegrationTestCase
{
	protected static $modules = ['system', 'user', 'field', 'text', 'filter', 'node', 'taxonomy'];

	private int $rootA;
	private int $child;
	private int $grandchild;

	protected function setUp(): void
	{
		parent::setUp();
		$this->installEntitySchema('user');
		$this->installEntitySchema('node');
		$this->installEntitySchema('taxonomy_term');
		$this->installConfig(['node', 'taxonomy']);
		NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

		Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
		$rootA = Term::create(['vid' => 'tags', 'name' => 'Root A', 'parent' => [0]]);
		$rootA->save();
		$rootB = Term::create(['vid' => 'tags', 'name' => 'Root B', 'parent' => [0]]);
		$rootB->save();
		$child = Term::create(['vid' => 'tags', 'name' => 'Child', 'parent' => [$rootA->id()]]);
		$child->save();
		$grandchild = Term::create([
			'vid' => 'tags',
			'name' => 'Grandchild',
			'parent' => [$child->id()],
		]);
		$grandchild->save();

		$this->rootA = (int) $rootA->id();
		$this->child = (int) $child->id();
		$this->grandchild = (int) $grandchild->id();
	}

	public function testTreeWithDefaults(): void
	{
		$tree = taxonomy_get_tree('tags');

		self::assertSame(
			[['Root A', 0], ['Child', 1], ['Grandchild', 2], ['Root B', 0]],
			array_map(static fn(object $term): array => [$term->name, $term->depth], $tree),
		);
	}

	public function testTreeFromParent(): void
	{
		$tree = taxonomy_get_tree('tags', $this->rootA);

		self::assertSame(
			[['Child', 0, $this->child], ['Grandchild', 1, $this->grandchild]],
			array_map(
				static fn(object $term): array => [$term->name, $term->depth, (int) $term->tid],
				$tree,
			),
		);
	}

	public function testTreeMaxDepth(): void
	{
		$tree = taxonomy_get_tree('tags', 0, 1);

		self::assertSame(
			['Root A', 'Root B'],
			array_map(static fn(object $term): string => $term->name, $tree),
		);
	}

	public function testTreeLoadEntities(): void
	{
		$tree = taxonomy_get_tree('tags', $this->rootA, null, true);

		self::assertCount(2, $tree);
		self::assertContainsOnlyInstancesOf(WrappedContentEntity::class, $tree);
	}
}
