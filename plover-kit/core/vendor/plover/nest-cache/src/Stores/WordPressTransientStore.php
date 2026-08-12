<?php

namespace Plover\Core\Nest\Cache\Stores;

use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * WordPress transient store Implementation
 * 
 * @since 1.0.0
 */
class WordPressTransientStore implements Store {

	/**
     * Key prefix to avoid conflicts.
     *
     * @var string
     */
    private string $prefix;

	/**
	 * @param string $prefix Optional. Prefix for all transient keys. Default ''.
	 * 
	 * @throws \RuntimeException
	 */
	public function __construct( $prefix = '' ) {
		// Check WordPress runtime environment
		if ( ! function_exists( 'get_transient' ) ) {
			throw new \RuntimeException();
		}

		$this->prefix = $prefix;
	}

	/**
     * Get the full transient key with prefix.
     *
     * @param string $key
     * @return string
     */
    private function key( string $key ): string {
        return $this->prefix . $key;
    }

	/**
	 * @inheritDoc
	 * @param string $key
	 * @param mixed $default
	 */
	public function get( string $key, $default = null ) {
		$v = get_transient( $this->key( $key ) );
		if ( $v === false ) {
			return $default;
		}

		return $v;
	}

	/**
	 * @inheritDoc
	 * @param string $key
	 * @param mixed $value
	 * @param int $seconds
	 * @return bool
	 */
	public function set( string $key, $value, int $seconds = 0 ): bool {
		return set_transient( $this->key( $key ), $value, $seconds > 0 ? $seconds : 0 );
	}

	/**
	 * @inheritDoc
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function forever( string $key, $value ): bool {
		return $this->set( $this->key( $key ), $value, 0 );
	}

	/**
	 * @inheritDoc
	 * @param string $key
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return delete_transient( $this->key( $key ) );
	}

	/**
	 * @inheritDoc
	 * 
	 * Note: WordPress does not provide a way to flush only prefixed transients.
     * A full flush is not implemented for this reason.
	 * 
	 * @return bool
	 */
	public function flush(): bool {
		// No flush implementaion
		return false;
	}
}
