<?php

namespace Plover\Core\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\Format;
use Plover\Core\Toolkits\Html\Document;
use Plover\Core\Toolkits\Responsive;

/**
 * Responsive layout tools
 *
 * @since 1.3.0
 */
class AdvancedLayout extends Extension {

	const MODULE_NAME = 'plover_advanced_layout';

	/**
	 * @return void
	 */
	public function register() {
		$this->modules->register( self::MODULE_NAME, array(
			'order'   => 100,
			'group'   => 'extensions',
			'label'   => __( 'Advanced Layout', 'plover' ),
			'excerpt' => __( 'Add responsive flexbox, text alignment, display, sizing, and positioning controls to core blocks. Create flexible, responsive layouts with ease.', 'plover' ),
			'icon'    => esc_url( $this->core->core_url( 'assets/images/advanced-layout.png' ) ),
			'doc'     => 'https://wpplover.com/docs/plover-kit/modules/advanced-layout/',
			'recent'  => true,
			'fields'  => array(
				'display'           => array(
					'label'   => __( 'Display', 'plover' ),
					'help'    => __( 'You can set responsive display, visibility, overflow and box-sizing css properties for all blocks.', 'plover' ),
					'default' => 'yes',
					'control' => Control::T_SWITCH,
					'upsell'  => sprintf(
						_x(
							'Upgrade to the %s edition to access visibility, overflow and box-sizing controls.',
							'%s is upsell text and link',
							'plover'
						),
						'<a class="accent" target="_blank" href="' . plover_upsell_url( [ 'utm_campaign' => 'css_display' ] ) . '">' . __( 'Premium', 'plover' ) . '</a>'
					),
				),
				'flex_blocks'       => array(
					'label'   => __( 'Flex Layout', 'plover' ),
					'help'    => __( 'Add responsive flex layout options to the selected block.', 'plover' ),
					'default' => apply_filters( 'plover_core_flex_layout_supported_blocks', array(
						'core/group'        => array(),
						'core/buttons'      => array(),
						'core/columns'      => array(),
						'core/social-links' => array(),
						'core/navigation' 	=> array(),
					) ),
					'control' => Control::T_BLOCK_SELECTOR,
				),
				'typography_blocks' => array(
					'label'        => __( 'Typography', 'plover' ),
					'help'         => __( 'Add responsive text alignment options to the selected block.', 'plover' ),
					'default'      => apply_filters( 'plover_core_typography_supported_blocks', array(
						'core/paragraph'    => array(
							'alignment' => 'yes',
						),
						'core/heading'      => array(
							'alignment' => 'yes',
						),
						'core/post-title'   => array(
							'alignment' => 'yes',
						),
						'core/post-excerpt' => array(
							'alignment' => 'yes',
						)
					) ),
					'control'      => Control::T_BLOCK_SELECTOR,
					'control_args' => array(
						'fields' => array(
							'alignment' => array(
								'label'   => __( 'Text Alignment', 'plover' ),
								'default' => 'yes',
								'control' => Control::T_SWITCH
							)
						)
					)
				),
				'position_blocks'   => array(
					'label'   => __( 'Position', 'plover' ),
					'help'    => __( 'You can set position, order and z-index css properties for blocks, responsive!', 'plover' ),
					'default' => apply_filters( 'plover_core_position_supported_blocks', array(
						'core/group'   		=> array(),
						'core/column'  		=> array(),
						'core/columns' 		=> array(),
						'core/cover'   		=> array(),
						'core/image'   		=> array(),
					) ),
					'control' => Control::T_BLOCK_SELECTOR,
				),
				'sizing_blocks'     => array(
					'premium' => true,
					'label'   => __( 'Sizing', 'plover' ),
					'help'    => __( 'Add responsive height, width, [min/max]-height and [min/max]-width css properties to selected blocks.', 'plover' ),
					'default' => apply_filters( 'plover_core_sizing_supported_blocks', array(
						'core/group' => array(),
						'core/cover' => array(),
					) ),
					'upsell'  => sprintf(
						_x(
							'The sizing block options is available only in the %s edition.',
							'%s is upsell text and link',
							'plover'
						),
						'<a class="accent" target="_blank" href="' . plover_upsell_url( [ 'utm_campaign' => 'sizing_blocks' ] ) . '">' . __( 'Premium', 'plover' ) . '</a>'
					),
					'control' => Control::T_BLOCK_SELECTOR,
				),
			),
		) );
	}

