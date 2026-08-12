<?php

namespace Plover\Core\Nest\Cache;

use Plover\Core\Nest\Container\Container;
use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * Cache manager
 * 
 * @since 1.0.0
 */
class CacheManager {

	/**
	 * Container instance
	 * 
	 * @var \Plover\Core\Nest\Container\Container
	 */
	protected $container;

	/**
	 * All initialized drivers
	 * 
	 * @var array
	 */
	protected $drivers = [];

	/**
	 * Cache driver creators
	 * 
	 * @var array
	 */
	protected $creators = [];

	/**
	 * Default cache driver
	 * 
	 * @var string
	 */
	protected $default_driver = 'memory';

	/**
	 * Default cache prefix
	 * 
	 * @var string
	 */
	protected $cache_prefix = '';

	/**
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		// Default memory cache driver
		$this->extend( 'memory', function () {
			return new \Plover\Core\Nest\Cache\Stores\MemoryStore();
		} );
		// WordPress transient cache driver
		$this->extend( 'wp-transient', function () {
			return new \Plover\Core\Nest\Cache\Stores\WordPressTransientStore( $this->cache_prefix );
		} );
		// WordPress Object cache driver
		$this->extend( 'wp-cache', function () {
			return new \Plover\Core\Nest\Cache\Stores\WordPressObjectCacheStore( $this->cache_prefix );
		} );
	}

	/**
	 * Set default cache prefix
	 * 
	 * @param string $prefix
	 * @return void
	 */
	public function setCachePrefix( string $prefix ) {
		$this->cache_prefix = $prefix;
		// Reset all created drivers
		$this->drivers = [];
	}

	/**
	 * Set default driver
	 * 
	 * @param string $name
	 * @return void
	 */
	public function setDefaultDriver( string $name ) {
		if ( ! isset( $this->creators[ $name ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Driver [%s] is not defined.', $name ) );
		}

		$this->default_driver = $name;
	}

	/**
	 * Same as driver method
	 * 
	 * @param mixed $name
	 * @return CacheRepository
	 */
	public function store( ?string $name = null ): CacheRepository {
		return $this->driver( $name );
	}

	/**
	 * Get cache driver
	 * 
	 * @param mixed $name
	 * @return CacheRepository
	 */
	public function driver( ?string $name = null ): CacheRepository {
		$name = $name ?: $this->default_driver;
		if ( ! isset( $this->drivers[ $name ] ) ) {
			$this->drivers[ $name ] = $this->createDriver( $name );
		}
		return $this->drivers[ $name ];
	}

	/**
	 * Extend cache driver
	 * 
	 * @param string $driver
	 * @param $callback
	 * @return void
	 */
	public function extend( string $driver, callable $callback ): void {
		$this->creators[ $driver ] = $callback;
	}

	/**
	 * Create drive instance
	 * 
	 * @param string $name
	 * @throws \InvalidArgumentException
	 * @return CacheRepository
	 */
	protected function createDriver( string $name ): Store {
		if ( isset( $this->creators[ $name ] ) ) {
			$driver = call_user_func( $this->creators[ $name ], $this->container );

			return new CacheRepository( $driver );
		}

		throw new \InvalidArgumentException( sprintf( 'Cache driver [%s] is not defined.', $name ) );
	}

	/**
	 * Magic method, proxy all store methods
	 * 
	 * @param mixed $method
	 * @param mixed $arguments
	 */
	public function __call( $method, $arguments ) {
		return $this->driver()->$method( ...$arguments );
	}
}
