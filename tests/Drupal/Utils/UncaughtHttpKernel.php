<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final class UncaughtHttpKernel implements HttpKernelInterface, TerminableInterface
{
	public function __construct(private readonly HttpKernelInterface $inner) {}

	public function handle(
		Request $request,
		int $type = self::MAIN_REQUEST,
		bool $catch = true,
	): Response {
		return $this->inner->handle($request, $type, false);
	}

	public function terminate(Request $request, Response $response): void
	{
		if ($this->inner instanceof TerminableInterface) {
			$this->inner->terminate($request, $response);
		}
	}
}
