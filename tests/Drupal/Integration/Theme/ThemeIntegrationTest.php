<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupflare\Anachron\Drupal\Theme\HookPreprocess;
use Drupflare\Anachron\Tests\Drupal\Utils\RequestTrait;
use Drupflare\Anachron\Tests\Drupal\Utils\TestHttpKernelTrait;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;
use Drupflare\Anachron\Tests\Drupal\Utils\TestThemeExtensionList;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\HttpFoundation\Request;

final class ThemeIntegrationTest extends IntegrationTestCase
{
	use RequestTrait;
	use TestHttpKernelTrait;

	protected static $modules = ['system', 'block', 'test_page_test'];

	protected function setUp(): void
	{
		$this->strictConfigSchema = false;
		parent::setUp();
	}

	public function register(ContainerBuilder $container): void
	{
		parent::register($container);
		$container->setDefinition(
			TestThemeExtensionList::class,
			(new ChildDefinition('extension.list.theme'))->setDecoratedService(
				'extension.list.theme',
			),
		);
		$this->registerTestHttpKernel($container);
		/** @var array{debug: bool} $twig */
		$twig = $container->getParameter('twig.config');
		$twig['debug'] = true;
		$container->setParameter('twig.config', $twig);
	}

	public function testListInfo(): void
	{
		$themeHandler = $this->container->get('extension.list.theme');
		assert($themeHandler instanceof ThemeExtensionList);
		self::assertArrayHasKey('bartik', $themeHandler->getList());
	}

	public function testPageDoesNotCrashWithTheme(): void
	{
		$this->config('core.extension')->set('theme.bartik', 0)->save(true);
		$this->config('system.theme')->set('default', 'bartik')->save(true);
		$this->rebuildContainerForTheme();

		$this->placeBlock('page_title_block', 'header');

		try {
			$this->doRequest(Request::create('/test-page'));
			self::assertStringContainsString('Test page text.', $this->getTextContent());
			self::assertStringContainsString(
				'<div id="page-wrapper"><div id="page">',
				$this->getRawContent(),
			);
			self::assertStringContainsString(
				'<h1class="title"id="page-title">Testpage</h1>',
				preg_replace('/\s/', '', $this->getRawContent()),
			);
		} catch (\Exception $e) {
			$this->fail($e->getMessage());
		}
	}

	public function testPagePreprocessSetsLogo(): void
	{
		$this->config('core.extension')->set('theme.bartik', 0)->save(true);
		$this->config('system.theme')->set('default', 'bartik')->save(true);
		$this->config('system.theme.global')->set('logo.use_default', true)->save(true);
		$themeManager = $this->container->get('theme.manager');
		self::assertInstanceOf(ThemeManagerInterface::class, $themeManager);
		$bartik = $this->container->get('theme.initialization')->getActiveThemeByName('bartik');
		$themeManager->setActiveTheme($bartik);

		// theme_get_setting() silences its deprecation with @ so phpunit never sees it
		$deprecations = [];
		set_error_handler(static function (int $errno, string $message) use (&$deprecations): bool {
			if ($errno === E_USER_DEPRECATED) {
				$deprecations[] = $message;
			}
			return true;
		});
		$variables = ['page' => []];
		try {
			HookPreprocess::page($variables);
		} finally {
			restore_error_handler();
		}

		self::assertSame([], $deprecations);
		self::assertStringEndsWith('/bartik/logo.svg', $variables['logo']);
	}

	/**
	 * The theme negotiator checks the container.themes parameter, which is fixed when the
	 * container builds, so a theme enabled through config afterwards is refused.
	 */
	private function rebuildContainerForTheme(): void
	{
		$kernel = $this->container->get('kernel');
		assert($kernel instanceof DrupalKernelInterface);
		$themes = $this->container->get('extension.list.theme');
		assert($themes instanceof ThemeExtensionList);
		$kernel->updateThemes(['bartik' => $themes->get('bartik')]);
		$this->container = $kernel->getContainer();
	}

	private function placeBlock(string $plugin_id, string $region): void
	{
		$entity_type_manager = $this->container->get('entity_type.manager');
		self::assertInstanceOf(EntityTypeManagerInterface::class, $entity_type_manager);
		$block_storage = $entity_type_manager->getStorage('block');
		$block_storage
			->create([
				'id' => "test_$plugin_id",
				'theme' => 'bartik',
				'region' => $region,
				'plugin' => $plugin_id,
				'settings' => [
					'id' => $plugin_id,
					'label' => $this->randomMachineName(),
				],
				'visibility' => [],
			])
			->save();
	}
}
