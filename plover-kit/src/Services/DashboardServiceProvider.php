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
		add_action( 'admin_enqueue_scripts', [ $this, 'add_global_styles_to_admin_dashboard' ] );
		add_filter( 'plover_core_dashboard_data', [ $this, 'dashboard_data' ] );
		add_filter( 'plover_core_should_localize_dashboard_data', [ $this, 'is_dashboard_screen' ] );
		add_action( 'admin_action_install_plover_theme', [ $this, 'install_plover_theme' ] );
		add_filter( 'themes_api_result', [ $this, 'add_custom_themes_to_popular' ], 10, 3 );

		// Show release note
		if ( self::release_note() ) {
			add_filter( 'plugin_action_links', [ $this, 'add_plugin_action_links' ], 10, 2 );
			add_action( 'admin_notices', [ $this, 'release_note_notice' ], 1 );
		}

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

	public function add_global_styles_to_admin_dashboard() {
		if ( $this->is_dashboard_screen() ) {
			wp_enqueue_global_styles_css_custom_properties();
		}
	}

	public function is_dashboard_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && $screen->base === 'toplevel_page_plover-kit';
	}

	public function dashboard_data( $data ) {
		$data['root']              = 'plover-kit-dashboard-page';
		$data['expert_page']       = 'https://wpplover.com/expert/';
		$data['affiliation']       = esc_url( admin_url( 'admin.php?page=plover-kit-affiliation' ) );
		$data['registered_blocks'] = $this->get_registered_blocks();

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

		$data['theme_json'] = \WP_Theme_JSON_Resolver::get_merged_data()->get_settings();

		return $data;
	}

	public function show_admin_menu() {
		?>
        <div class="wrap plover-dashboard__wrap">
            <h2 class="screen-reader-text"><?php esc_html_e( 'Plover Kit Dashboard', 'plover-kit' ); ?></h2>
        </div>
        <div id="plover-kit-dashboard-page" class="wrap plover-kit-dashboard-page"></div>
		<?php
	}

	/**
	 * Add custom plugin action links
	 *
	 * @param $links
	 * @param $plugin_file
	 *
	 * @return mixed
	 */
	public function add_plugin_action_links( $links, $plugin_file ) {
		$release_note = self::release_note();

		if ( $release_note && PLOVER_KIT_PLUGIN_FILE === $plugin_file ) {
			$links[] = '<a target="_blank" href="' . esc_url( $release_note['link'] ) . '">' . __( 'Release Note', 'plover-kit' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Show release note notice
	 *
	 * @return void
	 */
	public function release_note_notice() {
		$release_note = self::release_note();
		if ( ! $release_note || ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		$user_id     = get_current_user_id();
		$dismisse_id = 'plover_kit_' . PLOVER_KIT_VERSION . '_release_note_notice';
		$dismissed   = get_user_meta( $user_id, 'dismissed_admin_notices', true );
		if ( is_array( $dismissed ) && in_array( $dismisse_id, $dismissed, true ) ) {
			return;
		}

		wp_admin_notice( $release_note['excerpt'], [
			'type'        => 'warning',
			'dismissible' => true,
			'id'          => $dismisse_id,
		] );
	}

	/**
	 * Add custom themes to popular page
	 *
	 * @param $themes
	 *
	 * @return void
	 */
	public function add_custom_themes_to_popular( $themes, $action, $args ) {
		// Ignore internal themes api call
		if ( $args->request === 'plover' ) {
			return $themes;
		}
		// Only add custom themes in popular fist page ajax request
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $themes;
		}
		if ( absint( $_POST['request']['page'] ) > 0 || empty( $_POST['request']['browse'] ) || 'popular' !== $_POST['request']['browse'] ) {
			return $themes;
		}

		$recommended_themes = $this->recommended_themes();
		$themes->themes     = array_merge(
			$recommended_themes,
			$themes->themes,
		);

		return $themes;
	}

	/**
	 * Generate recommended themes list
	 *
	 * @return array
	 */
	protected function recommended_themes() {
		$cache_key = 'recommended_plover_themes';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$max_display   = 6;
		$plover_themes = array_filter( Theme::themes_by_author( 'plover' ), function ( $theme ) {
			return $theme->slug !== 'plover';
		} );

		if ( empty( $plover_themes ) ) {
			return array();
		}

		$plover_theme = Theme::meta( 'plover' );
		$latest       = array_splice( $plover_themes, 0, 3 );
		if ( $plover_theme ) {
			array_unshift( $latest, $plover_theme );
		}

		mt_srand( crc32( date( 'Y-m-d' ) ) );
		shuffle( $plover_themes );
		$plover_themes = array_merge( $latest, array_slice( $plover_themes, 0, $max_display ) );
		mt_srand();

		set_transient( $cache_key, $plover_themes, 24 * HOUR_IN_SECONDS );

		return $plover_themes;
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

	/**
	 * Get all registered block types
	 *
	 * @return array|array[]
	 */
	protected function get_registered_blocks() {
		$block_types       = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$registered_blocks = array();

		foreach ( $block_types as $name => $block ) {
			$registered_blocks[] = array(
				'name'     => $name,
				'title'    => $block->title ?? $name,
				'category' => $block->category,
			);
		}

		return $registered_blocks;
	}

	/**
	 * @return string[]|null
	 */
	public static function release_note() {
		$link = 'https://wpplover.com/plover-kit/v170-more-code-snippet-types-safer-embedding-and-security-control/';

		return [
			'link'    => $link,
			'excerpt' => sprintf(
				__( '%s Major updates to the code snippets module. More Code Snippet Types, Safer Embedding, and Security Control.', 'plover-kit' ),
				'<a target="_blank" href="' . esc_url( $link ) . '">' . __( 'Plover Kit v1.7.0 release note:', 'plover-kit' ) . '</a>'
			),
		];
	}
}
