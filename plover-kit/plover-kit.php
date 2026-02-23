<?php

/*
 * Plugin Name:         Plover Kit
 * Plugin URI:          https://wpplover.com/plugins/plover-kit
 * Description:         Plover kit have pluggable modules that enhance the Gutenberg core blocks and also provide extended features.
 * Author:              WP Plover
 * Author URI:          https://www.wpplover.com
 * Version:             1.5.10
 * Requires at least:   6.4
 * Requires PHP:        7.4
 * License:             GPLv2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         plover-kit
*/
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Defining plugin constants.
if ( !defined( 'PLOVER_KIT_SLUG' ) ) {
    define( 'PLOVER_KIT_SLUG', 'plover-kit' );
}
if ( !defined( 'PLOVER_KIT_VERSION' ) ) {
    define( 'PLOVER_KIT_VERSION', '1.5.10' );
}
if ( !defined( 'PLOVER_KIT_PLUGIN_PATH' ) ) {
    define( 'PLOVER_KIT_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( !defined( 'PLOVER_KIT_API_HOST' ) ) {
    define( 'PLOVER_KIT_API_HOST', 'https://api.wpplover.com/' );
}
if ( !defined( 'PLOVER_KIT_API_V1' ) ) {
    define( 'PLOVER_KIT_API_V1', untrailingslashit( PLOVER_KIT_API_HOST ) . '/index.php?rest_route=/plover-kit-api/v1' );
}
if ( !defined( 'PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS' ) ) {
    /**
     * @since 1.1.0
     */
    define( 'PLOVER_KIT_REMOTE_PATTERNS_CACHE_IN_SECONDS', DAY_IN_SECONDS );
}
if ( function_exists( 'plover_fs' ) ) {
    plover_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'plover_fs' ) ) {
        // Create a helper function for easy SDK access.
        function plover_fs() {
            global $plover_fs;
            if ( !isset( $plover_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_15782_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_15782_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $plover_fs = fs_dynamic_init( array(
                    'id'              => '15782',
                    'slug'            => 'plover-kit',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_100767648311be1f84cbfd9f5ea53',
                    'is_premium'      => false,
                    'premium_suffix'  => 'Premium',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'has_affiliation' => 'selected',
                    'menu'            => array(
                        'slug' => 'plover-kit',
                    ),
                    'is_live'         => true,
                ) );
            }
            return $plover_fs;
        }

        // Init Freemius.
        plover_fs();
        // Signal that SDK was initiated.
        do_action( 'plover_fs_loaded' );
    }
    // Require plover-core if not loaded.
    if ( !function_exists( 'plover_core' ) ) {
        require_once PLOVER_KIT_PLUGIN_PATH . '/core/vendor/autoload.php';
    }
    // Require plugin autoload file.
    require_once PLOVER_KIT_PLUGIN_PATH . '/vendor/autoload.php';
    // Bootstrap plover core
    $bootstrap = \Plover\Core\Bootstrap::make( PLOVER_KIT_SLUG, dirname( __FILE__ ), ( version_compare( \Plover\Core\Plover::VERSION, '1.0.6', '>=' ) ? untrailingslashit( plugin_dir_url( __FILE__ ) ) : [] ) );
    $bootstrap->withProviders( [\Plover\Core\Services\CoreFeaturesServiceProvider::class, \Plover\Kit\Services\PluginServiceProvider::class, \Plover\Kit\Services\DashboardServiceProvider::class] );
    $bootstrap->boot();
}