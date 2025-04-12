<?php
/**
 * Plugin helper functions.
 *
 * @since 1.0.0
 */

if ( ! function_exists( 'plover_kit' ) ) {
	/**
	 * Get plover kit application instance.
	 *
	 * @return \Plover\Core\Application|null
	 */
	function plover_kit() {
		return plover_app( PLOVER_KIT_SLUG );
	}
}

if ( ! function_exists( 'plover_kit_get_auth_data' ) ) {
	/**
	 * Get license auth data.
	 *
	 * @return array
	 */
	function plover_kit_get_auth_data() {
		if ( ! function_exists( 'plover_fs' ) ) {
			return [];
		}

		$site    = plover_fs()->get_site();
		$license = plover_fs()->_get_license();

		if ( ! $site || ! $license ) {
			return [];
		}

		$license_id       = $license->id;
		$install_id       = $site->id;
		$site_private_key = $site->secret_key;

		$nonce       = current_time( 'timestamp', true );
		$pk_hash     = hash( 'sha512', $site_private_key . '|' . $nonce );
		$auth_string = base64_encode( $pk_hash . '|' . $nonce );

		return [ $license_id, $install_id, $auth_string ];
	}
}

if ( ! function_exists( 'plover_kit_is_debug' ) ) {
	/**
	 * Is debug mode or not
	 *
	 * @return bool
	 */
	function plover_kit_is_debug() {
		return defined( 'PLOVER_KIT_DEBUG' ) ? PLOVER_KIT_DEBUG : false;
	}
}

if ( ! function_exists( 'plover_kit_compress_algorithm' ) ) {
	/**
	 * Get the compression algorithm
	 *
	 * @return false|string
	 */
	function plover_kit_compress_algorithm() {
		if ( extension_loaded( 'zlib' ) ) {
			if ( function_exists( 'gzdeflate' ) ) {
				return 'deflate';
			}
			if ( function_exists( 'gzcompress' ) ) {
				return 'zlib';
			}
			if ( function_exists( 'gzencode' ) ) {
				return 'gzip';
			}
		}

		if ( extension_loaded( 'bz2' ) ) {
			return 'bzip2';
		}

		return null;
	}
}

if ( ! function_exists( 'plover_kit_uncompress' ) ) {
	/**
	 * Compress string.
	 *
	 * @param $data
	 * @param $algorithm
	 *
	 * @return false|int|mixed|string
	 */
	function plover_kit_uncompress( $data, $algorithm = 'raw' ) {
		switch ( $algorithm ) {
			case 'deflate':
				if ( extension_loaded( 'zlib' ) && function_exists( 'gzdeflate' ) ) {
					return gzinflate( $data );
				} else {
					return false;
				}
			case 'zlib':
				if ( extension_loaded( 'zlib' ) && function_exists( 'gzcompress' ) ) {
					return gzuncompress( $data );
				} else {
					return false;
				}
			case 'gzip':
				if ( extension_loaded( 'zlib' ) && function_exists( 'gzencode' ) ) {
					return gzdecode( $data );
				} else {
					return false;
				}
			case 'bzip2':
				if ( extension_loaded( 'bz2' ) && function_exists( 'bzcompress' ) ) {
					return bzdecompress( $data );
				} else {
					return false;
				}
		}

		return $data;
	}
}

if ( ! function_exists( 'plover_kit_is_image_url' ) ) {
	/**
	 * Check if the URL points to an image.
	 *
	 * @param string $url Valid URL.
	 *
	 * @return false|int
	 */
	function plover_kit_is_image_url( $url ) {
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z\d-].?)+(:\d+)?\/[\w\-\.]+\.(jpg|png|gif|jpeg|webp)\/?$/i', $url );
	}
}

if ( ! function_exists( 'plover_kit_is_local_image' ) ) {
	/**
	 * Check if the image exists in the system.
	 *
	 * @param $data
	 *
	 * @return array
	 */
	function plover_kit_is_local_image( $data ) {
		global $wpdb;

		$image_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `post_id` FROM `' . $wpdb->postmeta . '`
					WHERE `meta_key` = \'_plover-kit_image_hash\'
						AND `meta_value` = %s
				;',
				sha1( $data['url'] )
			)
		);

		if ( $image_id ) {
			$local_image = array(
				'id'  => $image_id,
				'url' => wp_get_attachment_url( $image_id ),
			);

			return array(
				'status' => true,
				'image'  => $local_image,
			);
		}

		return array(
			'status' => false,
			'image'  => $data,
		);
	}
}

