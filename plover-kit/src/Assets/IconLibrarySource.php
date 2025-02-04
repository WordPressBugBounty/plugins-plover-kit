<?php

namespace Plover\Kit\Assets;

use Plover\Core\Assets\Contract\IconSource;

/**
 * @since 1.0.0
 */
class IconLibrarySource implements IconSource {

	/**
	 * Icon library post type
	 */
	protected const ICON_LIBRARY_POST_TYPE = 'plover_icon_library';

	/**
	 * Icon post type
	 */
	protected const ICON_POST_TYPE = 'plover_icon';

	/**
	 * @param $slug
	 *
	 * @return array|null
	 */
	public function get_library( $id ) {
		if ( (int) $id <= 0 ) {
			return null;
		}

		$post = get_post( (int) $id );

		if ( empty( $post ) || empty( $post->ID ) || self::ICON_LIBRARY_POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $this->prepare_library_for_response( $post );
	}

	/**
	 * @return array|mixed
	 */
	public function get_libraries() {
		$query = new \WP_Query();
		$posts = $query->query( array(
			'order'                  => 'desc',
			'orderby'                => 'date',
			'post_type'              => self::ICON_LIBRARY_POST_TYPE,
			'posts_per_page'         => - 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		return array_map( [ $this, 'prepare_library_for_response' ], $posts );
	}

	/**
	 * @param $library
	 *
	 * @return array|mixed|null
	 */
	public function get_icons( $library ) {
		if ( ! $this->get_library( $library ) ) {
			return null;
		}

		$query = new \WP_Query();
		$posts = $query->query( array(
			'post_parent'            => (int) $library,
			'post_type'              => self::ICON_POST_TYPE,
			'posts_per_page'         => - 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		return array_map( [ $this, 'prepare_icon_for_response' ], $posts );
	}

	/**
	 * @param $library
	 * @param $slug
	 *
	 * @return mixed|string|null
	 */
	public function get_icon( $library, $id ) {
		if ( (int) $id <= 0 || (int) $library <= 0 ) {
			return null;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) || self::ICON_POST_TYPE !== $post->post_type ) {
			return null;
		}

		if ( $post->post_parent !== (int) $library ) {
			return null;
		}

		$settings = json_decode( $post->post_content, true );

		return isset( $settings['svg'] ) && $settings['svg'] ? $settings['svg'] : '';
	}

	/**
	 * Response format for icon.
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function prepare_icon_for_response( $post ) {
		$settings = json_decode( $post->post_content, true );

		return [
			'id'   => $post->ID,
			'name' => isset( $post->post_title ) && $post->post_title ? $post->post_title : '',
			// Better performance and compatibility with ID as identify.
			'slug' => $post->ID,
			'svg'  => isset( $settings['svg'] ) && $settings['svg'] ? $settings['svg'] : '',
			'tags' => isset( $settings['tags'] ) && $settings['tags'] ? $settings['tags'] : [],
		];
	}

	/**
	 * Response format for icon library.
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function prepare_library_for_response( $post ) {
		return [
			'id'   => $post->ID,
			'name' => isset( $post->post_title ) && $post->post_title ? $post->post_title : '',
			// Better performance and compatibility with ID as identify.
			'slug' => $post->ID,
		];
	}
}
