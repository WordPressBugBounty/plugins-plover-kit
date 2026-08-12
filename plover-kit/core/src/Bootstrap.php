<?php

namespace Plover\Core;

use Plover\Core\Nest\Bootstrap as NestBootstrap;

/**
 * Plover core bootstrapper
 *
 * @since 1.0.0
 */
class Bootstrap extends NestBootstrap {

	/**
	 * Current application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Create plover core bootstrapper instance.
	 *
	 * @param $id
	 * @param $base_path
	 * @param $base_url
	 */
	protected function __construct( $id, $base_path, $base_url ) {
		$this->nest = plover_core();
		if ( ! $this->nest ) {
			$this->nest = new Plover( $base_path, $base_url );
		}

		$this->app = new Application( $id, $base_path, $base_url, $this->nest );
	}

	/**
	 * @param $id
	 * @param $base_path
	 * @param $base_url
	 * @param array $providers
	 *
	 * @return Bootstrap
	 *
	 * @since 1.0.6 add base_url parameter
	 */
	public static function make( $id, $base_path, $base_url = '', $providers = [] ) {
		return ( new static( $id, $base_path, $base_url ) )
			->withProviders( $providers );
	}

	/**
	 * @return void
	 */
	public function boot() {
		if ( static::$booted ) {
			return;
		}

		static::$booted = true;

		add_action( 'after_setup_theme', function () {
			$this->nest->register_providers( static::$providers );
			$this->nest->boot();
		} );
	}
}