if ( ! function_exists( 'plover_kit_do_image_import' ) ) {
	/**
	 * Import an external image
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	function plover_kit_do_image_import( $data ) {
		$local_image = plover_kit_is_local_image( $data );

		if ( $local_image['status'] ) {
			return $local_image['image'];
		}

		$file_content = wp_remote_retrieve_body(
			wp_safe_remote_get(
				$data['url'],
				array(
					'timeout'   => '60',
					'sslverify' => false,
				)
			)
		);

		if ( empty( $file_content ) ) {
			return $data;
		}

		$filename = basename( $data['url'] );

		$upload = wp_upload_bits( $filename, null, $file_content );
		$post   = array(
			'post_title' => $filename,
			'guid'       => $upload['url'],
		);
		$info   = wp_check_filetype( $upload['file'] );

		if ( $info ) {
			$post['post_mime_type'] = $info['type'];
		} else {
			return $data;
		}

		$post_id = wp_insert_attachment( $post, $upload['file'] );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$post_id,
			wp_generate_attachment_metadata( $post_id, $upload['file'] )
		);

		update_post_meta( $post_id, '_plover-kit_image_hash', sha1( $data['url'] ) );

		return array(
			'id'  => $post_id,
			'url' => $upload['url'],
		);
	}
}

if ( ! function_exists( 'plover_kit_process_import_content_urls' ) ) {
	/**
	 * Process urls in import content
	 *
	 * @param string $content
	 *
	 * @return array|mixed|string|string[]
	 */
	function plover_kit_process_import_content_urls( $content = '' ) {
		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $match );

		$urls = array_unique( $match[0] );

		if ( empty( $urls ) ) {
			return $content;
		}

		$map_urls   = array();
		$image_urls = array();

		foreach ( $urls as $url ) {
			if ( plover_kit_is_image_url( $url ) ) {
				$image_urls[] = $url;
			}
		}

		if ( ! empty( $image_urls ) ) {
			foreach ( $image_urls as $image_url ) {
				$image                  = array(
					'url' => $image_url,
					'id'  => 0,
				);
				$downloaded_image       = plover_kit_do_image_import( $image );
				$map_urls[ $image_url ] = $downloaded_image['url'];
			}
		}

		foreach ( $map_urls as $old_url => $new_url ) {
			$content = str_replace( $old_url, $new_url, $content );
			$old_url = str_replace( '/', '/\\', $old_url );
			$new_url = str_replace( '/', '/\\', $new_url );
			$content = str_replace( $old_url, $new_url, $content );
		}

		return $content;
	}
}

if ( ! function_exists( 'plover_kit_get_import_icons_library_id' ) ) {
	/**
	 * @return int|WP_Error
	 */
	function plover_kit_get_import_icons_library_id() {
		// Check that if the icon library for importing is exists or not.
		$query = new \WP_Query(
			array(
				'post_type'              => 'plover_icon_library',
				'posts_per_page'         => 1,
				'name'                   => 'plover-kit-pattern-library',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $query->posts ) ) {
			return $query->post->ID;
		}

		// Create if not exists
		$prepared_post = array(
			'post_type'    => 'plover_icon_library',
			'post_title'   => 'Pattern Library',
			'post_status'  => 'publish',
			'post_name'    => 'plover-kit-pattern-library',
			'post_content' => wp_json_encode( [] ),
		);

		return wp_insert_post( wp_slash( $prepared_post ), true, false );
	}
}

