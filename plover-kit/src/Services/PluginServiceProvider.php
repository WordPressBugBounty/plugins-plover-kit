<?php

namespace Plover\Kit\Services;

use Plover\Core\Assets\Styles;
use Plover\Core\Framework\ServiceProvider;
use Plover\Core\Services\Extensions\Extensions;
use Plover\Kit\Extensions\CodeSnippets;
use Plover\Kit\Extensions\Counter;
use Plover\Kit\Extensions\IconLibrary;
use Plover\Kit\Extensions\Measures;
use Plover\Kit\Extensions\PatternLibrary;
use Plover\Kit\Extensions\PremiumDisplay;
use Plover\Kit\Extensions\PremiumEntranceAnimation;
use Plover\Kit\Extensions\PremiumHighlight;
use Plover\Kit\Extensions\PremiumHoverAnimation;
use Plover\Kit\Extensions\PremiumParticles;
use Plover\Kit\Extensions\PremiumShapeDivider;
use Plover\Kit\Extensions\PremiumSticky;
use Plover\Kit\Extensions\TableOfContents;
/**
 * Bootstrap plugin features.
 *
 * @since 1.0.0
 */
class PluginServiceProvider extends ServiceProvider {
    /**
     * All plugin packages.
     */
    protected const PLUGIN_PACKAGES = ['dashboard', 'data', 'route'];

    /**
     * @param Extensions $extensions
     *
     * @return void
     */
    public function boot( Extensions $extensions, Styles $styles ) {
        $app = plover_kit();
        add_action( 'init', [$this, 'register_plugin_packages'] );
        add_filter( 'plover_core_dashboard_data', [$this, 'localize_current_plan'] );
        add_filter( 'plover_core_editor_data', [$this, 'localize_current_plan'] );
        $extensions->register( 'plover-kit-code-snippets', CodeSnippets::class );
        $extensions->register( 'plover-kit-icons', IconLibrary::class );
        $extensions->register( 'plover-kit-toc', TableOfContents::class );
        $extensions->register( 'plover-kit-patterns', PatternLibrary::class );
        $extensions->register( 'plover-kit-measures', Measures::class );
        $extensions->register( 'plover-kit-counter', Counter::class );
    }

    /**
     * Register plugin packages
     *
     * @return void
     */
    public function register_plugin_packages() {
        foreach ( self::PLUGIN_PACKAGES as $package ) {
            $asset = array();
            $asset_file = plover_kit()->app_path( "assets/js/packages/{$package}/index.min.asset.php" );
            if ( is_file( $asset_file ) ) {
                $asset = (require $asset_file);
            }
            $ver = $asset['version'] ?? (( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ));
            $style_file = plover_kit()->app_path( "assets/js/packages/{$package}/style.min.css" );
            if ( is_file( $style_file ) ) {
                wp_register_style(
                    "plover-kit-{$package}",
                    plover_kit()->app_url( "assets/js/packages/{$package}/style.min.css" ),
                    [],
                    $ver
                );
                wp_style_add_data( "plover-kit-{$package}", 'rtl', 'replace' );
            }
            wp_register_script(
                "plover-kit-{$package}",
                plover_kit()->app_url( "assets/js/packages/{$package}/index.min.js" ),
                $asset['dependencies'] ?? array(),
                $ver,
                false
            );
            $this->enqueue_core_styles_from_deps( $asset['dependencies'] ?? array() );
        }
    }

    /**
     * Localize current plan.
     *
     * @param $data
     *
     * @return mixed
     */
    public function localize_current_plan( $data ) {
        $data['plan'] = ( plover_fs()->can_use_premium_code() ? 'premium' : 'free' );
        // $data['upsell']   = esc_url( admin_url( 'admin.php?page=plover-kit-pricing' ) );
        $data['upsell'] = 'https://wpplover.com/plugins/plover-kit/#plans';
        $data['is_debug'] = plover_kit_is_debug();
        return $data;
    }

    /**
     * Enqueue plover core packages stylesheet from script dependencies.
     *
     * @param $deps
     *
     * @return void
     */
    protected function enqueue_core_styles_from_deps( $deps ) {
        if ( !is_admin() ) {
            // Don't need to load core styles on frontend
            return;
        }
        foreach ( $deps as $dep ) {
            if ( str_starts_with( $dep, 'plover' ) || str_starts_with( $dep, 'wp-' ) ) {
                wp_enqueue_style( $dep );
            }
        }
    }

}
