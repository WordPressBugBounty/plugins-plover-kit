<?php

namespace Plover\Kit\Controllers;

/**
 * Icon library post type rest api controller.
 *
 * @since 1.0.0
 */
class IconLibrariesController extends \WP_REST_Posts_Controller {

	/**
	 * The latest version of theme.json schema supported by the controller.
	 *
	 * @var int
	 */
	const LATEST_THEME_JSON_VERSION_SUPPORTED = 2;

	/**
	 * Checks if a given request has access to icon libraries.
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
				__( 'Sorry, you are not allowed to access icon libraries.', 'plover-kit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Checks if a given request has access to a icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 *
	 */
	public function get_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			return new \WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to access this icon library.', 'plover-kit' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Validates settings when creating or updating a icon library.
	 *
	 * @param string $value Encoded JSON string of icon library settings.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return true|\WP_Error True if the settings are valid, otherwise a WP_Error object.
	 */
	public function validate_icon_library_settings( $value, $request ) {
		$settings = is_array( $value ) ? $value : json_decode( $value, true );
		// Check settings string is valid JSON.
		if ( null === $settings ) {
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: Parameter name: "icon_library_settings". */
				sprintf( __( '%s parameter must be a valid JSON string.', 'plover-kit' ), 'icon_library_settings' ),
				array( 'status' => 400 )
			);
		}

		$schema   = $this->get_item_schema()['properties']['icon_library_settings'];
		$required = $schema['required'];

		if ( isset( $request['id'] ) ) {
			// Allow sending individual properties if we are updating an existing icon library.
			unset( $schema['required'] );

			// But don't allow updating the slug, since it is used as a unique identifier.
			if ( isset( $settings['slug'] ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %s: Name of parameter being updated: icon_library_settings[slug]". */
					sprintf( __( '%s cannot be updated.' ), 'icon_library_settings[slug]' ),
					array( 'status' => 400 )
				);
			}
		}

		// Check that the icon library settings match the theme.json schema.
		$has_valid_settings = rest_validate_value_from_schema( $settings, $schema, 'icon_library_settings' );

		if ( is_wp_error( $has_valid_settings ) ) {
			$has_valid_settings->add_data( array( 'status' => 400 ) );

			return $has_valid_settings;
		}

