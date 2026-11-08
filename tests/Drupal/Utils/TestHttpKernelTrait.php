<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Utils;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

trait TestHttpKernelTrait
{
	protected function registerTestHttpKernel(ContainerBuilder $container): void
	{
		$container
			->register('http_kernel.test', UncaughtHttpKernel::class)
			->setDecoratedService('http_kernel.basic')
			->addArgument(new Reference('http_kernel.test.inner'));
	}
}
