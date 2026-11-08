<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D10;

use Drupal;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\legacy_d10_services\Form\LegacyD10Form;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ServicesTest extends LegacyTestCase
{
	protected static $modules = ['system', 'user', 'block'];

	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(MissingDependencyException::class);
		$this->container->get('module_installer')->install(['legacy_d10_services']);
	}

	public function testEnabledWithTheSwitchCreatesItsSchemaAndKeepsTheDeclaredCore(): void
	{
		$this->enableLegacy('legacy_d10_services');
		self::assertTrue(Drupal::moduleHandler()->moduleExists('legacy_d10_services'));
		self::assertTrue(Drupal::database()->schema()->tableExists('legacy_d10_services_log'));
		$info = Drupal::service('extension.list.module')->getExtensionInfo('legacy_d10_services');
		self::assertSame('^10', $info['anachron_core_requirement']);
	}

	public function testBlockPluginIsDiscoveredFromItsAnnotation(): void
	{
		$this->enableLegacy('legacy_d10_services');
		$build = Drupal::service('plugin.manager.block')
			->createInstance('legacy_d10_services_block')
			->build();
		self::assertSame('legacy block', (string) $build['#markup']);
	}

	public function testFormClassBuildsThroughTheFormBuilder(): void
	{
		$this->enableLegacy('legacy_d10_services');
		$form = Drupal::formBuilder()->getForm(LegacyD10Form::class);
		self::assertSame('legacy_d10_services_form', $form['#form_id']);
		self::assertSame('legacy form', (string) $form['note']['#markup']);
	}

	public function testHelpHookAnswersOnItsRoute(): void
	{
		$this->enableLegacy('legacy_d10_services');
		$help = Drupal::moduleHandler()->invoke('legacy_d10_services', 'help', [
			'help.page.legacy_d10_services',
			Drupal::routeMatch(),
		]);
		self::assertStringContainsString('Service and plugin code', (string) $help);
	}

	public function testYamlDecodeThroughCoreSerializationYaml(): void
	{
		$this->enableLegacy('legacy_d10_services');
		$response = $this->doRequest(Request::create('/legacy-d10/services/yaml'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame(
			['name' => 'legacy', 'count' => 3],
			json_decode((string) $response->getContent(), true),
		);
	}

	public function testPhpassHashedPasswordThroughCorePasswordService(): void
	{
		$this->gap(
			'PhpassHashedPassword: Class "Drupal\Core\Password\PhpassHashedPassword" ' .
				'not found',
		);
	}

	public function testNodeFormCreateFromNodeNamespace(): void
	{
		$this->enableLegacy('legacy_d10_services');
		$response = $this->doRequest(Request::create('/legacy-d10/services/node-form'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame(
			['form' => 'Drupal\node\Form\NodeForm'],
			json_decode((string) $response->getContent(), true),
		);
		self::assertTrue(is_a('Drupal\node\NodeForm', 'Drupal\node\Form\NodeForm', true));
	}
}
