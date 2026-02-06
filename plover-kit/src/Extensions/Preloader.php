<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\StyleEngine;
/**
 * @since 1.5.6
 */
class Preloader extends Extension {
    const MODULE_NAME = 'plover_preloader';

    public function register() {
        $this->modules->register( self::MODULE_NAME, array(
            'recent'  => true,
            'premium' => false,
            'label'   => __( 'Preloader', 'plover-kit' ),
            'excerpt' => __( 'Animated elements that appear while website content loads make waiting more enjoyable.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/preloader.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/preloader/',
            'fields'  => array(
                'preloader' => array(
                    'control' => Control::T_PLACEHOLDER,
                    'default' => '',
                ),
            ),
            'group'   => 'blog-tools',
        ) );
    }

    /**
     * Boot preloader extension
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // Dashboard assets should always be queued.
        $this->scripts->enqueue_dashboard_asset( 'plover-preloader', array(
            'src'   => plover_kit()->app_url( 'assets/js/block-extensions/preloader/index.min.js' ),
            'path'  => plover_kit()->app_path( 'assets/js/block-extensions/preloader/index.min.js' ),
            'ver'   => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'asset' => plover_kit()->app_path( 'assets/js/block-extensions/preloader/index.min.asset.php' ),
        ) );
        $this->styles->enqueue_dashboard_asset( 'plover-preloader', array(
            'src'  => plover_kit()->app_url( 'assets/js/block-extensions/preloader/style.min.css' ),
            'path' => plover_kit()->app_path( 'assets/js/block-extensions/preloader/style.min.css' ),
            'ver'  => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'rtl'  => 'replace',
        ) );
        // localize preloaders data to dashboard
        add_filter( 'plover_core_dashboard_data', array($this, 'localize_dashboard_preloaders') );
        // Module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        $settings = json_decode( $this->settings->get( self::MODULE_NAME, 'preloader' ), true );
        $group = $settings['loader']['group'] ?? null;
        $style = $settings['loader']['style'] ?? null;
        // Preloder is not set
        if ( !$group || !$style ) {
            return;
        }
        $handle = "plover-loader-{$group}-{$style}";
        // Enqueue frontend loader script
        $this->scripts->enqueue_asset( $handle, array(
            'ver'    => PLOVER_KIT_VERSION,
            'src'    => plover_kit()->app_url( "assets/js/frontend/preloader/index.min.js" ),
            'path'   => plover_kit()->app_path( "assets/js/frontend/preloader/index.min.js" ),
            'asset'  => plover_kit()->app_path( 'assets/js/frontend/preloader/index.min.asset.php' ),
            'footer' => true,
        ) );
        // Enqueue frontend loader css
        $this->styles->enqueue_asset( $handle, array(
            'ver'  => PLOVER_KIT_VERSION,
            'src'  => plover_kit()->app_url( "assets/css/preloaders/{$group}/{$style}.css" ),
            'path' => plover_kit()->app_path( "assets/css/preloaders/{$group}/{$style}.css" ),
        ) );
        // Allow display style css rule
        add_filter( 'safe_style_css', array($this, 'safe_style_css') );
        // Generate wrap css and color vars
        $color = $settings['color'] ?? array();
        $declarations = [
            'position'         => 'fixed',
            'top'              => 0,
            'right'            => 0,
            'bottom'           => 0,
            'left'             => 0,
            'width'            => '100%',
            'height'           => '100%',
            'z-index'          => 999,
            'display'          => 'flex',
            'justify-content'  => 'center',
            'align-items'      => 'center',
            'background-color' => 'var(--bg, #fff)',
            '--bg'             => $color['background'] ?? '#fff',
        ];
        if ( isset( $color['loader'] ) ) {
            if ( is_string( $color['loader'] ) ) {
                $declarations['--loader'] = $color['loader'];
            } else {
                if ( is_array( $color['loader'] ) ) {
                    foreach ( $color['loader'] as $key => $value ) {
                        $declarations['--' . $key] = $value;
                    }
                }
            }
        }
        $raw_css = StyleEngine::compile_css( $declarations );
        remove_filter( 'safe_style_css', array($this, 'safe_style_css') );
        // Enqueue dynamic CSS rules
        $this->styles->enqueue_asset( $handle . '-raw', array(
            'raw' => ".plover-loader-wrap{{$raw_css}}",
        ) );
        // Render loader html structure
        add_action( 'wp_body_open', function () use($handle) {
            echo wp_kses_post( '<div class="plover-loader-wrap"><div class="plover-loader ' . $handle . '"></div></div>' );
        } );
    }

    /**
     * Temporary safe style CSS rules
     *
     * @param $rules
     *
     * @return array|string[]
     */
    public function safe_style_css( $rules ) {
        return array_merge( $rules, array('display') );
    }

    /**
     * All loaders
     *
     * @return array[]
     */
    public function localize_dashboard_preloaders( $data ) {
        $data['preloaders'] = apply_filters( 'plover-kit/preloaders', array(
            'spinner'                => apply_filters( 'plover-kit/preloader_spinner_styles', array(
                'label'  => __( 'Spinner', 'plover-kit' ),
                'count'  => 45,
                'styles' => array(
                    'style-01' => array('active', 'accent'),
                    'style-02' => array(),
                    'style-03' => array(),
                    'style-04' => array('active', 'track'),
                    'style-05' => array('track'),
                    'style-06' => array('track'),
                    'style-07' => array('active', 'track'),
                    'style-08' => array('active', 'track'),
                    'style-09' => array('active', 'track'),
                ),
            ) ),
            'dots'                   => apply_filters( 'plover-kit/preloader_dot_styles', array(
                'label'  => __( 'Dots', 'plover-kit' ),
                'count'  => 50,
                'styles' => array(
                    'style-01' => array(),
                    'style-02' => array(),
                    'style-03' => array('primary', 'secondary'),
                    'style-04' => array(),
                    'style-05' => array(),
                    'style-06' => array(),
                    'style-07' => array(),
                    'style-08' => array(),
                    'style-09' => array(),
                ),
            ) ),
            'bars'                   => apply_filters( 'plover-kit/preloader_bar_styles', array(
                'label'  => __( 'Bars', 'plover-kit' ),
                'count'  => 30,
                'styles' => array(
                    'style-01' => array(),
                    'style-02' => array(),
                    'style-03' => array(),
                    'style-04' => array(),
                    'style-05' => array(),
                    'style-06' => array(),
                ),
            ) ),
            'pie__premium_only'      => apply_filters( 'plover-kit/preloader_pie_styles', array(
                'label'   => __( 'Pie', 'plover-kit' ),
                'premium' => true,
                'count'   => 10,
                'styles'  => array(),
            ) ),
            'progress__premium_only' => apply_filters( 'plover-kit/preloader_progress_styles', array(
                'label'   => __( 'Progress', 'plover-kit' ),
                'premium' => true,
                'count'   => 28,
                'styles'  => array(),
            ) ),
            'wobbling__premium_only' => apply_filters( 'plover-kit/preloader_wobbling_styles', array(
                'label'   => __( 'Wobbling', 'plover-kit' ),
                'premium' => true,
                'count'   => 15,
                'styles'  => array(),
            ) ),
        ) );
        return $data;
    }

}
