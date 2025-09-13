<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
/**
 * Introduce a new counter block
 *
 * @since 1.5.0
 */
class Counter extends Extension {
    const MODULE_NAME = 'plover_counter_block';

    const COUNTER_BLOCK_NAME = 'plover-kit/counter';

    /**
     * @return void
     */
    public function register() {
        $this->modules->register( self::MODULE_NAME, array(
            'premium' => true,
            'label'   => __( 'Counter Block', 'plover-kit' ),
            'excerpt' => __( 'Introduce a counter block enables you to add an animated numbered counter to your page.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/counter-block.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/counter-block/',
            'fields'  => array(),
            'group'   => 'motion-effects',
        ) );
    }

    /**
     * Boot counter block extension
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        // register counter block
        add_action( 'init', [$this, 'register_blocks'] );
    }

    /**
     * Register blocks
     *
     * @return void
     */
    public function register_blocks() {
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/counter' ) );
    }

}
