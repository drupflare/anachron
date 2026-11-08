<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ContainerAwareShimTest extends TestCase
{
	public function testTheTraitHoldsTheContainerForAClassThatUsesIt(): void
	{
		$object = new class implements ContainerAwareInterface {
			use ContainerAwareTrait;

			public function container(): ?ContainerInterface
			{
				return $this->container;
			}
		};
		$container = new ContainerBuilder();
		self::assertNull($object->container());
		$object->setContainer($container);
		self::assertSame($container, $object->container());
		$object->setContainer(null);
		self::assertNull($object->container());
	}

	public function testAClassCanDeclareItsOwnNullableSetter(): void
	{
		$object = new class implements ContainerAwareInterface {
			public ?ContainerInterface $held = null;

			public function setContainer(?ContainerInterface $container = null): void
			{
				$this->held = $container;
			}
		};
		$container = new ContainerBuilder();
		$object->setContainer($container);
		self::assertSame($container, $object->held);
	}
}
