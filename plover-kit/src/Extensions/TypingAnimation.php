<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Toolkits\Html\Document;
/**
 * Text typing animation
 *
 * @since 1.5.2
 */
class TypingAnimation extends Extension {
    const MODULE_NAME = 'plover_typing_animation';

    /**
     * @return void
     */
    public function register() {
        $this->modules->register( self::MODULE_NAME, array(
            'recent'  => true,
            'premium' => true,
            'label'   => __( 'Typing Animation', 'plover-kit' ),
            'excerpt' => __( 'Add dynamic typing effects to your text.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/typing-animation.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/typing-animation/',
            'fields'  => array(),
            'group'   => 'motion-effects',
        ) );
    }

    /**
     * Boot typing animation extension
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
    }

}
