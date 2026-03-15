<?php

namespace Plover\Core\Toolkits;

use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\Html\Document;

/**
 * Sanitize and escape utils.
 *
 * @since 1.0.0
 */
class Format {

	const ALL_UNITS = [
		'px'    => [
			'value' => 'px',
			'step'  => 1,
		],
		'%'     => [
			'value' => '%',
			'step'  => 0.1,
		],
		'em'    => [
			'value' => 'em',
			'step'  => 0.01,
		],
		'rem'   => [
			'value' => 'rem',
			'step'  => 0.01,
		],
		'vw'    => [
			'value' => 'vw',
			'step'  => 0.1,
		],
		'vh'    => [
			'value' => 'vh',
			'step'  => 0.1,
		],
		'vmin'  => [
			'value' => 'vmin',
			'step'  => 0.1,
		],
		'vmax'  => [
			'value' => 'vmax',
			'step'  => 0.1,
		],
		'ch'    => [
			'value' => 'ch',
			'step'  => 0.01,
		],
		'ex'    => [
			'value' => 'ex',
			'step'  => 0.01,
		],
		'cm'    => [
			'value' => 'cm',
			'step'  => 0.001,
		],
		'mm'    => [
			'value' => 'mm',
			'step'  => 0.1,
		],
		'in'    => [
			'value' => 'in',
			'step'  => 0.001,
		],
		'pc'    => [
			'value' => 'pc',
			'step'  => 1,
		],
		'pt'    => [
			'value' => 'pt',
			'step'  => 1,
		],
		'svw'   => [
			'value' => 'svw',
			'step'  => 0.1,
		],
		'svh'   => [
			'value' => 'svh',
			'step'  => 0.1,
		],
		'svi'   => [
			'value' => 'svi',
			'step'  => 0.1,
		],
		'svb'   => [
			'value' => 'svb',
			'step'  => 0.1,
		],
		'svmin' => [
			'value' => 'svmin',
			'step'  => 0.1,
		],
		'lvw'   => [
			'value' => 'lvw',
			'step'  => 0.1,
		],
		'lvh'   => [
			'value' => 'lvh',
			'step'  => 0.1,
		],
		'lvi'   => [
			'value' => 'lvi',
			'step'  => 0.1,
		],
		'lvb'   => [
			'value' => 'lvb',
			'step'  => 0.1,
		],
		'lvmin' => [
			'value' => 'lvmin',
			'step'  => 0.1,
		],
		'dvw'   => [
			'value' => 'dvw',
			'step'  => 0.1,
		],
		'dvh'   => [
			'value' => 'dvh',
			'step'  => 0.1,
		],
		'dvi'   => [
			'value' => 'dvi',
			'step'  => 0.1,
		],
		'dvb'   => [
			'value' => 'dvb',
			'step'  => 0.1,
		],
		'dvmin' => [
			'value' => 'dvmin',
			'step'  => 0.1,
		],
		'dvmax' => [
			'value' => 'dvmax',
			'step'  => 0.1,
		],
		'svmax' => [
			'value' => 'svmax',
			'step'  => 0.1,
		],
		'lvmax' => [
			'value' => 'lvmax',
			'step'  => 0.1,
		],
	];

	/**
	 * Generates a closure to sanitize a fixed set of values.
	 *
	 * @param $args
	 *
	 * @return \Closure
	 */
	public static function create_select_sanitizer( $args = array() ) {
		return function ( $input ) use ( $args ) {
			// Get list of choices from the control associated with the setting.
			$options = $args['options'] ?? array();

			// If the input is valid, return it; otherwise, return the default.
			return in_array( $input, Arr::pluck( $options, 'value' ) ) ? $input : ( $args['default'] ?? null );
		};
	}

