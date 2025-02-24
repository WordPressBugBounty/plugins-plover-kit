<?php

namespace Plover\Kit\Services;

use Plover\Core\Assets\Scripts;
use Plover\Core\Assets\Styles;
use Plover\Core\Framework\ServiceProvider;
use Plover\Kit\Toolkits\Theme;

/**
 * @since 1.0.0
 */
class DashboardServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap this service.
	 *
	 * @param Styles $styles
	 * @param Scripts $scripts
	 *
	 * @return void
	 */
	public function boot( Styles $styles, Scripts $scripts ) {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_filter( 'plover_core_dashboard_data', [ $this, 'dashboard_data' ] );
		add_filter( 'plover_core_should_localize_dashboard_data', [ $this, 'is_dashboard_screen' ] );
		add_action( 'admin_action_install_plover_theme', [ $this, 'install_plover_theme' ] );

		$styles->enqueue_dashboard_asset( 'plover-dashboard-style', array(
			'src'       => plover_kit()->app_url( 'assets/js/dashboard/style.min.css' ),
			'path'      => plover_kit()->app_path( 'assets/js/dashboard/style.min.css' ),
			'ver'       => $this->core->is_debug() ? time() : PLOVER_KIT_VERSION,
			'rtl'       => 'replace',
			'condition' => [ $this, 'is_dashboard_screen' ],
		) );

		$styles->enqueue_dashboard_asset( 'plover-admin-style', array(
			'src'  => plover_kit()->app_url( 'assets/css/admin.css' ),
			'path' => plover_kit()->app_path( 'assets/css/admin.css' ),
			'ver'  => $this->core->is_debug() ? time() : PLOVER_KIT_VERSION,
		) );

		$scripts->enqueue_dashboard_asset( 'plover-dashboard-script', array(
			'src'         => plover_kit()->app_url( 'assets/js/dashboard/index.min.js' ),
			'path'        => plover_kit()->app_path( 'assets/js/dashboard/index.min.js' ),
			'asset'       => plover_kit()->app_path( 'assets/js/dashboard/index.min.asset.php' ),
			'ver'         => $this->core->is_debug() ? time() : PLOVER_KIT_VERSION,
			'condition'   => [ $this, 'is_dashboard_screen' ],
			'deps'        => [ 'plover-dashboard-data' ],
			'translation' => 'plover-kit',
		) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Plover Kit', 'plover-kit' ),
			__( 'Plover Kit', 'plover-kit' ),
			'manage_options',
			'plover-kit',
			'',
			plover_kit()->app_url( 'assets/images/plover-menu-logo.svg' ),
			'58.7'
		);

		add_submenu_page(
			'plover-kit',
			__( 'Plover Kit Modules', 'plover-kit' ),
			__( 'Modules', 'plover-kit' ),
			'manage_options',
			'plover-kit',
			array( $this, 'show_admin_menu' )
		);
	}

	public function is_dashboard_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && $screen->base === 'toplevel_page_plover-kit';
	}

	public function dashboard_data( $data ) {
		$data['root']        = 'plover-kit-dashboard-page';
		$data['affiliation'] = esc_url( admin_url( 'admin.php?page=plover-kit-affiliation' ) );
		if ( get_template() !== 'plover' ) {
			$data['theme'] = [
				'install'     => add_query_arg( array(
					'action'   => 'install_plover_theme',
					'theme'    => 'plover',
					'_wpnonce' => wp_create_nonce( 'install_plover_theme' )
				), admin_url( 'admin.php' ) ),
				'homepage'    => 'https://wpplover.com/themes/plover/',
				'playground'  => 'https://tastewp.com/template/ploverKit',
				'name'        => __( 'Plover Theme', 'plover-kit' ),
				'description' => __( 'Looking for a WordPress theme? Plover Theme works perfectly with Plover Kit. As a Full Site Editing theme, Plover utilizes the WordPress block editor and the extensions provided by the Plover Kit to create unique and eye-catching web design.', 'plover-kit' ),
				'screenshot'  => plover_kit()->app_url( 'assets/images/plover-screenshot.png' ),
			];
		}

		return $data;
	}

	public function show_admin_menu() {
		?>
        <div id="plover-kit-dashboard-page" class="wrap plover-kit-dashboard-page"></div>
		<?php
	}

	/**
	 * Install plover theme
	 *
	 * @return void
	 */
	public function install_plover_theme() {
		check_ajax_referer( 'install_plover_theme' );

		$theme_slug     = $_GET['theme'] ?? '';
		$allowed_themes = apply_filters( 'plover-kit/allowed_theme_slugs', [ 'plover' ] );

		if ( ! in_array( $theme_slug, $allowed_themes ) ) {
			wp_die(
				'<h1>' . __( 'Sorry, you are not allowed to install this theme.', 'plover-kit' ) . '</h1>',
				403
			);
		}

		Theme::install( $theme_slug, true );
	}
}
