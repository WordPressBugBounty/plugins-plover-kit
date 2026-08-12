<?php

namespace Plover\Core\Framework;

use Plover\Core\Nest\Nest;
use Plover\Core\Plover;
use Plover\Core\Nest\Support\ServiceProvider as NestServiceProvider;

/**
 * @since 1.0.0
 */
abstract class ServiceProvider extends NestServiceProvider {

	/**
	 * The core instance.
	 *
	 * @var Plover
	 */
	protected $core;

	/**
	 * Create a new service provider instance.
	 *
	 * @param Plover $core
	 */
	public function __construct( Nest $core ) {
		parent::__construct( $core );
		$this->core = $core; // Backward compatibility
	}

}