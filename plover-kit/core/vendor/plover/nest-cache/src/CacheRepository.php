<?php

namespace Plover\Core\Nest\Cache;

use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * @since 1.0.1
 */
class CacheRepository implements Store {
	/**
	 * Store instance
	 * 
	 * @var Store
	 */
	protected Store $store;

	/**
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Get a cache item
	 * 
	 * @param string $key
	 * @return mixed|null
	 */
	public function get( string $key, $default = null ) {
		return $this->store->get( $key );
	}

	/**
	 * Save a cache item with ttl
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return bool
	 */
	public function set( string $key, $value, int $ttl = 0 ): bool {
		return $this->store->set( $key, $value, $ttl );
	}

	/**
	 * Save a cache item, it never expires
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function forever( string $key, $value ): bool {
		return $this->store->forever( $key, $value );
	}

	/**
	 * Delete a cache item
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return $this->store->delete( $key );
	}

	/**
	 * Clear all cache
	 * 
	 * @return bool
	 */
	public function flush(): bool {
		return $this->store->flush();
	}

	/**
	 * Raw store instance
	 * 
	 * @return Store
	 */
	public function rawStore() {
		return $this->store;
	}

	/**
	 * Get a cache item, if it does not exist, call the callback to fetch and cache it.
	 * 
	 * @param string $key
	 * @param mixed $ttl
	 * @param mixed $callback
	 */
	public function remember( string $key, $ttl, $callback ) {
		$value = $this->get( $key );
		if ( $value !== null ) {
			return $value;
		}

		$value = call_user_func( $callback );

		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Get a cache item, if it does not exist, call the callback to cache it permanently.
	 * 
	 * @param string $key
	 * @param mixed $callback
	 */
	public function rememberForever( string $key, $callback ) {
		$value = $this->get( $key );
		if ( $value !== null ) {
			return $value;
		}

		$value = call_user_func( $callback );

		$this->forever( $key, $value );

		return $value;
	}
}