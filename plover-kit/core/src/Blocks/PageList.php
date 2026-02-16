<?php

namespace Plover\Core\Blocks;

use Plover\Core\Services\Blocks\Contract\HasSupports;
use Plover\Core\Services\Blocks\Contract\RenderableBlock;
use Plover\Core\Services\Blocks\Traits\ShouldNotOverride;
use Plover\Core\Toolkits\Html\Document;
use Plover\Core\Toolkits\StyleEngine;

/**
 * @since 1.0.0
 */
class PageList implements HasSupports, RenderableBlock {

	use ShouldNotOverride;

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'core/page-list';
	}

	/**
	 * @inheritDoc
	 */
	public function supports(): array {
		return [
			'spacing' => [
				'padding'                       => true,
				'margin'                        => [ 'top', 'bottom' ],
				'blockGap'                      => true,
				'__experimentalDefaultControls' => [
					'padding'  => true,
					'margin'   => false,
					'blockGap' => true,
				],
			],
			'color'                => [
				'gradients'                     => true,
				'link'                          => true,
				'__experimentalDefaultControls' => [
					'background' => true,
					'text'       => true
				]
			],
			'__experimentalBorder' => [
				'radius'                        => true,
				'width'                         => true,
				'color'                         => true,
				'style'                         => true,
				'__experimentalDefaultControls' => [
					'width' => true,
					'color' => true,
				],
			],
			'ploverShadow'         => [
				'text'            => true,
				'box'             => true,
				'defaultControls' => [
					'text' => true,
				]
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function render( $block_content, $block ): string {
		$attrs = $block['attrs'] ?? [];
		$html  = new Document( $block_content );
		$list  = $html->get_element_by_tags_priority( array( 'ul', 'ol' ) );
		if ( ! $list ) {
			return $block_content;
		}

		$gap = StyleEngine::get_block_gap_value( $attrs );
		// add block gap
		if ( isset( $gap ) ) {
			$list->add_styles( [ '--plover--style--block-gap' => $gap ] );
		}

		return $html->save_html();
	}
}