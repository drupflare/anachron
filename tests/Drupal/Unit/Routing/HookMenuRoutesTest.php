<?php

namespace Drupflare\Anachron\Tests\Drupal\Unit\Routing;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupflare\Anachron\Drupal\Controller\PageCallbackController;
use Drupflare\Anachron\Drupal\Routing\HookMenuRegistry;
use Drupflare\Anachron\Drupal\Routing\HookMenuRoutes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

class HookMenuRoutesTest extends TestCase
{
	public function testMenuExample(): void
	{
		$moduleName = 'menu_example';
		require_once __DIR__ . "/../../../fixtures/retrofit/$moduleName/$moduleName.module";
		$moduleHandler = $this->createMock(ModuleHandlerInterface::class);
		$moduleHandler
			->method('invokeAllWith')
			->willReturnCallback(static function (string $hook, callable $callback) use (
				$moduleName,
			): void {
				if ($hook === 'menu') {
					$callback($moduleName . '_menu', $moduleName);
				}
			});
		$hookMenuRegistry = new HookMenuRegistry($moduleHandler, new NullBackend('foo'));
		$sut = new HookMenuRoutes($moduleHandler, $hookMenuRegistry);
		$collection = new RouteCollection();
		$event = new RouteBuildEvent($collection);
		$sut->onAlterRoutes($event);
		self::assertCount(20, $collection);
		$routes = $collection->all();
		$route = array_shift($routes);
		self::assertNotFalse($route);
		self::assertEquals(
			[
				'_title' => 'Menu Example',
				'_controller' => PageCallbackController::class . '::getPage',
				'_menu_callback' => '_menu_example_basic_instructions',
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'_custom_page_arguments' => [
					new TranslatableMarkup(
						'This page is displayed by the simplest (and base) menu example. Note that the title of the page is the same as the link title. You can also <a href=":link">visit a similar page with no menu link</a>. Also, note that there is a hook_menu_alter() example that has changed the path of one of the menu items.',
						[
							':link' => '/examples/menu_example/path_only',
						],
					),
				],
			],
			$route->getDefaults(),
		);
		self::assertEquals(['_access' => 'TRUE'], $route->getRequirements());
	}
}
