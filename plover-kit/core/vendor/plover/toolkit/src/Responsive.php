<?php

namespace Plover\Core\Toolkit;

/**
 * Utils for responsive design.
 *
 * @since 1.0.0
 */
class Responsive {

	const DEFAULT_VALUE = '__INITIAL_VALUE__';

	/**
	 * All devices names
	 *
	 * @since 1.0.0
	 */
	const  DEVICES = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Promote scalar value into responsive and apply callback function to each value
	 *
	 * @param $value
	 * @param $callback
	 * @param bool $fill
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function value( $value, $callback, bool $fill = true ) {
		$responsive_value = static::promote_scalar_value_into_responsive( $value, $fill );

		foreach ( static::DEVICES as $device ) {
			$responsive_value[ $device ] = call_user_func( $callback, $responsive_value[ $device ], $device );
		}

		return $responsive_value;
	}

	/**
	 * @param $value
	 * @param bool $fill
	 *
	 * @return array
	 */
	public static function promote_scalar_value_into_responsive( $value, bool $fill = false ) {
		if ( is_array( $value ) && isset( $value['desktop'] ) ) {
			$valueWithResponsive = $value;
		} else {
			$valueWithResponsive = array(
				'desktop' => $value,
				'tablet'  => static::DEFAULT_VALUE,
				'mobile'  => static::DEFAULT_VALUE,
			);
		}

		if ( $fill ) {
			if ( ! isset( $valueWithResponsive['tablet'] ) || $valueWithResponsive['tablet'] === static::DEFAULT_VALUE ) {
				$valueWithResponsive['tablet'] = $valueWithResponsive['desktop'];
			}
			if ( ! isset( $valueWithResponsive['mobile'] ) || $valueWithResponsive['mobile'] === static::DEFAULT_VALUE ) {
				$valueWithResponsive['mobile'] = $valueWithResponsive['tablet'];
			}
		}

		return $valueWithResponsive;
	}

	/**
	 * @param $value
	 * @param $device
	 *
	 * @return mixed
	 */
	public static function get_scalar_value_by_device( $value, $device = 'desktop' ) {
		return static::promote_scalar_value_into_responsive( $value, true )[ $device ];
	}

	/**
	 * Wrap desktop only css with media query.
	 *
	 * @param $css
	 *
	 * @return string
	 */
	public static function desktop_css( $css ) {
		return $css; // desktop first, don't need any media query.
	}

	/**
	 * Mobile screen width
	 * 
	 * @return int
	 */
	public static function tablet_screen_width() {
		return 782;
	}

	/**
	 * Get tablet breakpoint
	 *
	 * @return mixed|null
	 */
	public static function tablet_breakpoint( $mobileFirst = false ) {
		$breakpoint = static::tablet_screen_width();
		if ( ! $mobileFirst ) {
			$breakpoint --;
		}

		return $breakpoint . 'px';
	}

	/**
	 * Wrap tablet only css with media query.
	 *
	 * @param $css
	 *
	 * @return string
	 */
	public static function tablet_css( $css ) {
		return '@media (max-width: ' . static::tablet_breakpoint() . ') {' . $css . '}';
	}

	/**
	 * Mobile screen width
	 * 
	 * @return int
	 */
	public static function mobile_screen_width() {
		return 600;
	}

	/**
	 * Get mobile breakpoint
	 *
	 * @return mixed|null
	 */
	public static function mobile_breakpoint( $mobileFirst = false ) {
		$breakpoint = static::mobile_screen_width();
		if ( ! $mobileFirst ) {
			$breakpoint --;
		}

		return $breakpoint . 'px';
	}

	/**
	 * Wrap mobile only css with media query.
	 *
	 * @param $css
	 *
	 * @return string
	 */
	public static function mobile_css( $css ) {
		return '@media (max-width: ' . static::mobile_breakpoint() . ') {' . $css . '}';
	}
}