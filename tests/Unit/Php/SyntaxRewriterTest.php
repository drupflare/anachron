<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Unit\Php;

use Drupflare\Anachron\Php\SyntaxRewriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SyntaxRewriterTest extends TestCase
{
	/**
	 * Curly offsets with the expected square-bracket form.
	 *
	 * @return array<string, array{string, string}>
	 *   Source and expected output, keyed by case name.
	 */
	public static function positiveCases(): array
	{
		return [
			'variable offset' => ['<?php $str{0};', '<?php $str[0];'],
			'chained offsets' => ['<?php $a{0}{1};', '<?php $a[0][1];'],
			'offset after index' => ['<?php $a[1]{0};', '<?php $a[1][0];'],
			'property offset' => ['<?php $this->name{0};', '<?php $this->name[0];'],
			'nullsafe property offset' => ['<?php $a?->name{0};', '<?php $a?->name[0];'],
			'nested offset' => ['<?php $a{$b{0}};', '<?php $a[$b[0]];'],
			'offset with string interpolation' => ['<?php $a{"{$b}"};', '<?php $a["{$b}"];'],
			'offset with dollar brace interpolation' => ['<?php $a{"${b}"};', '<?php $a["${b}"];'],
		];
	}

	/**
	 * Source that must come back byte-identical.
	 *
	 * @return array<string, array{string}>
	 *   Source keyed by case name.
	 */
	public static function negativeCases(): array
	{
		return [
			'interpolation in double quotes' => ['<?php $x = "{$a[0]} ${b} $c{0}";'],
			'heredoc' => ["<?php\n\$x = <<<EOT\n{\$a['x']} \$b{0}\nEOT;\n"],
			'single quoted string' => ["<?php \$s = '\$a{0}';"],
			'line comment' => ["<?php // \$a{0}\n\$c = 1;"],
			'block comment' => ['<?php /* $b{1} */ $c = 1;'],
			'if block' => ['<?php if ($a) { $b = 1; }'],
			'else and try blocks' => ['<?php if ($a) { } else { } try { } catch (E $e) { }'],
			'class with methods' => [
				"<?php\nclass C {\n\tpublic function f(\$a) {\n\t\treturn \$a;\n\t}\n}\n",
			],
			'closure' => ['<?php $f = function ($x) use ($y) { return $x + $y; };'],
			'switch and match' => [
				"<?php switch (\$a) { case 1: break; }\n\$r = match (\$a) { 1 => 'x' };",
			],
			'declare block' => ['<?php declare(ticks=1) { $a = 1; }'],
			'trait use block' => ['<?php class C { use T { f as g; } }'],
			'class extends and implements' => ['<?php class C extends B implements I { }'],
			'brace after whitespace' => ['<?php $str {0};'],
			'bare block after a block' => ['<?php if ($a) { } { $b = 2; }'],
			'empty offset' => ['<?php $a{};'],
			'property hook with get block' => [
				'<?php class C { public $name{ get { return 1; } } }',
			],
			'property hook with set parameter' => [
				'<?php class C { public $name{ set(string $v) { $this->n = $v; } } }',
			],
			'property hook with arrow' => ["<?php class C { public \$name{ get => 'x'; } }"],
		];
	}

	#[DataProvider('positiveCases')]
	public function testRewritesCurlyOffsetsToSquareBrackets(string $source, string $expected): void
	{
		$this->assertSame($expected, (new SyntaxRewriter())->rewrite($source));
	}

	#[DataProvider('positiveCases')]
	public function testSourceWithCurlyOffsetFailsPhpLint(string $source, string $expected): void
	{
		$this->assertNotSame(0, $this->lint($source));
	}

	#[DataProvider('positiveCases')]
	public function testRewrittenOutputPassesPhpLint(string $source, string $expected): void
	{
		$this->assertSame(0, $this->lint((new SyntaxRewriter())->rewrite($source)));
	}

	#[DataProvider('positiveCases')]
	public function testRewritingTwiceEqualsRewritingOnce(string $source, string $expected): void
	{
		$once = (new SyntaxRewriter())->rewrite($source);
		$this->assertSame($once, (new SyntaxRewriter())->rewrite($once));
	}

	#[DataProvider('negativeCases')]
	public function testOtherSyntaxIsReturnedByteIdentical(string $source): void
	{
		$this->assertSame($source, (new SyntaxRewriter())->rewrite($source));
	}

	public function testNewlineCountIsPreserved(): void
	{
		$source = "<?php\n\$a{0};\n\n\$b{\$c{1}}{2};\n\$d = \"x\";\n";
		$rewritten = (new SyntaxRewriter())->rewrite($source);

		$this->assertSame("<?php\n\$a[0];\n\n\$b[\$c[1]][2];\n\$d = \"x\";\n", $rewritten);
		$this->assertSame(substr_count($source, "\n"), substr_count($rewritten, "\n"));
	}

	private function lint(string $code): int
	{
		$path = tempnam(sys_get_temp_dir(), 'anachron-syntax');
		file_put_contents($path, $code);
		$command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
		exec($command, $output, $status);
		unlink($path);
		return $status;
	}
}
