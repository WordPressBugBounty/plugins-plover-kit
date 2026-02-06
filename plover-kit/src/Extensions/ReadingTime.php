<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
/**
 * Introduce a new reading time block
 *
 * @since 1.5.1
 */
class ReadingTime extends Extension {
    const MODULE_NAME = 'plover_reading_time_block';

    const BLOCK_NAME = 'plover-kit/reading-time';

    /**
     * @return void
     */
    public function register() {
        $fields = array();
        $this->modules->register( self::MODULE_NAME, array(
            'premium' => true,
            'label'   => __( 'Reading Time Block', 'plover-kit' ),
            'excerpt' => __( 'Let’s you easily add an estimated reading time to your WordPress posts.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/reading-time-block.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/reading-time-block/',
            'fields'  => $fields,
            'group'   => 'blog-tools',
        ) );
    }

    /**
     * Boot reading time block extension
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        // register reading time block
        add_action( 'init', [$this, 'register_blocks'] );
    }

    /**
     * Register blocks
     *
     * @return void
     */
    public function register_blocks() {
        $attributes = array();
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/reading-time' ), array(
            'render_callback' => array($this, 'render_block'),
            'attributes'      => $attributes,
        ) );
    }

    /**
     * Server side render block content
     *
     * @param $attributes
     *
     * @return string
     */
    public function render_block( $attributes ) {
        return '';
    }

}
