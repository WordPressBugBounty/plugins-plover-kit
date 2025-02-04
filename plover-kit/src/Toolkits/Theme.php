<?php

namespace Plover\Kit\Toolkits;

/**
 * @since 1.3.3
 */
class Theme {

	/**
	 * Check whether a theme is installed
	 *
	 * @param $theme
	 *
	 * @return bool
	 */
	public static function is_installed( $theme ) {
		$all_themes = wp_get_themes( array( 'errors' => false ) );

		return isset( $all_themes[ $theme ] );
	}

	/**
	 * @param $theme
	 *
	 * @return void
	 */
	public static function switch_active_theme( $theme ) {
		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_die( __( 'Sorry, you are not allowed to swicth themes on this site.', 'plover-kit' ) );
		}

		$theme = wp_get_theme( $theme );

		if ( ! $theme->exists() || ! $theme->is_allowed() ) {
			wp_die(
				'<h1>' . __( 'Something went wrong.', 'plover-kit' ) . '</h1>' .
				'<p>' . __( 'The requested theme does not exist.', 'plover-kit' ) . '</p>',
				403
			);
		}

		switch_theme( $theme->get_stylesheet() );
	}

	/**
	 * Install a theme
	 *
	 * @param $theme
	 * @param $activate
	 *
	 * @return void
	 */
	public static function install( $theme, $activate = true ) {

		global $title, $parent_file, $submenu_file;
		$title        = __( 'Install Themes', 'plover-kit' );
		$parent_file  = 'themes.php';
		$submenu_file = 'themes.php';

		require_once ABSPATH . 'wp-admin/admin-header.php';

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'The installation process is starting. This process may take a while on some hosts, so please be patient.', 'plover-kit' );
		echo '</p></div>';

		if ( self::is_installed( $theme ) ) {
			echo '<h2>';
			echo esc_html__( 'Update Theme', 'plover-kit' );
			echo '</h2>';
			self::upgrade( $theme );
		} else {
			echo '<h2>';
			echo esc_html__( 'Install Theme', 'plover-kit' );
			echo '</h2>';
			self::do_install( $theme );
		}

		if ( $activate ) {
			self::switch_active_theme( $theme );
			echo '<p>' . esc_html__( 'Done!', 'plover-kit' ) . '</p>';
		}

		require_once ABSPATH . 'wp-admin/admin-footer.php';
	}

	/**
	 * Upgrade a theme from server
	 *
	 * @param $theme
	 *
	 * @return void
	 */
	public static function upgrade( $theme ) {

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! current_user_can( 'update_themes' ) ) {
			wp_die( __( 'Sorry, you are not allowed to update themes for this site.', 'plover-kit' ) );
		}

		wp_enqueue_script( 'updates' );

		// Used in the HTML title tag.
		$title = '';
		$nonce = 'upgrade-theme_' . $theme;
		$url   = 'update.php?action=upgrade-theme&theme=' . urlencode( $theme );

		$upgrader = new \Theme_Upgrader(
			new \Theme_Upgrader_Skin(
				compact( 'title', 'nonce', 'url', 'theme' )
			)
		);
		$upgrader->upgrade( $theme );
	}

	/**
	 * Install a theme from server
	 *
	 * @param $theme
	 *
	 * @return void
	 */
	protected static function do_install( $theme ) {
		if ( ! current_user_can( 'install_themes' ) ) {
			wp_die( __( 'Sorry, you are not allowed to install themes on this site.', 'plover-kit' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // For themes_api().

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $theme,
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_die( $api );
		}

		// Used in the HTML title tag.
		$title = '';
		$nonce = 'install-theme_' . $theme;
		$url   = 'update.php?action=install-theme&theme=' . urlencode( $theme );

		$upgrader = new \Theme_Upgrader(
			new \Theme_Installer_Skin(
				compact( 'title', 'url', 'nonce', 'api' )
			)
		);
		$upgrader->install( $api->download_link );
	}
}