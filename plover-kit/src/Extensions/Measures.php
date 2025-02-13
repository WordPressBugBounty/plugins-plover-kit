<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Toolkits\Format;
use Plover\Core\Toolkits\Html\Document;
use Plover\Core\Toolkits\Responsive;
/**
 * Add height, width, min/max height and min/max width options in Plover: Measures panel.
 *
 * @since 1.4.0
 */
class Measures extends Extension {
    const MODULE_NAME = 'plover_block_measures';

    /**
     * @return void
     */
    public function register( Blocks $blocks ) {
        $this->modules->register( self::MODULE_NAME, array(
            'premium' => true,
            'label'   => __( 'Block Measures', 'plover-kit' ),
            'excerpt' => __( 'You can set height, width, [min/max]-height and [min/max]-width css properties for blocks, responsive!', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/block-measures.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/block-measures/',
            'fields'  => array(),
            'group'   => 'supports',
        ) );
        $support_block_types = $this->support_block_types();
        foreach ( $support_block_types as $block ) {
            $blocks->extend_block_supports( $block, array(
                'ploverMeasures' => true,
            ) );
        }
    }

    /**
     * Bootstrap this module.
     *
     * @return void
     */
    public function boot() {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        $this->scripts->enqueue_editor_asset( 'plover-measures-extension', array(
            'ver'   => PLOVER_KIT_VERSION,
            'src'   => plover_kit()->app_url( 'assets/js/block-extensions/measures/index.min.js' ),
            'path'  => plover_kit()->app_path( 'assets/js/block-extensions/measures/index.min.js' ),
            'asset' => plover_kit()->app_path( 'assets/js/block-extensions/measures/index.min.asset.php' ),
        ) );
    }

    /**
     * @return array
     */
    protected function support_block_types() {
        return apply_filters( 'plover-kit/measures_supported_blocks', array('core/group', 'core/cover') );
    }

}
