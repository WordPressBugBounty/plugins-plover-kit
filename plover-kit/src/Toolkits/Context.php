<?php

namespace Plover\Kit\Toolkits;

/**
 * Block/Shortcode context detector
 *
 * @since 1.7.0
 */
class Context {

	/**
	 * Unknown context
	 *
	 * @var string
	 */
	const UNKNOWN = array(
		'type' => 'unknown',
		'id'   => 0,
	);

	/**
	 * Context instance
	 *
	 * @var Context
	 */
	private static $_instance = null;

	/**
	 * Track current template part/reusable blocks in FSE theme
	 *
	 * @var string
	 */
	private $block_stack = array();

	/**
	 * Singleton classes, used to detect the context of the current block/shortcode execution.
	 * Use Context::init() to get the context instance,
	 * Use $instance->detect() to get current context.
	 * Priority: Template Part / Reusable blocks -> Post/Page -> Template
	 */
	private function __construct() {
		// add_filter( 'template_include', array( $this, '_track_block_template' ) );
		add_filter( 'render_block_data', array( $this, '_track_block_template_part' ), 1, 2 );
		add_filter( 'render_block', array( $this, '_pop_block_template_part' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Get context class instance
	 *
	 * @return Context
	 */
	public static function init() {
		if ( self::$_instance === null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get current context data
	 *
	 *  Available context type: wp_template_part, wp_block, post_type, wp_template
	 *
	 * @return string|array unknown-context | {'type': 'context-type', 'id': 'context-id'}
	 */
	public static function context() {
		return self::init()->detect();
	}

	/**
	 * Detect current context data
	 *
	 * Available context type: wp_template_part, wp_block, post_type, wp_template
	 *
	 * @return string|array unknown-context | {'type': 'context-type', 'id': 'context-id'}
	 */
	private function detect() {
		// Template part/reusable block context
		$template_block = $this->get_template_block_context();
		if ( $template_block ) {
			return $template_block;
		}

		// Check queried object
		$content = $this->get_content_context();
		if ( $content ) {
			return $content;
		}

		// Template context
		$template = $this->get_current_template_context();
		if ( $template ) {
			return $template;
		}

		// Unknown context
		return self::UNKNOWN;
	}

	/**
	 * Get template block context
	 *
	 * @return array|null
	 */
	private function get_template_block_context() {
		if ( empty( $this->block_stack ) ) {
			return null;
		}

		$template_block = end( $this->block_stack );
		if ( $template_block['blockName'] === 'core/template-part' ) {
			return $this->get_template_part_context( $template_block['attrs'] ?? [] );
		}
		if ( $template_block['blockName'] === 'core/block' ) {
			return $this->get_reusable_block_context( $template_block['attrs'] ?? [] );
		}

		return apply_filters( 'plover-plover-kit/template_block_context', null, $template_block );
	}

	/**
	 * Fallback context detect
	 *
	 * @return array|string
	 */
	private function get_content_context() {
		if ( current_filter() !== 'the_content' || ! in_the_loop() ) {
			return null;
		}

		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return [
			'type' => $post->post_type,
			'id'   => $post->ID,
		];
	}

	/**
	 * Track the context before the block rendering.
	 *
	 * @param array $parsed_block
	 */
	public function _track_block_template_part( $parsed_block ) {

		if ( empty( $parsed_block['blockName'] ) ) {
			return $parsed_block;
		}

		$template_blocks = apply_filters( 'plover-kit/template_blocks', array(
			'core/template-part',
			'core/block'
		) );

		if ( in_array( $parsed_block['blockName'], $template_blocks ) ) {
			$this->block_stack[] = $parsed_block;
		}

		return $parsed_block;
	}

	/**
	 * Pop the context after the block has finished rendering.
	 *
	 * @param string $block_content
	 * @param array $block
	 */
	public function _pop_block_template_part( $block_content, $block ) {

		if ( empty( $block['blockName'] ) || empty( $this->block_stack ) ) {
			return $block_content;
		}

		$top = end( $this->block_stack );

		if ( $block['blockName'] == $top['blockName'] ) {
			array_pop( $this->block_stack );
		}

		return $block_content;
	}

	/**
	 * Get FSE template context
	 *
	 * @return array|null
	 */
	private function get_current_template_context() {
		/** $_wp_current_template_id is a WordPress core global variable used to cache the current template ID. */
		global $_wp_current_template_id;
		if ( ! empty( $_wp_current_template_id ) && is_string( $_wp_current_template_id ) ) {
			$parts = explode( '//', $_wp_current_template_id, 2 );

			if ( count( $parts ) === 2 ) {
				$template = get_block_template( $_wp_current_template_id, 'wp_template' );
				if ( $template && ! empty( $template->wp_id ) ) {
					return [
						'type' => 'wp_template',
						'id'   => $template->wp_id,
						'data' => [ 'slug' => $template->slug ],
					];
				}
			}
		}

		return null;
	}

	/**
	 * Get template part context information from block attributes
	 *
	 * @param array $attrs
	 *
	 * @return array
	 */
	private function get_template_part_context( $attrs ) {
		$slug  = $attrs['slug'] ?? '';
		$theme = $attrs['theme'] ?? get_stylesheet();

		$id = 0;
		if ( $slug ) {
			// Try to get template-part id
			$template = get_block_template( $theme . '//' . $slug, 'wp_template_part' );
			if ( $template && ! empty( $template->wp_id ) ) {
				$id = $template->wp_id;
			}
		}

		if ( $id === 0 ) {
			return null;
		}

		return array(
			'type' => 'wp_template_part',
			'id'   => $id,
		);
	}

	/**
	 * Get reusable block context information form block attributes
	 *
	 * @param mixed $attrs
	 *
	 * @return array|string
	 */
	private function get_reusable_block_context( $attrs ) {
		$ref = $attrs['ref'] ?? 0;

		if ( ! $ref ) {
			// Something wrong.
			return null;
		}

		return array(
			'type' => 'wp_block',
			'id'   => $ref,
		);
	}
}
