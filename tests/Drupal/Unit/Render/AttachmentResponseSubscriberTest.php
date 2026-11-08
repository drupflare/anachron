<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Render;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\HtmlResponse;
use PHPUnit\Framework\TestCase;
use Drupflare\Anachron\Drupal\Render\AttachmentResponseSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AttachmentResponseSubscriberTest extends TestCase
{
	public function testAddAttachments(): void
	{
		$sut = new AttachmentResponseSubscriber();
		$container = new ContainerBuilder();
		$container->set(AttachmentResponseSubscriber::class, $sut);
		\Drupal::setContainer($container);

		drupal_add_library('system', 'jquery');
		drupal_add_js(['hello' => 'World'], ['type' => 'setting']);
		drupal_add_css('https://example.com/cdn.js');
		drupal_add_css('.foo { color: pink }', ['type' => 'inline']);

		$response = new HtmlResponse();
		$event = new ResponseEvent(
			$this->createMock(HttpKernelInterface::class),
			new Request(),
			HttpKernelInterface::MAIN_REQUEST,
			$response,
		);
		$sut->onResponse($event);

		self::assertEquals(
			[
				'library' => ['core/jquery'],
				'drupalSettings' => ['hello' => 'World'],
				'css' => [
					'https://example.com/cdn.js' => [
						'type' => 'file',
						'group' => 0,
						'weight' => 0,
						'every_page' => false,
						'media' => 'all',
						'preprocess' => true,
						'data' => 'https://example.com/cdn.js',
						'browsers' => [],
					],
					[
						'type' => 'inline',
						'group' => 0,
						'weight' => 0,
						'every_page' => false,
						'media' => 'all',
						'preprocess' => true,
						'data' => '.foo { color: pink }',
						'browsers' => [],
					],
				],
			],
			$response->getAttachments(),
		);
	}
}