if ( ! function_exists( 'plover_kit_do_icon_import' ) ) {
	/**
	 * @param $svgString
	 *
	 * @return array|int|WP_Error
	 */
	function plover_kit_do_icon_import( $svgString ) {
		$library_id = plover_kit_get_import_icons_library_id();
		if ( is_wp_error( $library_id ) ) {
			return $library_id;
		}

		$hash = sha1( $svgString );

		// Check that if the icon library for importing is exists or not.
		$query = new \WP_Query(
			array(
				'post_type'              => 'plover_icon',
				'posts_per_page'         => 1,
				'name'                   => $hash,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( $query->post ) {
			return array(
				'iconSlug'    => $query->post->ID,
				'iconLibrary' => $library_id,
			);
		}

		// Create if not exists
		$prepared_post = array(
			'post_type'    => 'plover_icon',
			'post_parent'  => $library_id,
			'post_title'   => $hash,
			'post_status'  => 'publish',
			'post_name'    => $hash,
			'post_content' => wp_json_encode( array(
				'tags' => [],
				'svg'  => $svgString,
			) ),
		);

		$icon_id = wp_insert_post( wp_slash( $prepared_post ), true, false );
		if ( is_wp_error( $icon_id ) ) {
			return $icon_id;
		}

		return array(
			'iconSlug'    => $icon_id,
			'iconLibrary' => $library_id,
		);
	}
}

if ( ! function_exists( 'plover_kit_import_icons_from_blocks' ) ) {
	/**
	 * Import icons from parsed blocks
	 *
	 * @param $blocks
	 *
	 * @return mixed
	 */
	function plover_kit_import_icons_from_blocks( $blocks ) {
		$new_blocks = array();

		foreach ( $blocks as $block ) {
			if ( isset( $block['attrs']['iconSvgString'] )
			     && $block['attrs']['iconLibrary'] !== 'plover-core' // Don't import core icons
			) {
				$result = plover_kit_do_icon_import( $block['attrs']['iconSvgString'] );
				if ( ! is_wp_error( $result ) ) { // ignore errors
					$block['attrs']['iconLibrary'] = (string) $result['iconLibrary'];
					$block['attrs']['iconSlug']    = (string) $result['iconSlug'];
				}
			}

			if ( isset( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = plover_kit_import_icons_from_blocks( $block['innerBlocks'] );
			}

			$new_blocks[] = $block;
		}

		return $new_blocks;
	}
}

if ( ! function_exists( 'plover_kit_process_import_icons' ) ) {
	/**
	 * Process icons in import content
	 *
	 * @param $content
	 *
	 * @return mixed|string
	 */
	function plover_kit_process_import_icons( $content = '' ) {
		return serialize_blocks(
			plover_kit_import_icons_from_blocks(
				parse_blocks( $content )
			)
		);
	}
}

if ( ! function_exists( 'plover_kit_install_plugin' ) ) {
	/**
	 * Install plugin
	 *
	 * @param string $slug
	 * @param string $plugin_file
	 *
	 * @return mixed
	 */
	function plover_kit_install_plugin( $slug, $plugin_file ) {
		$slug        = sanitize_key( wp_unslash( $slug ) );
		$plugin_file = plugin_basename( sanitize_text_field( wp_unslash( $plugin_file ) ) );

		$status = array(
			'install' => 'plugin',
			'slug'    => $slug,
		);

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'plover-kit' );

			return $status;
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		// Looks like a plugin is installed
		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {

			$plugin_data             = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
			$status['plugin']        = $plugin_file;
			$status['pluginVersion'] = $plugin_data['Version'];
			$status['pluginName']    = $plugin_data['Name'];

			// plugin is inactive.
			if ( current_user_can( 'activate_plugin', $plugin_file ) && is_plugin_inactive( $plugin_file ) ) {
				$result = activate_plugin( $plugin_file );

				if ( is_wp_error( $result ) ) {
					$status['errorCode']    = $result->get_error_code();
					$status['errorMessage'] = $result->get_error_message();

					return $status;
				}

				return $status;
			}
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$status['errorMessage'] = $api->get_error_message();

			return $status;
		}

		if ( isset( $status['pluginVersion'] ) && version_compare( $status['pluginVersion'], $api->version, '>=' ) ) {
			return $status;
		}

		$status['pluginName'] = $api->name;

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link, array(
			'overwrite_package' => true,
		) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();

			return $status;
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();

			return $status;
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();

			return $status;
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'plover-kit' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return $status;
		}

		$install_status = install_plugin_install_status( $api );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			$result = activate_plugin( $install_status['file'] );

			if ( is_wp_error( $result ) ) {
				$status['errorCode']    = $result->get_error_code();
				$status['errorMessage'] = $result->get_error_message();

				return $status;
			}
		}

		return $status;
	}
}