	public function boot( Blocks $blocks ) {
		// module is disabled.
		if ( ! $this->settings->checked( self::MODULE_NAME ) ) {
			return;
		}

		$flex_blocks = $this->settings->get( self::MODULE_NAME, 'flex_blocks' );
		foreach ( $flex_blocks as $block => $args ) {
			$blocks->extend_block_supports( $block, array(
				'ploverFlexLayout' => $args,
			) );
		}

		$typography_blocks = $this->settings->get( self::MODULE_NAME, 'typography_blocks' );
		foreach ( $typography_blocks as $block => $args ) {
			$blocks->extend_block_supports( $block, array(
				'ploverTypography' => $args,
			) );
		}

		$position_blocks = $this->settings->get( self::MODULE_NAME, 'position_blocks' );
		foreach ( $position_blocks as $block => $args ) {
			$blocks->extend_block_supports( $block, array(
				'ploverPosition' => $args,
			) );
		}

		$sizing_blocks = $this->settings->get( AdvancedLayout::MODULE_NAME, 'sizing_blocks' );
		foreach ( $sizing_blocks as $block => $args ) {
			$blocks->extend_block_supports( $block, array(
				'ploverSizing' => $args,
			) );
		}

		$this->scripts->enqueue_editor_asset( 'plover-advanced-layout', array(
			'ver'   => 'core',
			'src'   => $this->core->core_url( 'assets/js/block-supports/advanced-layout/index.min.js' ),
			'path'  => $this->core->core_path( 'assets/js/block-supports/advanced-layout/index.min.js' ),
			'asset' => $this->core->core_path( 'assets/js/block-supports/advanced-layout/index.min.asset.php' )
		) );

		$this->styles->enqueue_editor_asset( 'plover-advanced-layout', array(
			'ver'  => 'core',
			'rtl'  => 'replace',
			'src'  => $this->core->core_url( 'assets/js/block-supports/advanced-layout/style.min.css' ),
			'path' => $this->core->core_path( 'assets/js/block-supports/advanced-layout/style.min.css' )
		) );

		foreach ( Responsive::DEVICES as $device ) {
			// Enqueue responsive display css
			if ( $this->settings->checked( self::MODULE_NAME, 'display' ) ) {
				$allowed_display_values = $this->get_allowed_display_values();
				foreach ( $allowed_display_values as $display_value ) {
					$this->styles->enqueue_asset( "plover-is-display-{$display_value}-{$device}", array(
						'raw'      => "body .is-display-{$display_value}-{$device}{display:{$display_value};}",
						'device'   => $device,
						'keywords' => [ "is-display-{$display_value}-{$device}" ],
					) );
				}
			}
			// Enqueue responsive layout css
			if ( ! empty( $flex_blocks ) ) {
				foreach ( [ 'row', 'column', 'row-reverse', 'column-reverse' ] as $direction ) {
					$this->styles->enqueue_asset( "plover-is-flex-{$direction}-{$device}", array(
						'raw'      => ".is-flex-{$direction}-{$device}{display:flex;flex-direction:{$direction};}",
						'device'   => $device,
						'keywords' => [ "is-flex-{$direction}-{$device}" ],
					) );
				}

				foreach ( [ 'flex-start', 'center', 'flex-end', 'space-between' ] as $align ) {
					$this->styles->enqueue_asset( "plover-is-justify-{$align}-{$device}", array(
						'raw'      => ".is-justify-{$align}-{$device}{justify-content:{$align};}",
						'device'   => $device,
						'keywords' => [ "is-justify-{$align}-{$device}" ],
					) );
				}

				foreach ( [ 'flex-start', 'center', 'flex-end', 'stretch' ] as $align ) {
					$this->styles->enqueue_asset( "plover-is-align-{$align}-{$device}", array(
						'raw'      => ".is-align-{$align}-{$device}{align-items:{$align};}",
						'device'   => $device,
						'keywords' => [ "is-align-{$align}-{$device}" ],
					) );
				}
			}
			// Enqueue responsive text align css
			if ( ! empty( $typography_blocks ) ) {
				foreach ( [ 'left', 'center', 'right', 'justify' ] as $align ) {
					$this->styles->enqueue_asset( "plover-has-text-align-{$align}-{$device}", array(
						'raw'      => ".has-text-align-{$align}-{$device}{text-align:{$align};}",
						'device'   => $device,
						'keywords' => [ "has-text-align-{$align}-{$device}" ],
					) );
				}
			}
			// Enqueue responsive position css
			if ( ! empty( $position_blocks ) ) {
				$allowed_position_values = $this->get_allowed_position_values();
				foreach ( $allowed_position_values as $position_value ) {
					$this->styles->enqueue_asset( "plover-is-position-{$position_value}-{$device}", array(
						'raw'      => "body .is-position-{$position_value}-{$device}{position:{$position_value};}",
						'device'   => $device,
						'keywords' => [ "is-position-{$position_value}-{$device}" ],
					) );
				}
				foreach ( [ 'top', 'right', 'bottom', 'left' ] as $direction ) {
					$this->styles->enqueue_asset( "plover-is-position-{$direction}-{$device}", array(
						'raw'      => ".has-position-{$direction}-{$device}{{$direction}:var(--position-{$direction}-{$device});}",
						'device'   => $device,
						'keywords' => [ "has-position-{$direction}-{$device}" ],
					) );
				}
				// Enqueue responsive z-index css
				$this->styles->enqueue_asset( "plover-has-z-{$device}", array(
					'raw'      => ".has-z-{$device}{z-index:var(--z-{$device});}",
					'device'   => $device,
					'keywords' => [ "has-z-{$device}" ],
				) );
				// Enqueue responsive order css
				$this->styles->enqueue_asset( "plover-has-order-{$device}", array(
					'raw'      => ".has-order-{$device}{order:var(--order-{$device});}",
					'device'   => $device,
					'keywords' => [ "has-order-{$device}" ],
				) );
			}
		}

		add_filter( 'render_block', [ $this, 'render' ], 11, 2 );
	}

