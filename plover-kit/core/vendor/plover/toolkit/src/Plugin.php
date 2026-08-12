<?php

namespace Plover\Core\Toolkit;

/**
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Check whether a plugin is installed
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public static function is_installed( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( ! empty( $all_plugins[ $slug ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Install new plugin
	 *
	 * @param $plugin_zip
	 *
	 * @return array|bool|\WP_Error
	 */
	public static function install_from_zip( $plugin_zip ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader = new \Plugin_Upgrader();
		$installed = $upgrader->install( $plugin_zip );

		return $installed;
	}

	/**
	 * Upgrade plugin
	 *
	 * @param $slug
	 *
	 * @return array|bool|\WP_Error
	 */
	public static function upgrade( $slug ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader = new \Plugin_Upgrader();
		$upgraded = $upgrader->upgrade( $slug );

		return $upgraded;
	}

	/**
	 * Install and active plugin, call after admin_init hook
	 *
	 * @param $plugins
	 * @param $steps
	 */
	public static function install( $plugins, $steps = [] ) {
		if ( ! is_array( $steps ) ) { // Backward compatibility
			$steps = [];
		}

		$steps = wp_parse_args( $steps, array(
			'begin' => null,
			'before' => null,
			'upgrading' => null,
			'installing' => null,
			'activating' => null,
			'success' => null,
			'failed' => null,
			'after' => null,
			'done' => null,
		) );

		$steps['begin'] && call_user_func( $steps['begin'] );

		foreach ( $plugins as $slug => $args ) {
			$args = is_array( $args ) ? $args : array( 'label' => $args );
			$name = $args['label'] ?? $slug;
			$plugin_slug = isset( $args['slug'] ) ? $args['slug'] : "{$slug}/{$slug}.php";
			$plugin_zip = "https://downloads.wordpress.org/plugin/{$slug}.latest-stable.zip";

			$steps['before'] && call_user_func( $steps['before'] );

			if ( self::is_installed( $plugin_slug ) ) {
				$steps['upgrading'] && call_user_func( $steps['upgrading'], $name );
				self::upgrade( $plugin_slug );
				$installed = true;
			} else {
				$steps['installing'] && call_user_func( $steps['installing'], $name );
				$installed = self::install_from_zip( $plugin_zip );
			}

			if ( ! is_wp_error( $installed ) && $installed ) {
				$steps['activating'] && call_user_func( $steps['activating'], $name );
				activate_plugin( $plugin_slug );
				$steps['success'] && call_user_func( $steps['success'], $name );
			} else {
				$steps['failed'] && call_user_func( $steps['failed'], $name );
			}

			$steps['after'] && call_user_func( $steps['after'] );
		}

		$steps['done'] && call_user_func( $steps['done'] );
	}
}