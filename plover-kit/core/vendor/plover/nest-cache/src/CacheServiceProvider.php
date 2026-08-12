<?php

namespace Plover\Core\Nest\Cache;

use Plover\Core\Nest\Support\ServiceProvider;

/**
 * @since 1.0.0
 */
class CacheServiceProvider extends ServiceProvider {

	/**
	 * @var array
	 */
	public $singletons = [
		\Plover\Core\Nest\Cache\CacheManager::class,
	];

    /**
     * @var array
     */
	public $aliases = [
		'cache' => \Plover\Core\Nest\Cache\CacheManager::class
	];
}
