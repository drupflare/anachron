<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Template\Loader\FilesystemLoader;
use Drupflare\Anachron\Drupal\Asset\RetrofitJsCollectionRenderer;
use Drupflare\Anachron\Drupal\Asset\RetrofitLibraryDiscovery;
use Drupflare\Anachron\Drupal\Controller\RetrofitTitleResolver;
use Drupflare\Anachron\Drupal\DependencyInjection\Compiler\FilesAutoloaderPass;
use Drupflare\Anachron\Drupal\Entity\EntityTypeManager;
use Drupflare\Anachron\Drupal\EventSubscriber\HookExit;
use Drupflare\Anachron\Drupal\EventSubscriber\HookInit;
use Drupflare\Anachron\Drupal\Extension\LegacyInfoParser;
use Drupflare\Anachron\Drupal\Field\FieldTypePluginManager;
use Drupflare\Anachron\Drupal\Form\FormBuilder;
use Drupflare\Anachron\Drupal\Language\GlobalLanguageSetter;
use Drupflare\Anachron\Drupal\Menu\LocalActionManager;
use Drupflare\Anachron\Drupal\Menu\LocalTaskManager;
use Drupflare\Anachron\Drupal\Menu\MenuLinkManager;
use Drupflare\Anachron\Drupal\ParamConverter\PageArgumentsConverter;
use Drupflare\Anachron\Drupal\Path\CurrentPathStack;
use Drupflare\Anachron\Drupal\Render\AttachmentResponseSubscriber;
use Drupflare\Anachron\Drupal\Render\RetrofitHtmlResponseAttachmentsProcessor;
use Drupflare\Anachron\Drupal\Routing\HookAdminPaths;
use Drupflare\Anachron\Drupal\Routing\HookMenuRegistry;
use Drupflare\Anachron\Drupal\Routing\HookMenuRoutes;
use Drupflare\Anachron\Drupal\Template\RetrofitExtension;
use Drupflare\Anachron\Drupal\Theme\Registry;
use Drupflare\Anachron\Drupal\User\GlobalUserSetter;
use Drupflare\Anachron\Drupal\User\HookPermissions;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;

class Provider extends ServiceProviderBase
{
	public function register(ContainerBuilder $container)
	{
		$namespaces = $container->getParameter('container.namespaces');
		$namespaces['Drupflare\Anachron\Drupal'] = __DIR__;
		$container->setParameter('container.namespaces', $namespaces);

		$container
			->register(HookMenuRegistry::class)
			->addArgument(new Reference('module_handler'))
			->addArgument(new Reference('cache.data'));

		$container
			->register(HookMenuRoutes::class)
			->addArgument(new Reference('module_handler'))
			->setAutowired(true)
			->addTag('event_subscriber');

		$container
			->register(GlobalUserSetter::class)
			->addTag('event_subscriber')
			->addTag('drupflare.reset');

		$container
			->register(GlobalLanguageSetter::class)
			->addArgument(new Reference('language_manager'))
			->addArgument(new Reference('module_handler'))
			->addTag('event_subscriber')
			->addTag('drupflare.reset');

		$container->register(PageArgumentsConverter::class)->addTag('paramconverter');

		$container->setDefinition(
			LegacyInfoParser::class,
			(new ChildDefinition('info_parser'))
				->setClass(LegacyInfoParser::class)
				->setDecoratedService('info_parser'),
		);

		$container->setDefinition(
			MenuLinkManager::class,
			(new ChildDefinition('plugin.manager.menu.link'))->setDecoratedService(
				'plugin.manager.menu.link',
			),
		);

		$container->setDefinition(
			LocalActionManager::class,
			(new ChildDefinition('plugin.manager.menu.local_action'))->setDecoratedService(
				'plugin.manager.menu.local_action',
			),
		);

		$container->setDefinition(
			LocalTaskManager::class,
			(new ChildDefinition('plugin.manager.menu.local_task'))->setDecoratedService(
				'plugin.manager.menu.local_task',
			),
		);

		$container->setDefinition(
			Registry::class,
			(new ChildDefinition('theme.registry'))->setDecoratedService('theme.registry'),
		);

		$container->setDefinition(
			FilesystemLoader::class,
			(new ChildDefinition('twig.loader.filesystem'))
				->setDecoratedService('twig.loader.filesystem')
				->addMethodCall('addPath', [__DIR__ . '/../templates', 'retrofit']),
		);

		$container
			->register(RetrofitExtension::class)
			->addArgument(new Reference('theme.registry'))
			->addTag('twig.extension');

		if ($container->has('user.permissions')) {
			$container
				->register(HookPermissions::class)
				->setDecoratedService('user.permissions')
				->addArgument(new Reference(HookPermissions::class . '.inner'))
				->addArgument(new Reference('module_handler'));
		}

		$container
			->register(RetrofitHtmlResponseAttachmentsProcessor::class)
			->setDecoratedService('html_response.attachments_processor')
			->addArgument(new Reference(RetrofitHtmlResponseAttachmentsProcessor::class . '.inner'))
			->addArgument(new Reference('asset.js.collection_renderer'))
			->setAutowired(true);

		$container->register(AttachmentResponseSubscriber::class)->addTag('event_subscriber');

		$container
			->register(RetrofitLibraryDiscovery::class)
			->setDecoratedService('library.discovery')
			->setAutowired(true);

		$container
			->register(RetrofitJsCollectionRenderer::class)
			->setDecoratedService('asset.js.collection_renderer')
			->setAutowired(true);

		$container->setDefinition(
			FieldTypePluginManager::class,
			(new ChildDefinition('plugin.manager.field.field_type'))->setDecoratedService(
				'plugin.manager.field.field_type',
			),
		);

		$container
			->register(RetrofitTitleResolver::class)
			->setDecoratedService('title_resolver')
			->addArgument(new Reference(RetrofitTitleResolver::class . '.inner'))
			->addArgument(new Reference('request_stack'));

		$container->setDefinition(
			FormBuilder::class,
			(new ChildDefinition('form_builder'))->setDecoratedService('form_builder'),
		);

		$container->setDefinition(
			CurrentPathStack::class,
			(new ChildDefinition('path.current'))->setDecoratedService('path.current'),
		);

		$container->setDefinition(
			EntityTypeManager::class,
			(new ChildDefinition('entity_type.manager'))->setDecoratedService(
				'entity_type.manager',
			),
		);

		$container
			->register(HookExit::class)
			->addArgument(new Reference('module_handler'))
			->setAutowired(true)
			->addTag('event_subscriber');

		$container
			->register(HookInit::class)
			->addArgument(new Reference('module_handler'))
			->setAutowired(true)
			->addTag('event_subscriber');

		$container
			->register(HookAdminPaths::class)
			->addArgument(new Reference('state'))
			->setAutowired(true)
			->addTag('event_subscriber');

		$container->addCompilerPass(new FilesAutoloaderPass());
	}

	public function alter(ContainerBuilder $container) {}
}
