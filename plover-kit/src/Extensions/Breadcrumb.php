<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\StyleEngine;
/**
 * Introduce a new breadcrumb block and shortcode
 *
 * @since 1.5.5
 */
class Breadcrumb extends Extension {
    const MODULE_NAME = 'plover_breadcrumb';

    const BLOCK_NAME = 'plover-kit/breadcrumb';

    public function register() {
        $fields = array();
        $this->modules->register( self::MODULE_NAME, array(
            'recent'  => true,
            'premium' => true,
            'label'   => __( 'Breadcrumb', 'plover-kit' ),
            'excerpt' => __( 'Let’s you easily display breadcrumb navigation on your WordPress site, easy customization.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/breadcrumb.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/breadcrumb/',
            'fields'  => $fields,
            'group'   => 'blog-tools',
        ) );
    }

    /**
     * Boot breadcrumb block extension
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        // register breadcrumb block
        add_action( 'init', [$this, 'register_blocks'] );
    }

    /**
     * Register blocks
     *
     * @return void
     */
    public function register_blocks() {
        $attributes = array();
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/breadcrumb' ), array(
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