		// Check that none of the required settings are empty values.
		foreach ( $required as $key ) {
			if ( isset( $settings[ $key ] ) && ! $settings[ $key ] ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %s: Name of the reset setting parameter. */
					sprintf( __( '%s cannot be empty.', 'plover-kit' ), "icon_library_settings[ $key ]" ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Sanitizes the icon library settings when creating or updating a icon library.
	 *
	 * @return array Decoded array of icon library settings.
	 */
	public function sanitize_icon_library_settings( $value ) {
		// Settings arrive as stringified JSON with a multipart/form-data request.
		$settings = is_array( $value ) ? $value : json_decode( $value, true );

		$schema = $this->get_item_schema()['properties']['icon_library_settings'];

		$sanitized_settings = rest_sanitize_value_from_schema( $settings, $schema, 'icon_library_settings' );

		// Sanitize settings based on callbacks in the schema.
		return $this->sanitize_value_from_schema_callbacks( $sanitized_settings, $schema, 'icon_library_settings' );
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
	 * Get the arguments used when creating or updating an icon library.
	 *
	 * @return array Icon Library create/edit arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = \WP_REST_Server::CREATABLE ) {
		if ( \WP_REST_Server::CREATABLE === $method || \WP_REST_Server::EDITABLE === $method ) {
			$properties = $this->get_item_schema()['properties'];

			return array(
				'theme_json_version'    => $properties['theme_json_version'],
				// When creating or updating, icon_library_settings is stringified JSON, to work with multipart/form-data.
				'icon_library_settings' => array(
					'type'              => 'string',
					'required'          => true,
					'validate_callback' => array( $this, 'validate_icon_library_settings' ),
					'sanitize_callback' => array( $this, 'sanitize_icon_library_settings' ),
				),
			);
		}

		return parent::get_endpoint_args_for_item_schema( $method );
	}

	/**
	 * Prepares a single icon libraries post for create or update.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \stdClass|\WP_Error Post object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_post = new \stdClass();
		// Settings have already been decoded by ::sanitize_icon_library_settings().
		$settings = $request->get_param( 'icon_library_settings' );

		// This is an update and we merge with the existing icon library.
		if ( isset( $request['id'] ) ) {
			$existing_post = $this->get_post( $request['id'] );
			if ( is_wp_error( $existing_post ) ) {
				return $existing_post;
			}

			$prepared_post->ID = $existing_post->ID;
			$existing_settings = $this->get_settings_from_post( $existing_post );
			$settings          = array_merge( $existing_settings, $settings );
		}

		$prepared_post->post_type   = $this->post_type;
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
	 * Gets the icon library's settings from the post.
	 *
	 * @param \WP_Post $post Icon Library post object.
	 *
	 * @return array Icon Library settings array.
	 */
	protected function get_settings_from_post( $post ) {
//		$settings_json = json_decode( $post->post_content, true );
		// Default to empty strings if the settings are missing.
		return array(
			'name' => isset( $post->post_title ) && $post->post_title ? $post->post_title : '',
			'slug' => isset( $post->post_name ) && $post->post_name ? $post->post_name : '',
//			'icons' => isset( $settings_json['icons'] ) && $settings_json['icons'] ? $settings_json['icons'] : '',
		);
	}

	/**
	 * Creates a single icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 *
	 */
	public function create_item( $request ) {
		$settings = $request->get_param( 'icon_library_settings' );

		// Check that the icon library slug is unique.
		$query = new \WP_Query(
			array(
				'post_type'              => $this->post_type,
				'posts_per_page'         => 1,
				'name'                   => $settings['slug'],
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( ! empty( $query->posts ) ) {
			return new \WP_Error(
				'rest_duplicate_icon_library',
				/* translators: %s: Icon library slug. */
				sprintf( __( 'A icon library with slug "%s" already exists.', 'plover-kit' ), $settings['slug'] ),
				array( 'status' => 400 )
			);
		}

		return parent::create_item( $request );
	}

	/**
	 * Deletes a single icon library.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 *
	 */
	public function delete_item( $request ) {
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for icon libraries.
		if ( ! $force ) {
			return new \WP_Error(
				'rest_trash_not_supported',
				/* translators: %s: force=true */
				sprintf( __( 'Icon libraries do not support trashing. Set "%s" to delete.', 'plover-kit' ), 'force=true' ),
				array( 'status' => 501 )
			);
		}

		return parent::delete_item( $request );
	}

	/**
	 * Prepares a single icon library output for response.
	 *
	 * @param \WP_Post $item Post object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 *
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		if ( $request->get_param( '_icons_count' ) !== 'false' ) {
			$fields[] = 'count';
		}
		if ( $request->has_param( '_icons' ) ) {
			$fields[] = 'icons';
		}

		$settings = $this->get_settings_from_post( $item );
		$data     = array();

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $item->ID;
		}

		if ( rest_is_field_included( 'theme_json_version', $fields ) ) {
			$data['theme_json_version'] = static::LATEST_THEME_JSON_VERSION_SUPPORTED;
		}

		if ( rest_is_field_included( 'icon_library_settings.name', $fields ) ) {
			$data['name'] = $settings['name'];
		}
		if ( rest_is_field_included( 'icon_library_settings.slug', $fields ) ) {
			$data['slug'] = $settings['slug'];
		}
		if ( rest_is_field_included( 'count', $fields ) ) {
			$data['count'] = $this->get_icons_count( $item->ID );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		if ( rest_is_field_included( '_links', $fields ) ) {
			$links = $this->prepare_links( $item );
			$response->add_links( $links );
		}

		/**
		 * Filters the icon library data for a REST API response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WP_Post $post Font family post object.
		 * @param \WP_REST_Request $request Request object.
		 *
		 */
		return apply_filters( 'rest_prepare_plover_icon_library', $response, $item, $request );
	}

	/**
	 * Get the child icon posts count.
	 *
	 * @param int $icon_library_id Font family post ID.
	 *
	 * @return int
	 */
	protected function get_icons_count( $icon_library_id ) {
		$query = new \WP_Query(
			array(
				'post_parent'            => $icon_library_id,
				'post_type'              => 'plover_icon',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return $query->found_posts;
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
				'id'                    => array(
					'description' => __( 'Unique identifier for the post.', 'plover-kit' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'theme_json_version'    => array(
					'description' => __( 'Version of the theme.json schema used for the icon settings.', 'plover-kit' ),
					'type'        => 'integer',
					'default'     => static::LATEST_THEME_JSON_VERSION_SUPPORTED,
					'minimum'     => 2,
					'maximum'     => static::LATEST_THEME_JSON_VERSION_SUPPORTED,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'icons'                 => array(
					'description' => __( 'The IDs of the child icons in the icon library.', 'plover-kit' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit', 'embed' ),
					'items'       => array(
						'type' => 'integer',
					),
				),
				'icon_library_settings' => array(
					'description'          => __( 'icon-library definition.', 'plover-kit' ),
					'type'                 => 'object',
					'context'              => array( 'view', 'edit', 'embed' ),
					'properties'           => array(
						'name' => array(
							'description' => __( 'Name of the icon library preset, translatable.', 'plover-kit' ),
							'type'        => 'string',
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
						'slug' => array(
							'description' => __( 'Kebab-case unique identifier for the icon library preset.', 'plover-kit' ),
							'type'        => 'string',
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_title',
							),
						),
					),
					'required'             => array( 'name', 'slug' ),
					'additionalProperties' => false,
				),
			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieves the query params for the icon library collection.
	 *
	 * @return array Collection parameters.
	 *
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		// Remove unneeded params.
		unset(
			$query_params['after'],
			$query_params['modified_after'],
			$query_params['before'],
			$query_params['modified_before'],
			$query_params['search'],
			$query_params['search_columns'],
			$query_params['status']
		);

		$query_params['orderby']['default'] = 'id';
		$query_params['orderby']['enum']    = array( 'id', 'include' );

		/**
		 * Filters collection parameters for the icon library controller.
		 *
		 * @param array $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'rest_plover_icon_library_collection_params', $query_params );
	}

	/**
	 * Prepares icon library links for the request.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		// Entity meta.
		$links = parent::prepare_links( $post );

		return array(
			'self'       => $links['self'],
			'collection' => $links['collection'],
		);
	}
}
