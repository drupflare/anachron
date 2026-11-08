<?php

declare(strict_types=1);

use Drupal\Core\Render\Markup;

/**
 * @return array<string, array<int, mixed>>
 */
function drupal_get_messages(?string $type = null, bool $clear_queue = true): array
{
	$messenger = \Drupal::messenger();
	$all = $messenger->all();
	$messages = $type === null ? $all : (isset($all[$type]) ? [$type => $all[$type]] : []);
	if ($clear_queue) {
		$type === null ? $messenger->deleteAll() : $messenger->deleteByType($type);
	}
	return $messages;
}

/**
 * @return string[][]|\Drupal\Component\Render\MarkupInterface[][]
 */
function drupal_set_message(
	null|string|\Stringable $message = null,
	string $type = 'status',
	bool $repeat = true,
): array {
	$messenger = \Drupal::messenger();
	if ($message !== null) {
		$messenger->addMessage(Markup::create($message), $type, $repeat);
	}
	return $messenger->all();
}
