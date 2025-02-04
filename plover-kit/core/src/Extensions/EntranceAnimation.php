<?php

namespace Plover\Core\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Toolkits\Arr;
use Plover\Core\Toolkits\Filesystem;
use Plover\Core\Toolkits\Html\Document;

/**
 * @since 1.2.0
 */
class EntranceAnimation extends Extension {

	const MODULE_NAME = 'plover_entrance_animation';

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'recent'  => true,
			'label'   => __( 'Entrance Animation', 'plover' ),
			'excerpt' => __( 'Entrance animation adding dynamic movement to elements as they appear on screen.', 'plover' ),
			'icon'    => esc_url( $this->core->core_url( 'assets/images/entrance-animation.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/entrance-animation/',
			'fields'  => array(),
			'group'   => 'motion-effects',
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		// editor controls and editor preview
		$this->scripts->enqueue_editor_asset( 'plover-entrance-animation', array(
			'ver'   => 'core',
			'src'   => $this->core->core_url( 'assets/js/block-supports/entrance-animation/index.min.js' ),
			'path'  => $this->core->core_path( 'assets/js/block-supports/entrance-animation/index.min.js' ),
			'asset' => $this->core->core_path( 'assets/js/block-supports/entrance-animation/index.min.asset.php' )
		) );

		// frontend script
		$this->scripts->enqueue_asset( 'plover-entrance-animation', apply_filters( 'plover_entrance_animation_script', array(
			'ver'      => 'core',
			'src'      => $this->core->core_url( 'assets/js/frontend/entrance-animation/index.min.js' ),
			'path'     => $this->core->core_path( 'assets/js/frontend/entrance-animation/index.min.js' ),
			'asset'    => $this->core->core_path( 'assets/js/frontend/entrance-animation/index.min.asset.php' ),
			'keywords' => [ 'plover-entrance-animated' ]
		) ) );

		// global animation css
		$this->styles->enqueue_asset( 'plover-animation', array(
			'ver'      => 'core',
			'src'      => $this->core->core_url( "assets/css/animations/animate.css" ),
			'path'     => $this->core->core_path( "assets/css/animations/animate.css" ),
			'keywords' => [ 'plover-entrance-animated', 'plover-animated', 'plover-invisible' ],
		) );

		$animations = $this->get_entrance_animations();
		foreach ( $animations as $animation ) {
			$this->styles->enqueue_asset( "plover-animation-{$animation['name']}", array(
				'ver'      => 'core',
				'src'      => $animation['src'],
				'path'     => $animation['path'],
				'keywords' => [ $animation['name'] ],
			) );
		}

		// Render frontend entrance animation attributes
		add_filter( 'render_block', [ $this, 'render' ], 11, 2 );
		// Send animation presets to JavaScript
		add_filter( 'plover_core_editor_data', [ $this, 'localize_entrance_animations' ] );
	}

	/**
	 * @return array
	 */
	public function localize_entrance_animations( $data ) {
		$animations = Arr::pluck( $this->get_entrance_animations(), 'name' );

		$data['customBlockSupports']['entranceAnimation'] = [
			'attributes' => apply_filters( 'plover_core_entrance_animation_attributes', [
				'entranceAnimation' => [
					'type' => 'string',
				],
			] ),
			'animations' => $animations,
		];

		return $data;
	}

	/**
	 * @param string $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function render( $block_content, $block ) {
		if ( is_admin() ) { // don't load in the editor
			return $block_content;
		}

		$attrs     = $block['attrs'] ?? [];
		$animation = $attrs['entranceAnimation'] ?? '';
		if ( ! $animation ) {
			return $block_content;
		}

		$html = new Document( $block_content );
		$wrap = $html->get_root_element();
		if ( ! $wrap ) {
			return $block_content;
		}

		$wrap_classes = [ 'plover-entrance-animated' ];
		if ( str_contains( $animation, 'In' ) ) {
			$wrap_classes[] = 'plover-invisible';
		}

		$wrap->add_classnames( $wrap_classes );
		$wrap->set_attribute( 'data-entrance-animation', esc_attr( $animation ) );

		apply_filters( 'plover_core_render_entrance_animation', $wrap, $block, $attrs );

		return $html->save_html();
	}

	/**
	 * Get all entrance animations
	 *
	 * @return array
	 */
	protected function get_entrance_animations() {
		$animation_files = Filesystem::list_files( $this->core->core_path( 'assets/css/animations/styles' ) );
		$animations      = array_map( function ( $filename ) {
			$animation_name = basename( $filename, '.css' );

			return [
				'name' => $animation_name,
				'src'  => $this->core->core_url( "assets/css/animations/styles/{$animation_name}.css" ),
				'path' => $this->core->core_path( "assets/css/animations/styles/{$animation_name}.css" ),
			];
		}, $animation_files );

		$animations = apply_filters( 'plover_core_entrance_animations', $animations );

		usort( $animations, function ( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );

		return $animations;
	}
}