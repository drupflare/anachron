<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Functions;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;

final class CommonTest extends IntegrationTestCase
{
	protected static $modules = ['system'];

	public function testRenderDoesNotCallShow(): void
	{
		$renderer = $this->container->get('renderer');
		self::assertInstanceOf(RendererInterface::class, $renderer);

		// show() silences its deprecation with @ so phpunit never sees it
		$deprecations = [];
		set_error_handler(static function (int $errno, string $message) use (&$deprecations): bool {
			if ($errno === E_USER_DEPRECATED) {
				$deprecations[] = $message;
			}
			return true;
		});
		$element = ['#markup' => 'hello'];
		try {
			$renderer->executeInRenderContext(new RenderContext(), function () use (
				&$element,
			): void {
				self::assertSame('hello', (string) render($element));
			});
		} finally {
			restore_error_handler();
		}

		self::assertSame([], $deprecations);
	}

	public function testRenderTwiceReturnsTheSameMarkup(): void
	{
		$renderer = $this->container->get('renderer');
		self::assertInstanceOf(RendererInterface::class, $renderer);

		$element = ['#markup' => 'hello'];
		$second = '';
		$renderer->executeInRenderContext(new RenderContext(), function () use (
			&$element,
			&$second,
		): void {
			render($element);
			$second = (string) render($element);
		});

		self::assertSame('hello', $second);
	}
}
