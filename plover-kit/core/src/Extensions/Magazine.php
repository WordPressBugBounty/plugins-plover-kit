<?php

namespace Plover\Core\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Toolkits\Format;
use Plover\Core\Toolkits\Html\Document;
use Plover\Core\Toolkits\Responsive;
use Plover\Core\Toolkits\Str;
use Plover\Core\Toolkits\StyleEngine;

/**
 * Introduce magazine template
 *
 * @since 1.2.7
 */
class Magazine extends Extension {

	const MODULE_NAME = 'plover_magazine_layout';

	/**
	 * @return void
	 */
	public function register() {
		$fields = array();

		$this->modules->register( self::MODULE_NAME, array(
			'recent'  => true,
			'label'   => __( 'Magazine Layout', 'plover' ),
			'excerpt' => __( "Elevate your WordPress site with the Magazine Layout dynamic content presentation.", 'plover' ),
			'icon'    => esc_url( $this->core->core_url( 'assets/images/magazine-layout.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/magazine-layout/',
			'fields'  => $fields,
			'group'   => 'blog-tools',
		) );
	}

	/**
	 * Boot magazine layout extension
	 *
	 * @return void
	 */
	public function boot() {
		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		// Enqueue responsive magazine style css snippet
		$styles = $this->get_magazine_styles();
		foreach ( $styles as $id => $responsive_styles ) {
			foreach ( $responsive_styles as $device => $style ) {
				$scope = "plover-magazine-{$id}";
				$this->styles->enqueue_asset( "{$scope}-{$device}", array(
					'raw'      => $style,
					'device'   => $device,
					'keywords' => [ $scope ],
				) );
			}
		}

		// Enqueue responsive magazine height css snippet.
		$devices = [ 'desktop', 'tablet', 'mobile' ];
		foreach ( $devices as $device ) {
			$this->styles->enqueue_asset( "plover-magazine-height-{$device}", array(
				'raw'       => ".plover-posts-magazine{height:var(--plover-magazine-height-{$device},520px);}",
				'device'    => $device,
				'condition' => ! is_admin(),
				'keywords'  => [ 'plover-posts-magazine' ],
			) );
		}

		// Extension editor controls and preview
		$this->scripts->enqueue_editor_asset( 'plover-magazine-layout-extension', array(
			'ver'   => 'core',
			'src'   => $this->core->core_url( 'assets/js/block-extensions/magazine-layout/index.min.js' ),
			'path'  => $this->core->core_path( 'assets/js/block-extensions/magazine-layout/index.min.js' ),
			'asset' => $this->core->core_path( 'assets/js/block-extensions/magazine-layout/index.min.asset.php' ),
		) );
		$this->styles->enqueue_editor_asset( 'plover-magazine-layout-extension', array(
			'ver'  => 'core',
			'rtl'  => 'replace',
			'src'  => $this->core->core_url( 'assets/js/block-extensions/magazine-layout/style.min.css' ),
			'path' => $this->core->core_path( 'assets/js/block-extensions/magazine-layout/style.min.css' ),
		) );

		// Frontend styles
		$this->styles->enqueue_asset( 'plover-magazine-layout-style', array(
			'ver'      => 'core',
			'src'      => $this->core->core_url( 'assets/css/magazine-layout/style.css' ),
			'path'     => $this->core->core_path( 'assets/css/magazine-layout/style.css' ),
			'keywords' => [ 'plover-posts-magazine' ],
		) );

		// Modify query block context
		add_filter( 'render_block_context', [ $this, 'override_context' ], 11, 2 );
		// Render magazine layout
		add_filter( 'render_block_core/post-template', [ $this, 'render' ], 11, 2 );
		// Send default magazine attributes to JavaScript
		add_filter( 'plover_core_editor_data', [ $this, 'localize_editor_data' ] );
	}

	/**
	 * Override query block context
	 * 
	 * @param mixed $context
	 * @param mixed $parsed_block
	 */
	public function override_context( $context, $parsed_block ) {
		$blockName = $parsed_block['blockName'] ?? '';
		$blockAttrs = $parsed_block['attrs'] ?? [];

		// We only override the context of magazine layout template query args.
		if ( $blockName === 'core/post-template' && Str::contains_any( $blockAttrs['className'] ?? '', 'is-magazine-design' ) ) {
			if ( isset($context['query']) ) {
				$layout = $this->get_layouts( $blockAttrs['magazineLayout'] ?? 'style-01' );
				if ( $layout && isset( $layout['count'] ) ) {
					// Override perPage query parameter
					$context['query']['perPage'] = $layout['count'];
				}
			}
		}
		return $context;
	}

	/**
	 * @param string $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function render( $block_content, $block ) {
		$attrs = $block['attrs'] ?? [];
		if ( ! Str::contains_any($attrs['className'] ?? '', 'is-magazine-design') ) {
			return $block_content;
		}

		$html = new Document( $block_content );
		$wrap = $html->get_root_element();
		if ( ! $wrap ) {
			return $block_content;
		}

		$classnames = array( 'plover-posts-magazine', 'plover-magazine-' . ( $attrs['magazineLayout'] ?? 'style-01' ) );
		$styles     = array();

		$containerHeight = $attrs[ 'containerHeight' ] ?? '';
		if ( $containerHeight ) {
			$height = Responsive::promote_scalar_value_into_responsive( $containerHeight );
			$styles['--plover-magazine-height-desktop'] = Format::sanitize_unit_value( $height['desktop'] );
			$styles['--plover-magazine-height-tablet'] = Format::sanitize_unit_value( $height['tablet'] );
			$styles['--plover-magazine-height-mobile'] = Format::sanitize_unit_value( $height['mobile'] );
		}

		$gap = StyleEngine::get_block_gap_value( $attrs );
		// add block gap
		if ( isset( $gap ) ) {
			$styles['--magazine-gap'] = $gap;
		}

		$wrap->add_classnames( $classnames );
		$wrap->add_styles( $styles );
		foreach( $wrap->children() as $el ) {
			$el->add_classnames( [ 'plover-magazine-item' ] );
		}
		
		return $html->save_html();
	}

	/**
	 * Localize editor data
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function localize_editor_data( $data ) {
		$data['extensions']['magazineTemplate'] = [
			'layouts' => $this->get_layouts()
		];

		return $data;
	}

	/**
	 * Get all magazine layout css styles
	 * 
	 * @return mixed
	 */
	protected function get_magazine_styles() {
		$styles = array(
			'style-01' => array(
				'desktop' => ".plover-magazine-style-01{grid-template-columns:1fr 1fr 1fr 1fr;grid-template-rows:1fr 1fr;}.plover-magazine-style-01 .plover-magazine-item:nth-of-type(1){grid-column:1 / 3;grid-row:1 / 3;}.plover-magazine-style-01 .plover-magazine-item:nth-of-type(2){grid-column:3 / 5;grid-row:auto;}",
				'tablet'  => ".plover-magazine-style-01{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr 1fr;}.plover-magazine-style-01 .plover-magazine-item:nth-of-type(1){grid-column:1 / 3;grid-row:1;}.plover-magazine-style-01 .plover-magazine-item:nth-of-type(2){grid-column:1 / 3;grid-row:auto;}",
			),
			'style-02' => array(
				'all' => ".plover-magazine-style-02{grid-template-columns:1fr 1fr;grid-template-rows:1fr;}",
			),
			'style-03' => array(
				'desktop' => ".plover-magazine-style-03{grid-template-columns:1fr 1fr 1fr 1fr;grid-template-rows:1fr;}.plover-magazine-style-03 .plover-magazine-item:nth-of-type(1){grid-column: 1 / 2;grid-row: auto;}.plover-magazine-style-03 .plover-magazine-item:nth-of-type(2){grid-column:2 / 4;grid-row: auto;}",
				'tablet'  => ".plover-magazine-style-03{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;}.plover-magazine-style-03 .plover-magazine-item:nth-of-type(1){grid-column: 1 / 3;grid-row: auto;}.plover-magazine-style-03 .plover-magazine-item:nth-of-type(2){grid-column:1 / 2;grid-row: auto;}",
			),
			'style-04' => array(
				'desktop' => ".plover-magazine-style-04{grid-template-columns:1fr 1fr 1fr;grid-template-rows:1fr;}.plover-magazine-style-04 .plover-magazine-item:nth-of-type(1){grid-column:1 / 2;grid-row:auto;}",
				'tablet'  => ".plover-magazine-style-04{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;}.plover-magazine-style-04 .plover-magazine-item:nth-of-type(1){grid-column:1 / 3;grid-row:auto;}",
			),
			'style-05' => array(
				'desktop' => ".plover-magazine-style-05{grid-template-columns:1fr 1fr 1fr 1fr;grid-template-rows:1fr;}",
				'tablet'  => ".plover-magazine-style-05{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;}",
			),
			'style-06' => array(
				'desktop' => ".plover-magazine-style-06{grid-template-columns:1fr 1fr 1fr 1fr;grid-template-rows:1fr;}.plover-magazine-style-06 .plover-magazine-item:nth-of-type(1){grid-column:1 / 3;grid-row: auto;}",
				'tablet'  => ".plover-magazine-style-06{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;}.plover-magazine-style-06 .plover-magazine-item:nth-of-type(1){grid-column:1 / 3;grid-row: auto;}",
			)
		);

		return apply_filters( 'plover_core_magazine_styles', $styles );
	}

	/**
	 * Get all magazine layouts
	 *
	 * @return mixed|null
	 */
	protected function get_layouts( $id = null ) {
		$layouts = array(
			'style-01' => array(
				'premium' => false,
				'count'   => 4,
			),
			'style-02' => array(
				'premium' => false,
				'count'   => 2,
			),
			'style-03' => array(
				'premium' => false,
				'count'   => 3,
			),
			'style-04' => array(
				'premium' => false,
				'count'   => 3,
			),
			'style-05' => array(
				'premium' => false,
				'count'   => 4,
			),
			'style-06' => array(
				'premium' => false,
				'count'   => 3,
			),
			'style-07' => array(
				'premium' => true,
				'count'   => 3,
			),
			'style-08' => array(
				'premium' => true,
				'count'   => 4,
			),
			'style-09' => array(
				'premium' => true,
				'count'   => 3,
			),
			'style-10' => array(
				'premium' => true,
				'count'   => 3,
			),
			'style-11' => array(
				'premium' => true,
				'count'   => 5,
			),
			'style-12' => array(
				'premium' => true,
				'count'   => 5,
			),
			'style-13' => array(
				'premium' => true,
				'count'   => 4,
			),
			'style-14' => array(
				'premium' => true,
				'count'   => 4,
			),
			'style-15' => array(
				'premium' => true,
				'count'   => 5,
			),
			'style-16' => array(
				'premium' => true,
				'count'   => 5,
			),
			'style-17' => array(
				'premium' => true,
				'count'   => 5,
			),
			'style-18' => array(
				'premium' => true,
				'count'   => 5,
			),
		);

		$layouts = apply_filters( 'plover_core_magazine_layouts', $layouts );
		if ( $id !== null ) {
			if ( isset( $layouts[ $id ] ) ) {
				return $layouts[ $id ];
			}

			return null;
		}

		return $layouts;
	}
}
