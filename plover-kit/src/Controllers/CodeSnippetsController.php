<?php

namespace Plover\Kit\Controllers;

/**
 * Code snippet post type rest api controller.
 *
 * @since 1.7.0
 */
class CodeSnippetsController extends \WP_REST_Posts_Controller {

	/**
	 * Register custom routes
	 *
	 * @return void
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/capabilities',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_user_capabilities' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(
					'capabilities' => array(
						'required'          => true,
						'type'              => 'object',
						'sanitize_callback' => function ( $caps ) {
							$sanitized    = [];
							$allowed_caps = array( 'insert_plover_code_snippet' );

							foreach ( $caps as $cap => $user_ids ) {
								if ( in_array( $cap, $allowed_caps ) && is_array( $user_ids ) ) {
									$sanitized[ $cap ] = array_map( 'absint', $user_ids );
								}
							}

							return $sanitized;
						},
					),
				)
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/inline',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_inline_items' ),
				'permission_callback' => array( $this, 'inline_items_permissions_check' ),
			)
		);
	}

	/**
	 * Online logged in user can access inline code snippets.
	 *
	 * @return bool|\WP_Error
	 */
	public function inline_items_permissions_check() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.', 'plover-kit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get inline code snippets the current user is allowed to use.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function get_inline_items( $request ) {
		// Query all publish (enabled) or draft (disabled) snippets that have the "allow_inline" meta enabled.
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish,draft',
			'posts_per_page' => - 1,
			'fields'         => 'ids',  // Lightweight: only fetch IDs first
			'meta_query'     => array(
				array(
					'key'     => 'plover_kit_code_snippet_allow_inline',
					'value'   => true,
					'compare' => '=',
				),
			),
		);

		$snippet_ids = get_posts( $args );

		// Build a list of snippet data (ID and title) for initial filtering.
		$snippets = array();
		foreach ( $snippet_ids as $id ) {
			$snippets[] = array(
				'id'    => (int) $id,
				'title' => get_the_title( $id ),
			);
		}

		// If the user is an admin or has the dedicated capability,
		// return every inline-enabled snippet.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'insert_plover_code_snippet' ) ) {
			return rest_ensure_response( $snippets );
		}

		// Preload all allowlist meta in one batch to avoid per-post queries.
		if ( ! empty( $snippet_ids ) ) {
			update_meta_cache( 'post', $snippet_ids );
		}

		// For other users, return only snippets where they appear in the whitelist meta.
		$current_user_id = get_current_user_id();
		$items           = array();

		foreach ( $snippets as $snippet ) {
			$allowed_users = get_post_meta( $snippet['id'], 'plover_kit_code_snippet_allow_inline_users', true );

			if ( is_array( $allowed_users ) && in_array( $current_user_id, $allowed_users ) ) {
				$items[] = $snippet;
			}
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Check administrator permission
	 *
	 * @return bool|\WP_Error
	 */
	public function admin_permissions_check() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden_context',
			__( 'Sorry, you are not allowed to access this resource.', 'plover-kit' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Update user capabilities related to code snippets.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function update_user_capabilities( $request ) {
		$capabilities = $request->get_param( 'capabilities' );
		if ( empty( $capabilities ) || ! is_array( $capabilities ) ) {
			return new \WP_Error( 'invalid_capabilities', __( 'Invalid capabilities.', 'plover-kit' ), [ 'status' => 400 ] );
		}

		global $wpdb;
		$cap_key = $wpdb->get_blog_prefix() . 'capabilities';

		// Retrieve all user IDs at once
		$all_user_ids = get_users( [ 'fields' => 'ID' ] );

		// Bulk preload all user meta (capability information)
		update_meta_cache( 'user', $all_user_ids );

		// Build an in-memory map: user_id => list of capabilities the user has
		$user_caps_map = [];
		foreach ( $all_user_ids as $uid ) {
			// Fetch from cached meta, no additional database queries
			$caps = get_user_meta( $uid, $cap_key, true );
			if ( is_array( $caps ) ) {
				$user_caps_map[ $uid ] = array_keys( $caps );
			} else {
				$user_caps_map[ $uid ] = [];
			}
		}

		$results = [];

		foreach ( $capabilities as $cap_name => $desired_user_ids ) {
			$cap_name = sanitize_key( $cap_name );
			if ( empty( $cap_name ) ) {
				continue;
			}

			// Users who currently have this capability
			$current_users = [];
			foreach ( $user_caps_map as $uid => $user_caps ) {
				if ( in_array( $cap_name, $user_caps, true ) ) {
					$current_users[] = $uid;
				}
			}

			// Users to add: in the request but do not currently have the capability
			$to_add = array_diff( $desired_user_ids, $current_users );

			// Candidates for removal: currently have the capability but not in the request
			$to_remove_candidates = array_diff( $current_users, $desired_user_ids );

			// Filter out administrators: they must never lose any capability
			$to_remove = array_filter( $to_remove_candidates, function ( $user_id ) {
				return ! user_can( $user_id, 'manage_options' );
			} );

			// Add capabilities
			foreach ( $to_add as $user_id ) {
				$user = get_user_by( 'ID', $user_id );
				if ( $user ) {
					$user->add_cap( $cap_name );
					// Update the in-memory map to reflect the change immediately
					$user_caps_map[ $user_id ][] = $cap_name;
				}
			}

			// Remove capabilities
			foreach ( $to_remove as $user_id ) {
				$user = get_user_by( 'ID', $user_id );
				if ( $user ) {
					$user->remove_cap( $cap_name );
					// Update the map as well
					$user_caps_map[ $user_id ] = array_diff( $user_caps_map[ $user_id ], [ $cap_name ] );
				}
			}

			$results[ $cap_name ] = [
				'added'   => array_values( $to_add ),
				'removed' => array_values( $to_remove ),
			];
		}

		return rest_ensure_response( [
			'success' => true,
			'results' => $results,
		] );
	}

	/**
	 * Retrieves the query params for the code snippets collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['code_type'] = [
			'description'       => __( 'Limit result set to specified code type.', 'plover-kit' ),
			'type'              => 'string',
			'enum'              => [ 'css', 'js', 'html', 'php' ],
			'sanitize_callback' => 'sanitize_text_field',
		];

		return $query_params;
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_post = parent::prepare_item_for_database( $request );
		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$meta = $request->get_param( 'meta' );
		if ( isset( $meta['plover_kit_code_snippet_type'] ) && $meta['plover_kit_code_snippet_type'] === 'php' ) {
			if ( ! plover_fs()->can_use_premium_code() ) {
				return new \WP_Error( 'upgrade-needed', __( 'Please upgrade to the Premium plan to use PHP code snippets.', 'plover-kit' ), [ 'status' => 400 ] );
			}
		}

		return $prepared_post;
	}

	/**
	 * Filters the query arguments for the code snippets collection.
	 *
	 * @param array $prepared_args Key value array of query var to query value.
	 * @param \WP_REST_Request $request The request used.
	 *
	 * @return array Modified query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$prepared_args = parent::prepare_items_query( $prepared_args, $request );

		$meta_query = [];

		// Code type filter
		$code_type = $request->get_param( 'code_type' );
		if ( in_array( $code_type, [ 'css', 'js', 'html', 'php' ], true ) ) {
			$meta_query[] = [
				'key'     => 'plover_kit_code_snippet_type',
				'value'   => $code_type,
				'compare' => '=',
			];
		}

		if ( ! empty( $meta_query ) ) {
			$prepared_args['meta_query'] = $meta_query;
		}

		return $prepared_args;
	}
}