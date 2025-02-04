<?php

namespace Plover\Kit\Controllers;

use Plover\Core\Toolkits\Format;

/**
 * Icon post type rest api controller.
 *
 * @since 1.0.0
 */
class IconsController extends \WP_REST_Posts_Controller {

	/**
	 * The latest version of theme.json schema supported by the controller.
	 *
	 * @var int
	 */
	const LATEST_THEME_JSON_VERSION_SUPPORTED = 2;

	/**
	 * Registers the routes for posts.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'        => array(
					'icon_library_id' => array(
						'description' => __( 'The ID for the parent icon library of the icon.', 'plover-kit' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_create_params(),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'        => array(
					'icon_library_id' => array(
						'description' => __( 'The ID for the parent icon library of the icon.', 'plover-kit' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'id'              => array(
						'description' => __( 'Unique identifier for the icon.', 'plover-kit' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass Trash and force deletion.', 'plover-kit' ),
						),
					),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to icons.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 *
	 */
	public function get_items_permissions_check( $request ) {
		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->read ) ) {
			return new \WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to access icons.', 'plover-kit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Checks if a given request has access to an icon.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			return new \WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to access this icon.', 'plover-kit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Retrieves the query params for the icon collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		// Remove unneeded params.
		unset(
			$query_params['after'],
			$query_params['modified_after'],
			$query_params['before'],
			$query_params['modified_before'],
			$query_params['search_columns'],
			$query_params['slug'],
			$query_params['status']
		);

		$query_params['orderby']['default'] = 'id';
		$query_params['orderby']['enum']    = array( 'id', 'include' );
		$query_params['parent']             = array(
			'description' => __( 'Limit result set to items with particular parent IDs.', 'plover-kit' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		/**
		 * Filters collection parameters for the icon controller.
		 *
		 * @param array $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'rest_plover_icon_collection_params', $query_params );
	}

	/**
	 * Retrieves a collection of icons within the parent icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$icon_library = $this->get_parent_icon_library_post( $request['icon_library_id'] );
		if ( is_wp_error( $icon_library ) ) {
			return $icon_library;
		}

		$request->set_param( 'parent', [ $request['icon_library_id'] ] );

		return parent::get_items( $request );
	}

	/**
	 * Retrieves a single icon within the parent icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 *
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Check that the icon has a valid parent icon library.
		$icon_library = $this->get_parent_icon_library_post( $request['icon_library_id'] );
		if ( is_wp_error( $icon_library ) ) {
			return $icon_library;
		}

		if ( (int) $icon_library->ID !== (int) $post->post_parent ) {
			return new \WP_Error(
				'rest_icon_parent_id_mismatch',
				/* translators: %d: A post id. */
				sprintf( __( 'The icon does not belong to the specified icon library with id of "%d".', 'plover-kit' ), $icon_library->ID ),
				array( 'status' => 404 )
			);
		}

		return parent::get_item( $request );
	}

	/**
	 * Deletes a single icon.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 *
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$icon_library = $this->get_parent_icon_library_post( $request['icon_library_id'] );
		if ( is_wp_error( $icon_library ) ) {
			return $icon_library;
		}

		if ( (int) $icon_library->ID !== (int) $post->post_parent ) {
			return new \WP_Error(
				'rest_icon_library_parent_id_mismatch',
				/* translators: %d: A post id. */
				sprintf( __( 'The icon does not belong to the specified icon library with id of "%d".', 'plover-kit' ), $icon_library->ID ),
				array( 'status' => 404 )
			);
		}

		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for icons.
		if ( ! $force ) {
			return new \WP_Error(
				'rest_trash_not_supported',
				/* translators: %s: force=true */
				sprintf( __( 'Icons do not support trashing. Set "%s" to delete.', 'plover-kit' ), 'force=true' ),
				array( 'status' => 501 )
			);
		}

		return parent::delete_item( $request );
	}

	/**
	 * Get the parent icon library, if the ID is valid.
	 *
	 * @param int $icon_library_id Supplied ID.
	 *
	 * @return \WP_Post|\WP_Error Post object if ID is valid, WP_Error otherwise.
	 *
	 */
	protected function get_parent_icon_library_post( $icon_library_id ) {
		$error = new \WP_Error(
			'rest_post_invalid_parent',
			__( 'Invalid post parent ID.', 'plover-kit' ),
			array( 'status' => 404 )
		);

		if ( (int) $icon_library_id <= 0 ) {
			return $error;
		}

		$icon_library_post = get_post( (int) $icon_library_id );

		if ( empty( $icon_library_post ) || empty( $icon_library_post->ID )
		     || 'plover_icon_library' !== $icon_library_post->post_type
		) {
			return $error;
		}

		return $icon_library_post;
	}

	/**
	 * Get the params used when creating a new icon.
	 *
	 * @return array Icon create arguments.
	 */
	public function get_create_params() {
		$properties = $this->get_item_schema()['properties'];

		return array(
			'theme_json_version' => $properties['theme_json_version'],
			// When creating, icon_settings maybe stringified JSON, to work with multipart/form-data used
			// when uploading icons.
			'icon_settings'      => array(
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'validate_create_icon_settings' ),
				'sanitize_callback' => array( $this, 'sanitize_icon_settings' ),
			),
		);
	}

	/**
	 * Validates settings when creating an icon.
	 *
	 * @param string|array $value array or encoded JSON string of icon settings.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return true|\WP_Error True if the settings are valid, otherwise a WP_Error object.
	 */
	public function validate_create_icon_settings( $value, $request ) {
		$settings = is_array( $value ) ? $value : json_decode( $value, true );

		// Check settings string is valid JSON.
		if ( null === $settings ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'icon_settings parameter must be a valid JSON string.', 'plover-kit' ),
				array( 'status' => 400 )
			);
		}

		// Check that the icon settings match the theme.json schema.
		$schema             = $this->get_item_schema()['properties']['icon_settings'];
		$has_valid_settings = rest_validate_value_from_schema( $settings, $schema, 'icon_settings' );

		if ( is_wp_error( $has_valid_settings ) ) {
			$has_valid_settings->add_data( array( 'status' => 400 ) );

			return $has_valid_settings;
		}

		// Check that none of the required settings are empty values.
		$required = $schema['required'];
		foreach ( $required as $key ) {
			if ( isset( $settings[ $key ] ) && ! $settings[ $key ] ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %s: Name of the reset setting parameter. */
					sprintf( __( '%s cannot be empty.', 'plover-kit' ), "icon_setting[ $key ]" ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Sanitizes the icon settings when creating an icon.
	 *
	 * @param string|array $value Array or encoded JSON string of icon settings.
	 *
	 * @return array Decoded and sanitized array of icon settings.
	 */
	public function sanitize_icon_settings( $value ) {
		// Settings arrive as stringified JSON with a multipart/form-data request.
		$settings = is_array( $value ) ? $value : json_decode( $value, true );
		$schema   = $this->get_item_schema()['properties']['icon_settings'];

		$sanitized_settings = rest_sanitize_value_from_schema( $settings, $schema, 'icon_settings' );

		// Sanitize settings based on callbacks in the schema.
		return $this->sanitize_value_from_schema_callbacks( $sanitized_settings, $schema, 'icon_settings' );
	}

	/**
	 * Sanitize settings based on callbacks in the schema.
	 *
	 * @param $value
	 * @param $args
	 * @param $param
	 *
	 * @return array|mixed
	 */
	protected function sanitize_value_from_schema_callbacks( $value, $args, $param = '' ) {

		if ( isset( $args['arg_options']['sanitize_callback'] ) ) {
			return call_user_func( $args['arg_options']['sanitize_callback'], $value );
		}

		if ( 'array' === $args['type'] ) {
			$value = rest_sanitize_array( $value );

			if ( ! empty( $args['items'] ) ) {
				foreach ( $value as $index => $v ) {
					$value[ $index ] = $this->sanitize_value_from_schema_callbacks( $v, $args['items'], $param . '[' . $index . ']' );
				}
			}

			return $value;
		}

		if ( 'object' === $args['type'] ) {
			$value = rest_sanitize_object( $value );

			foreach ( $value as $property => $v ) {
				if ( isset( $args['properties'][ $property ] ) ) {
					$value[ $property ] = $this->sanitize_value_from_schema_callbacks( $v, $args['properties'][ $property ], $param . '[' . $property . ']' );
					continue;
				}

				$pattern_property_schema = rest_find_matching_pattern_property_schema( $property, $args );
				if ( null !== $pattern_property_schema ) {
					$value[ $property ] = $this->sanitize_value_from_schema_callbacks( $v, $pattern_property_schema, $param . '[' . $property . ']' );
					continue;
				}

				if ( isset( $args['additionalProperties'] ) ) {
					if ( false === $args['additionalProperties'] ) {
						unset( $value[ $property ] );
					} elseif ( is_array( $args['additionalProperties'] ) ) {
						$value[ $property ] = $this->sanitize_value_from_schema_callbacks( $v, $args['additionalProperties'], $param . '[' . $property . ']' );
					}
				}
			}

			return $value;
		}

		return $value;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			// Base properties for every Post.
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the post.', 'plover-kit' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'theme_json_version' => array(
					'description' => __( 'Version of the theme.json schema used for the icon settings.', 'plover-kit' ),
					'type'        => 'integer',
					'default'     => static::LATEST_THEME_JSON_VERSION_SUPPORTED,
					'minimum'     => 2,
					'maximum'     => static::LATEST_THEME_JSON_VERSION_SUPPORTED,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'parent'             => array(
					'description' => __( 'The ID for the parent icon library of the icon.', 'plover-kit' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'icon_settings'      => array(
					'type'                 => 'object',
					'context'              => array( 'view', 'edit', 'embed' ),
					'properties'           => array(
						'name' => array(
							'description' => __( 'Name of the icon, translatable.', 'plover-kit' ),
							'type'        => 'string',
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
						'slug' => array(
							'description' => __( 'Kebab-case unique identifier for the icon.', 'plover-kit' ),
							'type'        => 'string',
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_title',
							),
						),
						'svg'  => array(
							'description' => __( 'Raw SVG string.', 'plover-kit' ),
							'type'        => 'string',
							'arg_options' => array(
								'sanitize_callback' => [ Format::class, 'sanitize_svg' ],
							),
						),
						'tags' => array(
							'type'  => 'array',
							'items' => array(
								'type'        => 'string',
								'arg_options' => array(
									'sanitize_callback' => 'sanitize_text_field',
								),
							)
						)
					),
					'required'             => array( 'name', 'slug', 'svg' ),
					'additionalProperties' => false,
				),
			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Creates an icon for the parent icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$icon_library = $this->get_parent_icon_library_post( $request['icon_library_id'] );
		if ( is_wp_error( $icon_library ) ) {
			return $icon_library;
		}

		// Settings have already been decoded by ::sanitize_icon_settings().
		$settings = $request->get_param( 'icon_settings' );
		// Invalid SVG file become empty strings after sanitization
		if ( ! is_string( $settings['svg'] ) || ! $settings['svg'] ) {
			return new \WP_Error(
				'invalid_svg',
				__( 'Invalid svg file.', 'plover-kit' ),
				array( 'status' => 422 )
			);
		}

		// create a unique slug
		$settings['slug'] = $icon_library->post_name . '-' . $settings['slug'] . '-' . uniqid();
		// Store the updated settings for prepare_item_for_database to use.
		$request->set_param( 'icon_settings', $settings );

		// Ensure that $settings data is slashed, so values with quotes are escaped.
		// WP_REST_Posts_Controller::create_item uses wp_slash() on the post_content.
		$icon_post = parent::create_item( $request );

		if ( is_wp_error( $icon_post ) ) {
			return $icon_post;
		}

		return $icon_post;
	}

	/**
	 * Prepares a single icon post for creation.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \stdClass Post object.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_post = new \stdClass();

		// Settings have already been decoded by ::sanitize_icon_settings().
		$settings = $request->get_param( 'icon_settings' );

		$prepared_post->post_type   = $this->post_type;
		$prepared_post->post_parent = $request['icon_library_id'];
		$prepared_post->post_status = 'publish';
		$prepared_post->post_title  = $settings['name'];
		$prepared_post->post_name   = sanitize_title( $settings['slug'] );

		// Remove duplicate information from settings.
		unset( $settings['name'] );
		unset( $settings['slug'] );

		$prepared_post->post_content = wp_json_encode( $settings );

		return $prepared_post;
	}

	/**
	 * Prepares a single icon output for response.
	 *
	 * @param \WP_Post $item Post object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = $this->get_settings_from_post( $item );

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $item->ID;
		}
		if ( rest_is_field_included( 'theme_json_version', $fields ) ) {
			$data['theme_json_version'] = static::LATEST_THEME_JSON_VERSION_SUPPORTED;
		}

		if ( rest_is_field_included( 'parent', $fields ) ) {
			$data['parent'] = $item->post_parent;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		if ( rest_is_field_included( '_links', $fields ) || rest_is_field_included( '_embedded', $fields ) ) {
			$links = $this->prepare_links( $item );
			$response->add_links( $links );
		}

		/**
		 * Filters the icon data for a REST API response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WP_Post $post Font face post object.
		 * @param \WP_REST_Request $request Request object.
		 */
		return apply_filters( 'rest_prepare_plover_icon', $response, $item, $request );
	}

	/**
	 * Gets the icon's settings from the post.
	 *
	 * @param \WP_Post $post Font face post object.
	 *
	 * @return array Icon settings array.
	 *
	 */
	protected function get_settings_from_post( $post ) {
		$settings = json_decode( $post->post_content, true );

		// Default to empty strings if the settings are missing.
		return array(
			'name' => isset( $post->post_title ) && $post->post_title ? $post->post_title : '',
			'slug' => isset( $post->post_name ) && $post->post_name ? $post->post_name : '',
			'svg'  => isset( $settings['svg'] ) && $settings['svg'] ? $settings['svg'] : '',
			'tags' => isset( $settings['tags'] ) && $settings['tags'] ? $settings['tags'] : [],
		);
	}

	/**
	 * Prepares links for the request.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array Links for the given post.
	 *
	 */
	protected function prepare_links( $post ) {
		// Entity meta.
		$links = parent::prepare_links( $post );

		return array(
			'self'       => $links['self'],
			'collection' => $links['collection'],
			'parent'     => array(
				'href' => rest_url( $this->namespace . '/icon-libraries/' . $post->post_parent ),
			),
		);
	}
}
