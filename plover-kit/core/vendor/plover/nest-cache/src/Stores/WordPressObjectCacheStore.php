<?php

namespace Plover\Core\Nest\Cache\Stores;

use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * WordPress object cache store implementation.
 *
 * Relies on WP's wp_cache functions. In absence of a persistent cache plugin,
 * data lives only within a single request (default WP_Object_Cache).
 * 
 * @since 1.0.1
 */
class WordPressObjectCacheStore implements Store {
    
    /**
     * Cache group used for all keys.
     *
     * @var string
     */
    private string $group;

    /**
     * Constructor.
     *
     * @param string $group Cache group to isolate keys. Default 'default'.
     *
     * @throws \RuntimeException If wp_cache functions are not available.
     */
    public function __construct( string $group = 'default' ) {
        $this->group = $group;
    }

    /**
     * Retrieve a value from cache.
     *
     * @param string $key
     * @param mixed  $default Value to return if key does not exist.
     *
     * @return mixed
     */
    public function get( string $key, $default = null ) {
        $found = false;
        $value = wp_cache_get( $key, $this->group, false, $found );

        if ( ! $found ) {
            return $default;
        }

        return $value;
    }

    /**
     * Store a value with a TTL.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds   Expiration in seconds. 0 means no expiration.
     *
     * @return bool
     */
    public function set( string $key, $value, int $seconds = 0 ): bool {
        return wp_cache_set( $key, $value, $this->group, $seconds > 0 ? $seconds : 0 );
    }

    /**
     * Store a value indefinitely.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function forever( string $key, $value ): bool {
        return $this->set( $key, $value, 0 );
    }

    /**
     * Delete a cache entry.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete( string $key ): bool {
        return wp_cache_delete( $key, $this->group );
    }

    /**
     * Flush the entire object cache.
     *
     * WARNING: wp_cache_flush() empties the WHOLE object cache, not just this group.
     * Use with care, especially when multiple plugins rely on the object cache.
     *
     * @return bool
     */
    public function flush(): bool {
        // WordPress 6.1 introduced `wp_cache_flush_group`,
        // which allows us to clear the cache for only our own group.
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            return wp_cache_flush_group( $this->group );
        }

        // Fallback to flush all cache
        return wp_cache_flush();
    }
}
