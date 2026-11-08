<?php

declare(strict_types=1);

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupflare\Anachron\Drupal\Entity\WrappedConfigEntity;
use Drupflare\Anachron\Drupal\Entity\WrappedContentEntity;

function taxonomy_get_vocabularies(): array
{
	return array_map(
		static fn(VocabularyInterface $vocabulary) => new WrappedConfigEntity($vocabulary),
		Vocabulary::loadMultiple(),
	);
}

function taxonomy_get_parents($tid)
{
	$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
	assert($storage instanceof TermStorageInterface);
	return array_map(
		static fn(TermInterface $term) => new WrappedContentEntity($term),
		$storage->loadParents($tid),
	);
}

/**
 * @return EntityInterface[]
 */
function taxonomy_get_term_by_name(string $name): array
{
	$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
	assert($storage instanceof TermStorageInterface);
	return $storage->loadByProperties(['name' => $name]);
}

/**
 * @return object[]
 */
function taxonomy_get_tree(
	string $vid,
	int $parent = 0,
	?int $max_depth = null,
	bool $load_entities = false,
): array {
	$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
	assert($storage instanceof TermStorageInterface);
	$tree = $storage->loadTree($vid, $parent, $max_depth, $load_entities);
	if (!$load_entities) {
		return $tree;
	}
	return array_map(static function (object $term) {
		assert($term instanceof TermInterface);
		return new WrappedContentEntity($term);
	}, $tree);
}