	/**
	 * Generates a closure to sanitize tags value.
	 *
	 * @param $args
	 *
	 * @return \Closure
	 */
	public static function create_tags_sanitizer( $args = array() ) {
		return function ( $input ) use ( $args ) {
			if ( is_string( $input ) ) {
				$input = explode( ',', $input );
			}

			if ( ! is_array( $input ) ) {
				return [];
			}

			if ( isset( $args['suggestions'] ) && ( $args['validate'] ?? false ) ) {
				$input = array_filter( $input, function ( $item ) use ( $args ) {
					return in_array( $item, $args['suggestions'] );
				} );
			}

			return $input;
		};
	}

	/**
	 * Generates a closure to sanitize block selector control value.
	 *
	 * @param $args
	 *
	 * @return \Closure
	 *
	 * @since 1.3.0
	 */
	public static function create_block_selector_sanitizer( $args = array() ) {
		return function ( $input ) use ( $args ) {
			if ( ! is_array( $input ) ) {
				return array();
			}

			$supported_namespace = array();
			$registered_blocks   = array();

			if ( isset( $args['collection'] ) ) {
				foreach ( $args['collection'] as $collection => $value ) {
					if ( is_bool( $value ) && $value ) { // namespace value, like 'core => true'
						$supported_namespace[] = $collection;
					} else if ( is_array( $value ) ) { // collection value
						$registered_blocks = array_merge(
							$registered_blocks,
							array_values( Arr::pluck( $value['blocks'], 'name' ) ),
						);
					}
				}
			}

			$result = array();

			foreach ( $input as $block_name => $block_fields ) {
				$block_name = self::sanitize_block_name( $block_name, $supported_namespace, $registered_blocks );
				if ( empty( $block_name ) ) { // Invalid block name
					continue;
				}

				// We need a new array to store the field values, and invalid fields will be ignored.
				$fields_value = array();
				// We have a configurable block, sanitize every block field
				if ( isset( $args['fields'] ) && is_array( $args['fields'] ) ) {
					foreach ( $args['fields'] as $field => $fieldArgs ) {
						// create block field sanitizer
						$sanitizer = Control::sanitize(
							$fieldArgs['control'],
							array_merge(
								$fieldArgs['control_args'] ?? array(),
								array( 'default' => $fieldArgs['default'] )
							)
						);

						if ( $sanitizer ) {
							$fields_value[ $field ] = call_user_func( $sanitizer, $block_fields[ $field ] ?? null );
						}
					}
				}

				$result[ $block_name ] = $fields_value;
			}

			return $result;
		};
	}