	/**
	 * Render block advanced layout style & classes.
	 *
	 * @param $block_content
	 * @param $block
	 *
	 * @return mixed
	 */
	public function render( $block_content, $block ) {
		$block_name = $block['blockName'] ?? '';
		$attrs      = $block['attrs'] ?? [];

		$classnames = array();
		$styles     = array();

		// Advanced flex layout
		$flex_blocks = $this->settings->get( self::MODULE_NAME, 'flex_blocks' );
		if ( isset( $flex_blocks[ $block_name ] ) ) {
			$orientation = array(
				'desktop' => $attrs['layout']['orientation'] ?? '',
				'tablet'  => $attrs['layout']['orientation'] ?? '',
				'mobile'  => $attrs['layout']['orientation'] ?? '',
			);

			$css_flex_direction = $attrs['cssFlexDirection'] ?? '';
			$justify_content    = $attrs['cssJustifyContent'] ?? '';
			$align_items        = $attrs['cssAlignItems'] ?? '';

			if ( $css_flex_direction ) {
				Responsive::value( $css_flex_direction, function ( $value, $device ) use ( &$classnames, &$orientation ) {
					$value = $this->sanitize_flex_direction_value( $value );
					if ( $value ) {
						$classnames[]           = "is-flex-{$value}-{$device}";
						$orientation[ $device ] =
							( $value === 'column' || $value === 'column-reverse' )
								? 'vertical'
								: 'horizontal';
					}

					return $value;
				} );
			}

			if ( $justify_content ) {
				Responsive::value( $justify_content, function ( $value, $device ) use ( &$classnames, &$orientation ) {
					$value = $this->sanitize_justification_value( $value, $orientation[ $device ] );
					if ( $value ) {
						if ( $orientation[ $device ] === 'vertical' ) { // flip justify-content and align-items
							$classnames[] = "is-align-{$value}-{$device}";
						} else {
							$classnames[] = "is-justify-{$value}-{$device}";
						}
					}
				} );
			}

			if ( $align_items ) {
				Responsive::value( $align_items, function ( $value, $device ) use ( &$classnames, &$orientation ) {
					$value = $this->sanitize_justification_value( $value, $orientation[ $device ] );
					if ( $value ) {
						if ( $orientation[ $device ] === 'vertical' ) { // flip justify-content and align-items
							$classnames[] = "is-justify-{$value}-{$device}";
						} else {
							$classnames[] = "is-align-{$value}-{$device}";
						}
					}
				} );
			}
		}

		// Advanced typography settings
		$typography_blocks = $this->settings->get( self::MODULE_NAME, 'typography_blocks' );
		if ( isset( $typography_blocks[ $block_name ] ) ) {
			$css_text_align = $attrs['cssTextAlign'] ?? '';
			if ( $css_text_align ) {
				Responsive::value( $css_text_align, function ( $value, $device ) use ( &$classnames ) {
					$value = $this->sanitize_text_align_value( $value );
					if ( $value ) {
						$classnames[] = "has-text-align-{$value}-{$device}";
					}

					return $value;
				} );
			}
		}

		// Position and z-index settings
		$position_blocks = $this->settings->get( self::MODULE_NAME, 'position_blocks' );
		if ( isset( $position_blocks[ $block_name ] ) ) {
			$css_position     = $attrs['cssPosition'] ?? '';
			$positioned_value = $attrs['cssPositionedValue'] ?? [];
			$css_z_index      = $attrs['cssZIndex'] ?? '';
			$css_order        = $attrs['cssOrder'] ?? '';

			if ( $positioned_value ) {
				Responsive::value( $positioned_value, function ( $value, $device ) use ( &$classnames, &$styles ) {
					$scalar_positioned = $this->sanitize_positioned_value( $value );
					foreach ( $scalar_positioned as $direction => $positioned_value ) {
						$classnames[]                                = "has-position-{$direction}-{$device}";
						$styles["--position-{$direction}-{$device}"] = $positioned_value;
					}
				} );
			}

			if ( $css_position ) {
				Responsive::value( $css_position, function ( $value, $device ) use ( &$classnames ) {
					$position = $this->sanitize_position_value( $value );
					if ( $position ) {
						$classnames[] = "is-position-{$position}-{$device}";
					}
				} );
			}

			if ( $css_z_index ) {
				Responsive::value( $css_z_index, function ( $value, $device ) use ( &$classnames, &$styles ) {
					$z_index = $this->sanitize_z_index_value( $value );
					if ( $z_index !== '' ) {
						$classnames[]            = "has-z-{$device}";
						$styles["--z-{$device}"] = $z_index;
					}
				} );
			}

			if ( $css_order ) {
				Responsive::value( $css_order, function ( $value, $device ) use ( &$classnames, &$styles ) {
					$order = $this->sanitize_order_value( $value );
					if ( $order ) {
						$classnames[]                = 'has-order-' . $device;
						$styles["--order-{$device}"] = $order;
					}
				} );
			}
		}

		// Display attributes
		if ( $this->settings->checked( self::MODULE_NAME, 'display' ) ) {
			$css_display = $attrs['cssDisplay'] ?? '';
			Responsive::value( $css_display, function ( $value, $device ) use ( &$classnames, &$styles ) {
				$value = $this->sanitize_display_value( $value );
				if ( $value ) {
					$classnames[] = "is-display-{$value}-{$device}";
				}
			} );
		}

		// For premium hook
		if ( ! apply_filters( 'plover_core_has_advanced_layout_attributes', ! ( empty( $classnames ) && empty( $styles ) ), $block ) ) {
			return $block_content;
		}

		$html = new Document( $block_content );
		$wrap = $html->get_root_element();
		if ( ! $wrap ) {
			return $block_content;
		}

		$wrap->add_classnames( $classnames );
		$wrap->add_styles( $styles );

		apply_filters( 'plover_core_render_advanced_layout', $wrap, $block );

		return $html->save_html();
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	protected function sanitize_flex_direction_value( $value ) {
		$value = strtolower( $value );

		return in_array( $value, [ 'row', 'column', 'row-reverse', 'column-reverse' ] ) ? $value : '';
	}

	/**
	 * @param $value
	 * @param $orientation
	 *
	 * @return string
	 */
	protected function sanitize_justification_value( $value, $orientation ) {
		$value = strtolower( $value );
		if ( ! in_array( $value, [ 'flex-start', 'center', 'flex-end', 'stretch', 'space-between' ] ) ) {
			return '';
		}

		if ( $orientation === 'vertical' ) { // Flip stretch and space-between value
			$value = $value === 'stretch' ? 'space-between' : ( $value === 'space-between' ? 'stretch' : $value );
		}

		return $value;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	protected function sanitize_text_align_value( $value ) {
		$value = strtolower( $value );

		return in_array( $value, [ 'left', 'center', 'right', 'justify' ] ) ? $value : '';
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	protected function sanitize_position_value( $value ) {
		$value          = strtolower( $value );
		$allowed_values = $this->get_allowed_position_values();

		return in_array( $value, $allowed_values ) ? $value : '';
	}

	/**
	 * @param $value
	 *
	 * @return array
	 */
	protected function sanitize_positioned_value( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$value = array_intersect_key( $value, array_flip( [
			'top',
			'right',
			'bottom',
			'left'
		] ) );

		foreach ( $value as $direction => $scalar ) {
			$value[ $direction ] = Format::sanitize_unit_value( $scalar );
		}

		return $value;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	protected function sanitize_display_value( $value ) {
		$value          = strtolower( $value );
		$allowed_values = $this->get_allowed_display_values();

		return in_array( $value, $allowed_values ) ? $value : '';
	}

	/**
	 * @param $value
	 *
	 * @return int|string
	 */
	protected function sanitize_z_index_value( $value ) {
		if ( $value === '' ) {
			return '';
		}

		return (int) $value;
	}

	/**
	 * @param $value
	 *
	 * @return int|string
	 */
	protected function sanitize_order_value( $value ) {
		if ( $value === '' ) {
			return '';
		}

		return (int) $value;
	}

	/**
	 * Allowed position values.
	 *
	 * @return mixed|null
	 */
	protected function get_allowed_position_values() {
		return apply_filters( 'plover_core_allowed_position_values', array(
			'static',
			'relative',
			'fixed',
			'absolute',
			'sticky'
		) );
	}

	/**
	 * Allowed display values.
	 *
	 * @return mixed|null
	 */
	protected function get_allowed_display_values() {
		return apply_filters( 'plover_core_allowed_display_values', array(
			'none',
			'block',
			'inline',
			'inline-block',
			'flex',
			'inline-flex',
			'grid',
			'inline-grid',
			'contents'
		) );
	}
}
