<?php

namespace Plover\Core\Blocks;

use Plover\Core\Services\Blocks\Contract\HasSupports;
use Plover\Core\Services\Blocks\Contract\RenderableBlock;
use Plover\Core\Services\Blocks\Traits\ShouldNotOverride;
use Plover\Core\Toolkits\Html\Document;

/**
 * @since 1.2.7
 */
class Navigation implements HasSupports, RenderableBlock {

    use ShouldNotOverride;

    /**
     * @inheritDoc
     */
    public function name(): string {
        return 'core/navigation';
    }

	/**
	 * @inheritDoc
	 */
	public function supports(): array {
		return [
			'color'                => [
				'text'                          => false,
				'background'                    => false,
				'gradients'                     => false,
				'link'                          => true,
			],
			'spacing'                 => [
				'padding'  => true,
				'margin'   => true,
				'blockGap' => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function render( $block_content, $block ): string {

        $nav_styles = array();

    	$element_link_styles = isset( $block['attrs']['style']['elements']['link'] ) ? $block['attrs']['style']['elements']['link'] : null;
        if ( $element_link_styles ) {
            $link_styles = wp_style_engine_get_styles( $element_link_styles );
            if ( isset( $link_styles['declarations']['color'] ) ) {
                $nav_styles['--wp--custom--navigation--color--text'] = $link_styles['declarations']['color'];
            }

            if ( isset( $element_link_styles[':hover'] ) ) {
                $hover_styles = wp_style_engine_get_styles( $element_link_styles[':hover'] );
                if ( isset( $hover_styles['declarations']['color'] ) ) {
                    $nav_styles['--wp--custom--navigation--hover--color--text'] = $hover_styles['declarations']['color'];
                }
            }

        }

        if ( empty( $nav_styles ) ) {
            return $block_content;
        }

        $html  = new Document( $block_content );
        $root = $html->get_root_element();
        if ( ! $root ) {
            return $block_content;
        }

        $root->add_styles( $nav_styles );

        return $html->save_html();
    }
}