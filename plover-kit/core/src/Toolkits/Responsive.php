<?php

namespace Plover\Core\Toolkits;

use Plover\Core\Toolkit\Responsive as WP_Toolkit;

/**
 * Utils for responsive design.
 *
 * @since 1.0.0
 */
class Responsive extends WP_Toolkit {

	/**
	 * Get tablet screen size
	 *
	 * @return mixed|null
	 */
	public static function tablet_screen_width() {
		return apply_filters( 'plover_core_css_tablet_breakpoint', 782 );
	}

	/**
	 * Get mobile screen size
	 *
	 * @return mixed|null
	 */
	public static function mobile_screen_width() {
		return apply_filters( 'plover_core_css_mobile_breakpoint', 600 );
	}
}