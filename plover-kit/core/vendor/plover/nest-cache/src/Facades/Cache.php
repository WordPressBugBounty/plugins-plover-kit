<?php

namespace Plover\Core\Nest\Cache\Facades;

use Plover\Core\Nest\Support\Facade;
use Plover\Core\Nest\Cache\Contracts\Store;

/**
 * @method static void 		setCachePrefix( string $prefix )
 * @method static void 		setDefaultDriver( string $name )
 * @method static Store 	store( ?string $name = null )
 * @method static Store 	driver( ?string $name = null )
 * @method static void 		extend( ?string $driver, $callback )
 * @method static mixed 	get( string $key )
 * @method static bool 		set( string $key, $value, int $ttl = 0 )
 * @method static bool 		forever( string $key, $value )
 * @method static bool 		delete( string $key )
 * @method static bool 		flush()
 * @method static mixed 	rememberForever( string $key, $callback )
 * @method static mixed 	remember( string $key, $ttl, $callback )
 * 
 * @since 1.0.1
 */
class Cache extends Facade {

	protected static function getFacadeAccessor() {
		return 'cache';
	}

}
