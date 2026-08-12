<?php

namespace Plover\Core\Nest\Cache\Stores;

use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * In memory cache store Implementation
 * 
 * @since 1.0.0
 */
class MemoryStore implements Store {
    /**
     * Save all cached items
     * 
     * @var array
     */
	protected array $storage = [];

    /**
     * Save the cache item's expiration time
     * 
     * @var array
     */
	protected array $expires = [];

	/**
	 * @inheritDoc
	 * @param string $key
	 * @param mixed $default
	 */
	public function get( string $key, $default = null ) {
		if ( ! $this->has( $key ) ) {
			return $default;
		}
		return $this->storage[ $key ];
	}

    /**
     * Check if a cache item exists or not
     * @param string $key
     * @return bool
     */
	protected function has( string $key ): bool {
		if ( isset( $this->expires[ $key ] ) && $this->expires[ $key ] !== 0 && $this->expires[ $key ] < time() ) {
			$this->delete( $key );
			return false;
		}
		return array_key_exists( $key, $this->storage );
	}

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $value
     * @param int $seconds
     * @return bool
     */
	public function set( string $key, $value, int $seconds = 0 ): bool {
		$this->storage[ $key ] = $value;
		$this->expires[ $key ] = $seconds === 0 ? 0 : time() + $seconds;
		return true;
	}

    /**
     * @inheritDoc
     * @param string $key
     * @param mixed $value
     * @return bool
     */
	public function forever( string $key, $value ): bool {
		return $this->set( $key, $value, 0 );
	}

    /**
     * @inheritDoc
     * @param string $key
     * @return bool
     */
	public function delete( string $key ): bool {
		unset( $this->storage[ $key ], $this->expires[ $key ] );
		return true;
	}

    /**
     * @inheritDoc
     * @return bool
     */
	public function flush(): bool {
		$this->storage = [];
		$this->expires = [];
		return true;
	}
}
