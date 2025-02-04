<?php

namespace Plover\Core\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Toolkits\Arr;
use Plover\Core\Toolkits\Filesystem;

/**
 * @since 1.2.0
 */
class HoverAnimation extends Extension {

	const MODULE_NAME = 'plover_hover_animation';

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'recent'  => true,
			'label'   => __( 'Hover Animation', 'plover' ),
			'excerpt' => __( 'The Hover Animation allow you to add mouse hover animation effect for elements on your WordPress website.', 'plover' ),
			'icon'    => esc_url( $this->core->core_url( 'assets/images/hover-animation.gif' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/hover-animation/',
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
		$this->scripts->enqueue_editor_asset( 'plover-hover-animation', array(
			'ver'   => 'core',
			'src'   => $this->core->core_url( 'assets/js/block-supports/hover-animation/index.min.js' ),
			'path'  => $this->core->core_path( 'assets/js/block-supports/hover-animation/index.min.js' ),
			'asset' => $this->core->core_path( 'assets/js/block-supports/hover-animation/index.min.asset.php' )
		) );

		// animation css
		$animations = $this->get_hover_animations();
		foreach ( $animations as $animation ) {
			$this->styles->enqueue_asset( "plover-hvr-{$animation['name']}", array(
				'ver'      => 'core',
				'src'      => $animation['src'],
				'path'     => $animation['path'],
				'keywords' => [ "plover-hvr-{$animation['name']}" ],
			) );
		}

		// Send animation presets to JavaScript
		add_filter( 'plover_core_editor_data', [ $this, 'localize_hover_animations' ] );
	}

	/**
	 * Send animation presets to JavaScript
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function localize_hover_animations( $data ) {
		$animations = Arr::pluck( $this->get_hover_animations(), 'name' );

		$data['customBlockSupports']['hoverAnimation'] = [
			'prefix'     => 'plover-hvr',
			'animations' => $animations,
		];

		return $data;
	}

	/**
	 * Get all hover animations
	 *
	 * @return array
	 */
	protected function get_hover_animations() {
		$animation_files = Filesystem::list_files( $this->core->core_path( 'assets/css/hover-animation' ) );
		$animations      = array_map( function ( $filename ) {
			$animation_name = basename( $filename, '.css' );

			return [
				'name' => $animation_name,
				'src'  => $this->core->core_url( "assets/css/hover-animation/{$animation_name}.css" ),
				'path' => $this->core->core_path( "assets/css/hover-animation/{$animation_name}.css" ),
			];
		}, $animation_files );

		$animations = apply_filters( 'plover_core_hover_animations', $animations );

		usort( $animations, function ( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );

		return $animations;
	}
}