<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Functions;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;

final class NodeTest extends IntegrationTestCase
{
	protected static $modules = ['system', 'user', 'field', 'text', 'filter', 'node'];

	protected function setUp(): void
	{
		parent::setUp();
		$this->installEntitySchema('user');
		$this->installEntitySchema('node');
		$this->installConfig(['node']);
		NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
	}

	public function testNodeLoadWithoutArgumentsReturnsFalse(): void
	{
		Node::create(['type' => 'page', 'title' => 'First'])->save();
		Node::create(['type' => 'page', 'title' => 'Second'])->save();

		self::assertFalse(node_load());
	}

	public function testNodeLoadByNid(): void
	{
		$node = Node::create(['type' => 'page', 'title' => 'First']);
		$node->save();

		$loaded = node_load($node->id());
		self::assertInstanceOf(Node::class, $loaded);
		self::assertSame($node->id(), $loaded->id());
	}
}
