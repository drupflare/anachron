<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Asset;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\DestructableInterface;

class RetrofitLibraryDiscovery implements
	LibraryDiscoveryInterface,
	CacheCollectorInterface,
	DestructableInterface
{
	/**
	 * @var array<mixed[]>
	 */
	protected array $retrofitLibraries = [];

	public function __construct(private readonly LibraryDiscoveryCollector $inner) {}

	public function has($key)
	{
		return $this->inner->has($key);
	}

	public function get($key)
	{
		return $this->inner->get($key);
	}

	public function set($key, $value)
	{
		$this->inner->set($key, $value);
	}

	public function delete($key)
	{
		$this->inner->delete($key);
	}

	public function reset()
	{
		$this->inner->reset();
	}

	public function clear()
	{
		$this->inner->clear();
	}

	public function destruct()
	{
		$this->inner->destruct();
	}

	/**
	 * @param string $extension
	 * @return array<mixed[]>
	 */
	public function getLibrariesByExtension($extension): array
	{
		return match ($extension) {
			'retrofit' => $this->retrofitLibraries,
			default => $this->inner->getLibrariesByExtension($extension),
		};
	}

	/**
	 * @param string $extension
	 * @param string $name
	 * @return mixed[]|false
	 */
	public function getLibraryByName($extension, $name): array|false
	{
		return match ($extension) {
			'retrofit' => $this->retrofitLibraries[$name] ?? false,
			default => $this->inner->getLibraryByName($extension, $name),
		};
	}

	public function clearCachedDefinitions(): void
	{
		$this->inner->clearCachedDefinitions();
	}

	/**
	 * @param array{
	 *   css?: mixed[],
	 *   js?: mixed[],
	 *   requires_jquery?: bool,
	 * } $attachments
	 */
	public function setRetrofitLibrary(string $key, array $attachments): void
	{
		$this->retrofitLibraries[$key]['license'] = [];
		if (!empty($attachments['js'])) {
			$this->retrofitLibraries[$key]['dependencies'][] = 'core/drupalSettings';
			$this->retrofitLibraries[$key]['js'][] = [
				'data' => 'retrofit',
				'scope' => 'footer',
			];
			if (!empty($attachments['requires_jquery'])) {
				$this->retrofitLibraries[$key]['dependencies'][] = 'core/jquery';
				$this->retrofitLibraries[$key]['dependencies'][] = 'core/once';
			}
		}
		foreach (['css', 'js'] as $type) {
			foreach ($attachments[$type] ?? [] as $data => $options) {
				if (!is_array($options)) {
					$options = ['data' => $options];
				}
				if (!is_numeric($data)) {
					$options['data'] = $data;
				}
				$options += [
					'type' => 'file',
					'version' => -1,
				];
				switch ($type) {
					case 'css':
						$options['weight'] ??= 0;
						$options['weight'] += match ($options['group'] ?? CSS_DEFAULT) {
							CSS_SYSTEM => CSS_LAYOUT,
							CSS_THEME, 100 => CSS_AGGREGATE_THEME,
							default => CSS_AGGREGATE_DEFAULT,
						};
						if (
							!isset($options['group']) ||
							$options['group'] !== CSS_AGGREGATE_THEME
						) {
							$options['group'] = CSS_AGGREGATE_DEFAULT;
						}
						break;

					case 'js':
						$options['group'] = JS_LIBRARY;
						$options['minified'] ??= false;
						break;
				}
				$this->retrofitLibraries[$key][$type][] = $options;
			}
		}
	}
}
