<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Router\Auth;
use Plover\Core\Router\Router;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Kit\Assets\IconLibrarySource;
use Plover\Kit\Controllers\IconLibrariesController;
use Plover\Kit\Controllers\IconsController;

class IconLibrary extends Extension {

	const MODULE_NAME = 'plover_icon_library';

	/**
	 * @return void
	 */
	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'label'   => __( 'Icon library', 'plover-kit' ),
			'excerpt' => __( 'Icon library allows you to manage icon libraries and upload your custom svg icons.', 'plover-kit' ),
			'icon'    => esc_url( plover_kit()->app_url( 'assets/images/icon-library.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/icon-library/',
			'fields'  => array(
				'icon_library' => array(
					'control' => Control::T_PLACEHOLDER,
				),
			)
		) );
	}

	/**
	 * Boot icons extension.
	 *
	 * @return void
	 */
	public function boot() {
		// dashboard assets should always be queued.
		$this->scripts->enqueue_dashboard_asset( 'plover-icon-library', array(
			'src'   => plover_kit()->app_url( 'assets/js/block-extensions/icon-library/index.min.js' ),
			'path'  => plover_kit()->app_path( 'assets/js/block-extensions/icon-library/index.min.js' ),
			'ver'   => $this->core->is_debug() ? time() : PLOVER_KIT_VERSION,
			'asset' => plover_kit()->app_path( 'assets/js/block-extensions/icon-library/index.min.asset.php' ),
		) );

		$this->styles->enqueue_dashboard_asset( 'plover-icon-library', array(
			'src'  => plover_kit()->app_url( 'assets/js/block-extensions/icon-library/style.min.css' ),
			'path' => plover_kit()->app_path( 'assets/js/block-extensions/icon-library/style.min.css' ),
			'ver'  => $this->core->is_debug() ? time() : PLOVER_KIT_VERSION,
			'rtl'  => 'replace',
		) );

		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'rest_api_init', [ $this, 'register_reset_api' ] );
		add_action( 'deleted_post', [ $this, 'after_delete_icon_library' ], 10, 2 );

		// register icon source.
		$this->core->get( 'icons' )->register_icon_source( new IconLibrarySource() );
	}

	/**
	 * Register icon library post type.
	 *
	 * @return void
	 */
	public function register_post_types() {
		register_post_type( 'plover_icon_library', array(
			'labels'                         => array(
				'name'          => __( 'Icon Libraries', 'plover-kit' ),
				'singular_name' => __( 'Icon Library', 'plover-kit' ),
			),
			'public'                         => plover_kit_is_debug(),
			'hierarchical'                   => false,
			'capabilities'                   => array(
				'read'                   => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'create_posts'           => 'manage_options',
				'publish_posts'          => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_published_posts' => 'manage_options',
			),
			'map_meta_cap'                   => true,
			'query_var'                      => false,
			'rewrite'                        => false,
			'show_in_rest'                   => true,
			'rest_base'                      => 'icon-libraries',
			'rest_namespace'                 => 'plover-kit/v1',
			'rest_controller_class'          => IconLibrariesController::class,
			// Disable autosave endpoints for icon libraries.
			'autosave_rest_controller_class' => 'stdClass',
		) );

		register_post_type( 'plover_icon', array(
			'labels'                         => array(
				'name'          => __( 'Icons', 'plover-kit' ),
				'singular_name' => __( 'Icon', 'plover-kit' ),
			),
			'public'                         => plover_kit_is_debug(),
			'hierarchical'                   => false,
			'capabilities'                   => array(
				'read'                   => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'create_posts'           => 'manage_options',
				'publish_posts'          => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_published_posts' => 'manage_options',
			),
			'map_meta_cap'                   => true,
			'query_var'                      => false,
			'rewrite'                        => false,
			'show_in_rest'                   => true,
			'rest_base'                      => 'icons',
			'rest_namespace'                 => 'plover-kit/v1',
			'rest_controller_class'          => IconsController::class,
			// Disable autosave endpoints for icon libraries.
			'autosave_rest_controller_class' => 'stdClass',
		) );
	}

	/**
	 * Deletes child icons when an icon library is deleted.
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @return void
	 */
	public function after_delete_icon_library( $post_id, $post ) {
		if ( 'plover_icon_library' !== $post->post_type ) {
			return;
		}

		$icons = get_children(
			array(
				'post_parent' => $post_id,
				'post_type'   => 'plover_icon',
			)
		);

		foreach ( $icons as $icon ) {
			wp_delete_post( $icon->ID, true );
		}
	}

	/**
	 * Register icon collections rest api.
	 *
	 * @return void
	 */
	public function register_reset_api() {
		$router = new Router( 'plover-kit/v1' );

		$router->read( '/icon-collections', [ $this, '_collections' ] )
		       ->use( [ Auth::class, 'can_manage_options' ] );

		$router->read( '/icon-collections/(?P<collection>[0-9|a-z|_-]+)', [ $this, '_collection' ] )
		       ->use( [ Auth::class, 'can_manage_options' ] );


		$router->register();
	}

	/**
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function _collections() {
		$response = wp_remote_get( PLOVER_KIT_API_V1 . '/icon-collections', array(
			'timeout' => apply_filters( 'plover-kit/timeout_for_api_request', 10 )
		) );

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return $this->response_error( $response );
		}

		$libraries = json_decode( wp_remote_retrieve_body( $response ), true );

		return rest_ensure_response( $libraries );
	}

	/**
	 * @param \WP_REST_Request|null $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * @throws \Exception
	 */
	public function _collection( ?\WP_REST_Request $request ) {
		$collection = $request->get_param( 'collection' );
		$zip        = plover_kit_compress_algorithm();

		$body = get_transient( 'plover_icon_collections_' . $collection );
		if ( false === $body ) {

			list( $license_id, $install_id, $auth_string ) = array_pad( plover_kit_get_auth_data(), 3, null );

			$endpoint = add_query_arg( array(
				'install_id' => $install_id,
				'license_id' => $license_id,
				'auth'       => $auth_string,
				'zip'        => $zip,
				'fields'     => 'icons'
			), PLOVER_KIT_API_V1 . "/icon-collections/{$collection}" );

			$response = wp_remote_get( $endpoint, array(
				'timeout' => apply_filters( 'plover-kit/timeout_for_api_request', 10 )
			) );

			// Test if the get request was not successful.
			if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
				return $this->response_error( $response );
			}

			$body = wp_remote_retrieve_body( $response );
			set_transient( 'plover_icon_collections_' . $collection, $body, DAY_IN_SECONDS );
		}

		$collection = json_decode( $body, true );
		// unzip if needed.
		if ( $zip && ( $collection['zip'] ?? '' ) === $zip ) {
			unset( $collection['zip'] );
			$collection['icons'] = json_decode( plover_kit_uncompress( base64_decode( $collection['icons'] ), $zip ), true );
		}

		return rest_ensure_response( $collection );
	}

	/**
	 * Helper function: get the right format of response errors.
	 *
	 * @param array|\WP_Error $response Array or WP_Error or the response.
	 *
	 * @return \WP_Error Error code and error message.
	 */
	private function response_error( $response ) {
		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $body && isset( $body['code'] ) && isset( $body['message'] ) ) {
				return new \WP_Error( $body['code'], $body['message'], $body['data'] ?? '' );
			}
		}

		$response_error = array();

		if ( is_array( $response ) ) {
			$response_error['error_code']    = $response['response']['code'];
			$response_error['error_message'] = $response['response']['message'];
		} else {
			$response_error['error_code']    = $response->get_error_code();
			$response_error['error_message'] = $response->get_error_message();
		}

		return new \WP_Error(
			'http_error',
			sprintf( /* translators: %1$s and %3$s - strong HTML tags, %2$s - file URL, %4$s - br HTML tag, %5$s - error code, %6$s - error message. */
				__( 'An error occurred: %1$s - %2$s.', 'plover-kit' ),
				$response_error['error_code'],
				$response_error['error_message']
			) .
			apply_filters( 'plover-kit/message_after_api_request_error', '' )
		);
	}
}
