<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Utils;

use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\KernelTests\AssertContentTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

trait RequestTrait
{
	use AssertContentTrait;

	/**
	 * @param array<string, string|int|bool|float> $formData
	 */
	protected function doFormSubmit(
		string $uri,
		array $formData,
		string $button = '',
		bool $followRedirect = true,
	): Response {
		$page = $this->doRequest(Request::create($uri));
		self::assertSame(Response::HTTP_OK, $page->getStatusCode());

		$formData += [
			'form_build_id' => $this->inputValue('form_build_id'),
			'form_id' => $this->inputValue('form_id'),
			'op' => $button === '' ? $this->inputValue('op') : $button,
		];
		$user = $this->container->get('current_user');
		self::assertInstanceOf(AccountProxyInterface::class, $user);
		if ($user->isAuthenticated()) {
			$formData['form_token'] = $this->inputValue('form_token');
		}

		try {
			$response = $this->doRequest(Request::create($uri, 'POST', $formData));
		} catch (EnforcedResponseException $e) {
			if (!$followRedirect) {
				throw $e;
			}
			return $this->doRequest(
				Request::create((string) $e->getResponse()->headers->get('Location')),
			);
		}

		if ($followRedirect && $response->getStatusCode() === Response::HTTP_SEE_OTHER) {
			return $this->doRequest(Request::create((string) $response->headers->get('Location')));
		}
		return $response;
	}

	protected function doRequest(Request $request, bool $terminate = false): Response
	{
		$kernel = $this->container->get('http_kernel');
		self::assertInstanceOf(HttpKernelInterface::class, $kernel);
		$response = $kernel->handle($request);
		$content = $response->getContent();
		self::assertNotFalse($content);
		$this->setRawContent($content);

		if ($terminate) {
			self::assertInstanceOf(TerminableInterface::class, $kernel);
			$kernel->terminate($request, $response);
		}
		return $response;
	}

	private function inputValue(string $name): string
	{
		$input = $this->cssSelect(sprintf('input[name="%s"]', $name));
		self::assertNotCount(0, $input, sprintf('no input named %s on the page', $name));
		return (string) $input[0]->attributes()?->value[0];
	}
}
