<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Router\Auth;
use Plover\Core\Router\Router;
use Plover\Core\Services\Extensions\Contract\Extension;

/**
 * @since 1.1.0
 */
class PatternLibrary extends Extension {

	const MODULE_NAME = 'plover_pattern_library';

	/**
	 * @return void
	 */
	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'label'   => __( 'Pattern library', 'plover-kit' ),
			'excerpt' => __( 'Add beautiful, ready-to-go layouts to your site with one click.', 'plover-kit' ),
			'icon'    => esc_url( plover_kit()->app_url( 'assets/images/pattern-library.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/pattern-library/',
		) );
	}

	/**
	 * Boot patterns extension.
	 *
	 * @return void
	 */
	public function boot() {
		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'rest_api_init', [ $this, 'register_reset_api' ] );
		add_filter( 'plover_core_editor_data', [ $this, 'localize_pattern_data' ] );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function localize_pattern_data( $data ) {
		$data['patternLibrary']['placeholder_image'] = plover_kit()->app_url( 'assets/images/pattern-placeholder.jpg' );

		return $data;
	}

	/**
	 * Register blocks
	 *
	 * @return void
	 */
	public function register_blocks() {
		register_block_type_from_metadata(
			plover_kit()->app_path( 'assets/js/patterns' )
		);
	}

	/**
	 * Register pattern library rest api.
	 *
	 * @return void
	 */
	public function register_reset_api() {
		$router = new Router( 'plover-kit/v1' );

		// Clear cache
		$router->edit( '/patterns/cache', array( $this, 'clear' ) )
		       ->use( array( Auth::class, 'can_edit_posts' ) );
		// Get pattern metas
		$router->read( '/pattern-metas', array( $this, 'patternMatas' ) )
		       ->use( array( Auth::class, 'can_edit_posts' ) );
		// Get all patterns
		$router->read( '/patterns', array( $this, 'patterns' ) )
		       ->use( array( Auth::class, 'can_edit_posts' ) );
		// Get pattern content
		$router->read( '/patterns/(?P<id>[\d]+)', array( $this, 'pattern' ) )
		       ->use( array( Auth::class, 'can_edit_posts' ) );

		$router->register();
	}

	/**
	 * Remove all patterns api request cache
	 *
	 * @param $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function clear() {
		delete_transient( 'plover-pattern-metas' );
		delete_transient( 'plover-pattern-metas-version' );
		delete_transient( 'plover-patterns-list' );
		delete_transient( 'plover-patterns-list-version' );
		delete_transient( 'plover-patterns-preview' );
		delete_transient( 'plover-patterns-preview-version' );

		return rest_ensure_response( array( 'status' => 'ok' ) );
	}

	/**
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function patternMatas() {
		return rest_ensure_response( $this->get_pattern_metas() );
	}

	/**
	 * Query patterns
	 *
	 * @param $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function patterns( $request ) {
		$paged     = max( absint( $request->get_param( 'paged' ) ), 1 );
		$taxonomy  = $request->get_param( 'taxonomy' );
		$cache_key = "plover-patterns-{$taxonomy}-{$paged}";

		$all_patterns = get_transient( 'plover-patterns-list' ) ?? array();
		// Reset patterns cache after plugin update
		if ( get_transient( 'plover-patterns-list-version' ) !== PLOVER_KIT_VERSION ) {
			$all_patterns = array();
			set_transient( 'plover-patterns-list-version', PLOVER_KIT_VERSION, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );
		}

		if ( ! isset( $all_patterns[ $cache_key ] ) ) {
			$zip      = plover_kit_compress_algorithm();
			$taxonomy = $taxonomy ? "&taxonomy={$taxonomy}" : '';
			$endpoint = "/patterns&per_page=0&zip={$zip}&paged={$paged}{$taxonomy}";
			$result   = $this->do_remote_api_request( $endpoint );
			// return errors
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// unzip if needed.
			if ( $zip && ( $result['zip'] ?? '' ) === $zip ) {
				unset( $result['zip'] );
				$result['posts'] = json_decode( plover_kit_uncompress( base64_decode( $result['posts'] ), $zip ), true );
			}

			$all_patterns[ $cache_key ] = $result;
			set_transient( 'plover-patterns-list', $all_patterns, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );
		}

		return rest_ensure_response( $all_patterns[ $cache_key ] );
	}

	/**
	 * Query pattern content
	 *
	 * @param $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function pattern( $request ) {
		$id                   = absint( $request->get_param( 'id' ) );
		$preview              = $request->get_param( 'preview' );
		$all_patterns_preview = array();
		// try to retrieve preview from cache
		if ( $preview ) {
			$all_patterns_preview = get_transient( 'plover-patterns-preview' ) ?? array();
			// Reset patterns preview cache after plugin update
			if ( get_transient( 'plover-patterns-preview-version' ) !== PLOVER_KIT_VERSION ) {
				$all_patterns_preview = array();
				set_transient( 'plover-patterns-preview-version', PLOVER_KIT_VERSION, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );
			}

			if ( isset( $all_patterns_preview[ $id ] ) ) {
				return rest_ensure_response( $all_patterns_preview[ $id ] );
			}
		}

		$result = $this->do_remote_api_request( "/patterns/{$id}", array(
			'nostats' => plover_kit_is_debug(),
			'preview' => $preview,
		) );
		// return errors
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $preview ) {
			// cache pattern preview
			$all_patterns_preview[ $id ] = $result;
			set_transient( 'plover-patterns-preview', $all_patterns_preview, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );

			return rest_ensure_response( $result );
		}

		if ( is_array( $result ) && isset( $result['content'] ) ) {
			$required_plugins      = $result['required_plugins'] ?? array();
			$metas                 = $this->get_pattern_metas();
			$required_plugins_list = is_array( $metas ) ? $metas['required_plugins_list'] : null;

			if ( ! empty( $required_plugins_list ) && ! empty( $result['required_plugins'] ) ) {
				foreach ( $required_plugins as $slug ) {
					if ( isset( $required_plugins_list[ $slug ] ) ) {
						$status = plover_kit_install_plugin( $slug, $required_plugins_list[ $slug ]['file'] );
						if ( isset( $status['errorMessage'] ) ) {
							return rest_ensure_response( $status );
						}
					}
				}
			}

			$result['content'] = wp_kses_post(
				plover_kit_process_import_icons(
					plover_kit_process_import_content_urls( $result['content'] )
				)
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get pattern metas
	 *
	 * @return array|mixed|\WP_Error
	 */
	private function get_pattern_metas() {
		$metas         = get_transient( 'plover-pattern-metas' );
		$cache_version = get_transient( 'plover-pattern-metas-version' );

		// Check from cache first
		if ( false === $metas || $cache_version !== PLOVER_KIT_VERSION ) {
			$metas = $this->do_remote_api_request( '/pattern-metas' );
			if ( ! is_wp_error( $metas ) ) {
				set_transient( 'plover-pattern-metas', $metas, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );
				set_transient( 'plover-pattern-metas-version', PLOVER_KIT_VERSION, PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS );
			}
		}

		return $metas;
	}

	/**
	 * @param $endpoint
	 * @param array $args
	 *
	 * @return \WP_Error|\array
	 */
	private function do_remote_api_request( $endpoint, $args = array() ) {
		list( $license_id, $install_id, $auth_string ) = array_pad( plover_kit_get_auth_data(), 3, null );

		$url = add_query_arg(
			array_merge( array(
				'install_id' => $install_id,
				'license_id' => $license_id,
				'auth'       => $auth_string,
			), $args ),
			PLOVER_KIT_API_V1 . $endpoint
		);

		$response = wp_remote_get( $url, array(
			'timeout' => apply_filters( 'plover-kit/timeout_for_api_request', 10 )
		) );

		// Test if the get request was not successful.
		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return $this->response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
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
