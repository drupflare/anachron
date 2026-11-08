<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Php;

/**
 * Rewrites the curly string offset syntax PHP 8.0 removed ($str{0}) to square brackets.
 *
 * Only the two braces of each offset change, each to a single character, so the output keeps
 * the input's line count and every line number in a stack trace.
 */
final class SyntaxRewriter
{
	/**
	 * Rewrites every curly offset in a PHP source string.
	 *
	 * @param string $code
	 *   PHP source.
	 *
	 * @return string
	 *   The source with curly offsets rewritten, or the same string when there are none.
	 */
	public function rewrite(string $code): string
	{
		$tokens = token_get_all($code);
		$replace = [];
		foreach ($tokens as $index => $token) {
			if ($token !== '{' || $index === 0 || !$this->startsOffset($tokens, $index, $replace)) {
				continue;
			}
			$close = $this->closingBrace($tokens, $index);
			if ($close === null || !$this->isExpression($tokens, $index + 1, $close)) {
				continue;
			}
			$replace[$index] = '[';
			$replace[$close] = ']';
		}
		if ($replace === []) {
			return $code;
		}

		$out = '';
		foreach ($tokens as $index => $token) {
			$out .= $replace[$index] ?? (is_array($token) ? $token[1] : $token);
		}
		return $out;
	}

	/**
	 * Checks whether the brace at an index directly follows something that can take an offset.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $open
	 *   Index of the brace.
	 * @param array<int, string> $replace
	 *   Replacements decided so far, which make a closing brace count as a bracket.
	 *
	 * @return bool
	 *   True when the brace is an offset opener.
	 */
	private function startsOffset(array $tokens, int $open, array $replace): bool
	{
		if ($tokens[$open - 1] === '}') {
			return ($replace[$open - 1] ?? null) === ']';
		}
		return $this->isOffsetHead($tokens, $open - 1);
	}

	/**
	 * Checks whether a token can take a curly offset.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $index
	 *   Index of the token.
	 *
	 * @return bool
	 *   True for a variable, a bracket, or a property name after an arrow.
	 */
	private function isOffsetHead(array $tokens, int $index): bool
	{
		$token = $tokens[$index];
		if (is_array($token)) {
			return $token[0] === T_VARIABLE ||
				($token[0] === T_STRING && $this->followsArrow($tokens, $index));
		}
		return $token === ']';
	}

	/**
	 * Checks whether the token at an index follows an object or nullsafe arrow.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $index
	 *   Index of the token.
	 *
	 * @return bool
	 *   True when the nearest non-space token before it is an arrow.
	 */
	private function followsArrow(array $tokens, int $index): bool
	{
		$prev = $this->previousNonSpace($tokens, $index);
		return $prev >= 0 &&
			is_array($tokens[$prev]) &&
			in_array($tokens[$prev][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true);
	}

	/**
	 * Finds the brace that closes the one at an index.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $open
	 *   Index of the opening brace.
	 *
	 * @return int|null
	 *   Index of the matching closing brace, or null when the braces never balance.
	 */
	private function closingBrace(array $tokens, int $open): ?int
	{
		$depth = 0;
		for ($k = $open, $count = count($tokens); $k < $count; $k++) {
			if ($this->opens($tokens[$k])) {
				$depth++;
			} elseif ($tokens[$k] === '}') {
				$depth--;
				if ($depth === 0) {
					return $k;
				}
			}
		}
		return null;
	}

	/**
	 * Checks whether a token opens a brace level, including string interpolation.
	 *
	 * @param string|array{int, string, int} $token
	 *   The token.
	 *
	 * @return bool
	 *   True for a plain brace, a string brace, or a dollar brace.
	 */
	private function opens(string|array $token): bool
	{
		return $token === '{' ||
			(is_array($token) &&
				in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true));
	}

	/**
	 * Checks whether the tokens between two braces form an offset expression.
	 *
	 * A block brace inside the range (a property hook body, for one) fails the check: a
	 * semicolon at the top level, or a brace after anything other than a variable, a bracket,
	 * a closing brace or a property name.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $start
	 *   Index of the first token after the opening brace.
	 * @param int $end
	 *   Index of the closing brace.
	 *
	 * @return bool
	 *   True when the range holds at least one non-space token and reads as an expression.
	 */
	private function isExpression(array $tokens, int $start, int $end): bool
	{
		$depth = 0;
		for ($k = $start; $k < $end; $k++) {
			$token = $tokens[$k];
			if ($depth === 0 && $token === ';') {
				return false;
			}
			if ($depth === 0 && $token === '{') {
				$prev = $this->previousNonSpace($tokens, $k);
				if ($prev < 0 || ($tokens[$prev] !== '}' && !$this->isOffsetHead($tokens, $prev))) {
					return false;
				}
			}
			if ($this->opens($tokens[$k])) {
				$depth++;
			} elseif ($token === '}') {
				$depth--;
			}
		}
		return $this->previousNonSpace($tokens, $end) >= $start;
	}

	/**
	 * Finds the nearest token before an index that is not whitespace.
	 *
	 * @param array<int, string|array{int, string, int}> $tokens
	 *   The token list.
	 * @param int $index
	 *   Index to search before.
	 *
	 * @return int
	 *   The index found, or -1 when only whitespace precedes it.
	 */
	private function previousNonSpace(array $tokens, int $index): int
	{
		for ($k = $index - 1; $k >= 0; $k--) {
			if (!is_array($tokens[$k]) || $tokens[$k][0] !== T_WHITESPACE) {
				return $k;
			}
		}
		return -1;
	}
}
