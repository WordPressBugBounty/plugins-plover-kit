<?php
/**
 * Core helpers
 *
 * @since 1.0.0
 */

if ( ! function_exists( 'plover_core' ) ) {
	/**
	 * Get global container instance or any bindings.
	 *
	 * @param $abs
	 *
	 * @return mixed|null
	 */
	function plover_core( $abs = null ) {
		$core = \Plover\Core\Plover::get_instance();
		if ( ! $core ) {
			return null;
		}

		return $abs !== null ? $core->get( $abs ) : $core;
	}
}

if ( ! function_exists( 'plover_app' ) ) {
	/**
	 * Get application instance.
	 *
	 * @param $id
	 *
	 * @return \Plover\Core\Application|null
	 */
	function plover_app( $id ) {
		return \Plover\Core\Application::get_app( $id );
	}
}

if ( ! function_exists( 'plover_block_id' ) ) {
	/**
	 * Get unique block id form block attrs.
	 *
	 * @param $attrs
	 *
	 * @return mixed|string
	 */
	function plover_block_id( $attrs ) {
		if ( isset( $attrs['ploverBlockID'] ) && $attrs['ploverBlockID'] ) {
			return sanitize_title( $attrs['ploverBlockID'] );
		}

		// fallback method.
		return wp_generate_uuid4();
	}
}

if ( ! function_exists( 'plover_upsell_url' ) ) {
	/**
	 * Manage upsell url in one place
	 *
	 * @return string
	 *
	 * @since 1.3.0
	 */
	function plover_upsell_url( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'utm_source'   => 'product_upsell',
			'utm_medium'   => 'wordpress',
			'utm_campaign' => 'product_upsell',
		) );

		$upsell_url = apply_filters( 'plover_core_upsell_url', 'https://wpplover.com/plugins/plover-kit/#plans' );

		return add_query_arg( $args, $upsell_url );
	}
}