	/**
	 * Sanitize a block name to ensure it only contains lowercase letters, numbers, hyphens, underscores, and slash.
	 * If `supported_namespace` and `registered_blocks` are provided,
	 * checks will also be performed on the range of valid block names.
	 *
	 * @param string $raw_name The raw input block name.
	 * @param array $supported_namespace The raw input block name.
	 * @param array $registered_blocks The raw input block name.
	 *
	 * @return string Sanitized block name, or empty string if sanitization results in an empty value.
	 *
	 * @since 1.3.0
	 */
	public static function sanitize_block_name( $raw_name, $supported_namespace = array(), $registered_blocks = array() ) {
		// Remove all illegal characters (allow only lowercase letters, numbers, underscore, hyphen, slash)
		$block_name = preg_replace( '/[^a-z0-9_\/-]/', '', strtolower( trim( $raw_name ) ) );
		// Allow all blocks
		if ( empty( $supported_namespace ) && empty( $registered_blocks ) ) {
			return $block_name;
		}
		// It's a registered block
		if ( in_array( $block_name, $registered_blocks ) ) {
			return $block_name;
		}
		// It's in the available block collection.
		list( $namespace ) = explode( '/', $block_name );
		if ( in_array( $namespace, $supported_namespace ) ) {
			return $block_name;
		}

		return '';
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	public static function sanitize_text( $str ) {
		return sanitize_text_field( $str );
	}

	/**
	 * Alias for sanitize_checkbox.
	 *
	 * @param $checked
	 *
	 * @return string
	 */
	public static function sanitize_switch( $checked ) {
		return static::sanitize_checkbox( $checked );
	}

	/**
	 * Checkbox value sanitization callback.
	 *
	 * Sanitization callback for 'checkbox' type controls. This callback sanitizes `$checked`
	 * as a boolean value, either TRUE or FALSE.
	 *
	 * @param $checked
	 *
	 * @return string
	 */
	public static function sanitize_checkbox( $checked ) {
		return ( $checked === 'yes' || $checked === true ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize raw SVG string.
	 *
	 * @param string $svg
	 *
	 * @return string
	 */
	public static function sanitize_svg( $svg ) {
		static $sanitizer = null;

		if ( is_null( $sanitizer ) ) {
			$sanitizer = new \enshrined\svgSanitize\Sanitizer();
			$sanitizer->minify( true );
			$sanitizer->removeXMLTag( true );
		}

		$dom    = new Document( $svg );
		$svg_el = $dom->get_element_by_tag_name( 'svg' );
		if ( ! $svg_el ) {
			return '';
		}

		// Removing attributes that affect custom style for SVG element.
		$svg_el->remove_attribute( 'width' );
		$svg_el->remove_attribute( 'height' );
		$svg_el->remove_attribute( 'style' );
		$svg_el->remove_attribute( 'class' );
		$svg = $dom->save_html();

		$svg = $sanitizer->sanitize( $svg );

		// Remove comments and spaces to minify store size.
		$svg = preg_replace( '/<!--(.|\s)*?-->/', '', $svg );
		$svg = preg_replace( '/\s+/', ' ', $svg );
		$svg = preg_replace( '/\t+/', '', $svg );
		$svg = preg_replace( '/>\s+</', '><', $svg );
		// Correct viewBox.
		$svg = str_replace( 'viewbox=', 'viewBox=', $svg );

		return $svg;
	}

	/**
	 * Sanitize value with unit
	 *
	 * @param $value
	 * @param $default_unit
	 *
	 * @return string
	 */
	public static function sanitize_unit_value( $value, $default_unit = 'px' ) {
		list( $quantity, $unit ) = self::parse_quantity_and_unit_from_raw_value( $value );

		if ( $quantity === null ) {
			return '';
		}

		$unit = $unit ? $unit : $default_unit;

		return "{$quantity}{$unit}";
	}

	/**
	 * @param $raw_value
	 * @param $allowed_units
	 *
	 * @return array
	 */
	public static function parse_quantity_and_unit_from_raw_value( $raw_value, $allowed_units = [] ) {
		if ( empty( $allowed_units ) ) {
			$allowed_units = Arr::pluck( array_values( self::ALL_UNITS ), 'value' );
		}

		$trimmedValue = isset( $raw_value ) ? trim( (string) $raw_value ) : '';
		if ( empty( $trimmedValue ) ) {
			return [ null, null ];
		}

		$parsedQuantity   = floatval( $trimmedValue );
		$quantityToReturn = is_finite( $parsedQuantity ) ? $parsedQuantity : null;

		$unitMatch   = preg_match( '/[\d.\-\+]*\s*(.*)/', $trimmedValue, $matches ) ? $matches : null;
		$matchedUnit = isset( $unitMatch[1] ) ? strtolower( $unitMatch[1] ) : null;

		$unitToReturn = in_array( $matchedUnit, $allowed_units ) ? $matchedUnit : null;

		return [ $quantityToReturn, $unitToReturn ];
	}

	/**
	 * Format inline JavaScript code.
	 *
	 * @param string $js
	 *
	 * @return string
	 */
	public static function inline_js( $js ) {
		$js = str_replace( '"', "'", $js );
		$js = trim( rtrim( $js, ';' ) );
		$js = Str::reduce_whitespace( $js );
		$js = Str::remove_line_breaks( $js );

		return apply_filters( 'plover_core_format_inline_js', $js );
	}

	/**
	 * Format inline CSS code.
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public static function inline_css( $css ) {
		return StyleEngine::compile_css(
			StyleEngine::css_to_declarations( $css )
		);
	}
}