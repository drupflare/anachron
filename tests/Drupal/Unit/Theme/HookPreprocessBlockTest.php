<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Theme;

use Drupflare\Anachron\Drupal\Theme\HookPreprocess;
use PHPUnit\Framework\TestCase;

final class HookPreprocessBlockTest extends TestCase
{
	public function testABlockWithNoIdIsLeftAlone(): void
	{
		$variables = ['elements' => ['#plugin_id' => 'system_branding_block']];
		HookPreprocess::block($variables);
		self::assertSame(['elements' => ['#plugin_id' => 'system_branding_block']], $variables);
	}
}
