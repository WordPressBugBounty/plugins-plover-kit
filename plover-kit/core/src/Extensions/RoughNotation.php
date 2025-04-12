<?php

namespace Plover\Core\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;

/**
 * @since 1.2.3
 */
class RoughNotation extends Extension {

	const MODULE_NAME = 'plover_rough_notation';

	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'recent'  => true,
			'label'   => __( 'Rough Notation', 'plover' ),
			'excerpt' => __( 'Create and animate hand-draw style notations on a page.', 'plover' ),
			'icon'    => esc_url( $this->core->core_url( 'assets/images/rough-notation.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/rough-notation/',
			'fields'  => array(),
			'group'   => 'motion-effects',
		) );
	}

	/**
	 * Boot rough notation
	 *
	 * @return void
	 */
	public function boot() {
		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		// Frontend scripts
		$this->scripts->enqueue_asset( 'plover-rough-notation-script', array(
			'src'       => $this->core->core_url( 'assets/js/frontend/rough-notation/index.min.js' ),
			'path'      => $this->core->core_path( 'assets/js/frontend/rough-notation/index.min.js' ),
			'asset'     => $this->core->core_path( 'assets/js/frontend/rough-notation/index.min.asset.php' ),
			'keywords'  => [ 'has-plover-rough-notation' ],
			'condition' => ! is_admin()
		) );

		// Enqueue rough notation extension assets
		$this->scripts->enqueue_editor_asset( 'plover-rough-notation-extension', array(
			'src'   => $this->core->core_url( 'assets/js/block-extensions/rough-notation/index.min.js' ),
			'path'  => $this->core->core_path( 'assets/js/block-extensions/rough-notation/index.min.js' ),
			'asset' => $this->core->core_path( 'assets/js/block-extensions/rough-notation/index.min.asset.php' ),
		) );
		$this->styles->enqueue_editor_asset( 'plover-rough-notation-editor-styles', array(
			'rtl'  => 'replace',
			'src'  => $this->core->core_url( 'assets/js/block-extensions/rough-notation/style.min.css' ),
			'path' => $this->core->core_path( 'assets/js/block-extensions/rough-notation/style.min.css' ),
		) );
	}
}