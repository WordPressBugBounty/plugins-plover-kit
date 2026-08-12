<?php

namespace Plover\Core;

use Plover\Core\Nest\Nest;
use Plover\Core\Nest\Container\Container;
use Plover\Core\Services\AssetsServiceProvider;
use Plover\Core\Services\Blocks\BlocksServiceProvider;
use Plover\Core\Services\Extensions\ExtensionsServiceProvider;
use Plover\Core\Services\Settings\SettingsServiceProvider;
use Plover\Core\Toolkits\Path;
use Plover\Core\Toolkits\Str;

/**
 * @since 1.0.0
 */
class Plover extends Nest {

	/**
	 * The plover/core framework version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.3.6';

	/**
	 * Create a new plover core instance.
	 *
	 * @param $base_path
	 * @param $base_url
	 */
	public function __construct( $base_path, $base_url ) {
		parent::__construct();

		$this->bind_paths_in_container( $base_path, $base_url );
		$this->register_base_service_providers();
		$this->register_core_container_aliases();
	}

	/**
	 * Bind all the app & core paths in the container.
	 *
	 * @param string $base_path
	 *
	 * @return $this
	 */
	protected function bind_paths_in_container( string $base_path, string $base_url ): Plover {
		$app_path = untrailingslashit( $base_path );
		// TODO: A generic approach to get core path.
		$core_path = untrailingslashit( dirname( __DIR__ ) );
		$app_url   = $base_url ?: content_url( Path::get_segment( $app_path, - 2 ) );
		$core_url  = $app_url . str_replace( '\\', '/', str_replace( $app_path, '', $core_path ) );

		$this->instance( 'core.path', $core_path );
		$this->instance( 'core.url', $core_url );

		return $this;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function register_base_bindings() {
		parent::register_base_bindings();

		$this->instance( 'core.version', static::VERSION );
		$this->instance( 'core', $this );
		$this->instance( Plover::class, $this );
	}

	/**
	 * Register all the base service providers.
	 *
	 * @return void
	 */
	protected function register_base_service_providers() {
		$this->register( new SettingsServiceProvider( $this ) );
		$this->register( new AssetsServiceProvider( $this ) );
		$this->register( new ExtensionsServiceProvider( $this ) );
		$this->register( new BlocksServiceProvider( $this ) );
	}

	/**
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	protected function register_core_container_aliases() {
		foreach (
			[
				'core' => [ self::class, Nest::class, Container::class ]
			] as $key => $aliases
		) {
			foreach ( $aliases as $alias ) {
				$this->alias( $key, $alias );
			}
		}
	}

	/**
	 * Get core asset url.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function core_url( string $path = '' ): string {
		return $this->get( 'core.url' ) . Str::leadingslashit( $path );
	}

	/**
	 * Get core asset path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function core_path( string $path = '' ): string {
		return $this->get( 'core.path' ) . Str::leadingslashit( $path );
	}

	/**
	 * Get the version number of the core framework.
	 *
	 * @return string
	 */
	public function version(): string {
		return static::VERSION;
	}

	/**
	 * Is debug mode on or not.
	 *
	 * @return bool
	 * @todo project-related switches
	 */
	public function is_debug(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
