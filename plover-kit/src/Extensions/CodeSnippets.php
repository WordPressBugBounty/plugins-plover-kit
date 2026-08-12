<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Nest\Cache\Facades\Cache;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Kit\Controllers\CodeSnippetsController;
use Plover\Kit\Toolkits\Context;
/**
 * @since 1.2.0
 */
class CodeSnippets extends Extension {
    const MODULE_NAME = 'plover_code_snippets';

    const CODE_SNIPPET_POST_TYPE = 'plover_code_snippet';

    /**
     * @return void
     */
    public function register() {
        $this->modules->register( self::MODULE_NAME, array(
            'label'   => __( 'Code Snippets', 'plover-kit' ),
            'excerpt' => __( 'Insert code snippets to site header or footer section like Google Analytics code, AdSense Code, Facebook Pixels code, and more.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/code-snippets.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/code-snippets/',
            'fields'  => array(
                'code_snippets_editor' => array(
                    'control' => Control::T_PLACEHOLDER,
                ),
            ),
        ) );
    }

    /**
     * Boot code snippets extension
     *
     * @return void
     */
    public function boot() {
        // dashboard assets should always be queued.
        $this->scripts->enqueue_dashboard_asset( 'plover-code-snippets', array(
            'src'   => plover_kit()->app_url( 'assets/js/block-extensions/code-snippets/index.min.js' ),
            'path'  => plover_kit()->app_path( 'assets/js/block-extensions/code-snippets/index.min.js' ),
            'ver'   => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'asset' => plover_kit()->app_path( 'assets/js/block-extensions/code-snippets/index.min.asset.php' ),
        ) );
        $this->styles->enqueue_dashboard_asset( 'plover-code-snippets', array(
            'src'  => plover_kit()->app_url( 'assets/js/block-extensions/code-snippets/style.min.css' ),
            'path' => plover_kit()->app_path( 'assets/js/block-extensions/code-snippets/style.min.css' ),
            'ver'  => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'rtl'  => 'replace',
        ) );
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        // Register code snippets post type and block
        add_action( 'init', [$this, 'register_post_types'] );
        add_action( 'init', [$this, 'register_blocks'] );
        // Invalidate snippets cache after change or delete
        add_action( 'save_post_' . self::CODE_SNIPPET_POST_TYPE, [$this, 'invalidate_snippets_cache'] );
        add_action( 'deleted_post', [$this, 'invalidate_snippets_cache_on_delete'] );
        // Evaluate all active code snippets
        $this->evaluate();
    }

    /**
     * Evaluate all active code snippets when code-snippets module booting
     *
     * @return bool
     * @since 1.7.0
     */
    private function evaluate() {
        if ( plover_kit_is_safe_mode() ) {
            return false;
        }
        $html_snippets = $this->fetch_active_snippets( [
            'meta_query' => [[
                'relation' => 'OR',
                [
                    'key'     => 'plover_kit_code_snippet_type',
                    'value'   => 'html',
                    'compare' => '=',
                ],
                [
                    'key'     => 'plover_kit_code_snippet_type',
                    'value'   => '',
                    'compare' => 'NOT EXISTS',
                ],
            ]],
        ] );
        $header_footer_codes = [];
        // Organize HTML code snippets by location and priority
        foreach ( $html_snippets as $snippet ) {
            if ( !$this->should_execute_code_snippet( $snippet ) ) {
                continue;
            }
            $loc = $snippet['location'];
            // header or footer
            $pri = $snippet['priority'];
            if ( !isset( $header_footer_codes[$loc][$pri] ) ) {
                $header_footer_codes[$loc][$pri] = '';
            }
            $header_footer_codes[$loc][$pri] .= $snippet['code'];
        }
        // Execute header and footer code snippets
        foreach ( $header_footer_codes as $location => $priorities ) {
            foreach ( $priorities as $priority => $code ) {
                if ( trim( $code ) === '' ) {
                    continue;
                }
                $hook = ( $location === 'header' ? 'wp_head' : 'wp_footer' );
                add_action( $hook, function () use($code) {
                    echo wp_unslash( $code ) . PHP_EOL;
                }, $priority );
            }
        }
        // Execute JavaScript, CSS and PHP code snippets
        $snippets = $this->fetch_active_snippets( [
            'meta_query' => [[
                'key'     => 'plover_kit_code_snippet_type',
                'value'   => array('css', 'js', 'php'),
                'compare' => 'IN',
            ], [
                'key'     => 'plover_kit_code_snippet_location',
                'value'   => ( is_admin() ? array('everywhere', 'admin') : array('everywhere', 'frontend', '') ),
                'compare' => 'IN',
            ]],
        ] );
        foreach ( $snippets as $snippet ) {
            if ( $this->should_execute_code_snippet( $snippet ) ) {
                $this->execute_snippet( $snippet );
            }
        }
        return true;
    }

    /**
     * Fetch and sort active snippets
     *
     * @param $query
     *
     * @return array
     * @since 1.7.0
     *
     */
    private function fetch_active_snippets( $query = array() ) {
        $version = $this->get_snippets_cache_version();
        $cache_key = 'plover_active_snippets_' . md5( serialize( $query ) ) . $version;
        $snippets = Cache::get( $cache_key );
        if ( null === $snippets ) {
            $posts = get_posts( array_merge( [
                'post_type'   => self::CODE_SNIPPET_POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
            ], $query ) );
            $snippets = [];
            foreach ( $posts as $post ) {
                $snippets[] = $this->transform_snippet_post( $post );
            }
            $comparisons = [function ( array $a, array $b ) {
                return $a['priority'] <=> $b['priority'];
            }, function ( array $a, array $b ) {
                return $a['id'] <=> $b['id'];
            }];
            usort( $snippets, static function ( $a, $b ) use($comparisons) {
                foreach ( $comparisons as $comparison ) {
                    $result = $comparison( $a, $b );
                    if ( 0 !== $result ) {
                        return $result;
                    }
                }
                return 0;
            } );
            Cache::set( $cache_key, $snippets, DAY_IN_SECONDS );
        }
        return $snippets;
    }

    /**
     * Transform snippet post to standard snippet array
     *
     * @param $post
     * @param array $override
     *
     * @return array
     * @since 1.7.0
     *
     */
    private function transform_snippet_post( $post, $override = array() ) {
        $code_type = get_post_meta( $post->ID, 'plover_kit_code_snippet_type', true );
        $location = get_post_meta( $post->ID, 'plover_kit_code_snippet_location', true );
        if ( !in_array( $code_type, [
            'html',
            'js',
            'css',
            'php'
        ] ) ) {
            $code_type = 'html';
        }
        if ( !in_array( $location, array(
            'everywhere',
            'admin',
            'frontend',
            'header',
            'footer',
            'inline'
        ) ) ) {
            $location = ( $code_type === 'html' ? 'header' : 'frontend' );
        }
        return array_merge( [
            'id'       => $post->ID,
            'location' => $location,
            'type'     => $code_type,
            'priority' => $this->get_priority( get_post_meta( $post->ID, 'plover_kit_code_snippet_priority', true ) ),
            'exclude'  => get_post_meta( $post->ID, 'plover_kit_code_snippet_exclude', true ),
            'code'     => $post->post_content,
        ], $override );
    }

    /**
     * Execute code snippet
     *
     * @param $snippet
     *
     * @return false|mixed|string|null
     * @since 1.7.0
     *
     */
    private function execute_snippet( $snippet ) {
        $type = $snippet['type'];
        if ( $type === 'css' ) {
            return $this->execute_static_code( $snippet, $type );
        }
        if ( $type === 'js' ) {
            return $this->execute_static_code( $snippet, $type );
        }
        if ( $type === 'html' ) {
            return $this->execute_html_code( $snippet );
        }
        return null;
    }

    /**
     * Execute JavaScript or CSS code
     *
     * @param $snippet
     * @param $type
     *
     * @return null
     * @since 1.7.0
     *
     */
    private function execute_static_code( $snippet, $type ) {
        $code = trim( $this->strip_wrapper_tags( $snippet['code'], $type ) );
        if ( empty( $code ) ) {
            return null;
        }
        $location = $snippet['location'];
        if ( $location == 'inline' ) {
            if ( $type === 'css' ) {
                return '<style>' . wp_unslash( $code ) . '</style>';
                // @codingStandardsIgnoreLine.
            } else {
                if ( $type === 'js' ) {
                    return '<script>' . wp_unslash( $code ) . '</script>';
                    // @codingStandardsIgnoreLine.
                }
            }
        }
        $priority = $snippet['priority'];
        $handle = 'plover-kit-snippet-' . $snippet['id'];
        if ( $location === 'admin' || $location === 'everywhere' ) {
            $this->add_inline_script(
                'admin_enqueue_scripts',
                $type,
                $code,
                $handle,
                $priority
            );
        }
        if ( $location === 'frontend' || $location === 'everywhere' ) {
            $this->add_inline_script(
                'wp_enqueue_scripts',
                $type,
                $code,
                $handle,
                $priority
            );
        }
        return null;
    }

    /**
     * Add inline script
     *
     * @param $hook
     * @param $type
     * @param $code
     * @param $handle
     * @param $priority
     *
     * @return void
     * @since 1.7.0
     *
     */
    private function add_inline_script(
        $hook,
        $type,
        $code,
        $handle,
        $priority
    ) {
        add_action( $hook, function () use($code, $type, $handle) {
            if ( $type === 'css' ) {
                wp_register_style( $handle, false );
                wp_enqueue_style( $handle );
                wp_add_inline_style( $handle, $code );
            } else {
                if ( $type === 'js' ) {
                    wp_register_script( $handle, false );
                    wp_enqueue_script( $handle );
                    wp_add_inline_script( $handle, $code );
                }
            }
        }, $priority );
    }

    /**
     * Execute html code
     *
     * @param $snippet
     *
     * @return false|void
     * @since 1.7.0
     *
     */
    private function execute_html_code( $snippet ) {
        $code = trim( $snippet['code'] );
        if ( empty( $code ) || $snippet['type'] !== 'html' ) {
            return false;
        }
        // Output.
        return wp_unslash( $code ) . PHP_EOL;
        // @codingStandardsIgnoreLine.
    }

    /**
     * Strip <script> or <style> tag base on code type
     *
     * @param $code
     * @param $code_type
     *
     * @return string
     * @since 1.7.0
     *
     */
    private function strip_wrapper_tags( $code, $code_type ) {
        switch ( $code_type ) {
            case 'css':
                return preg_replace( '/<\\s*style[^>]*>|<\\s*\\/\\s*style\\s*>/i', '', $code );
            case 'js':
                return preg_replace( '/<\\s*script[^>]*>|<\\s*\\/\\s*script\\s*>/i', '', $code );
            default:
                return $code;
        }
    }

    /**
     * Invalidate snippets cache on delete
     * @return void
     * @since 1.7.0
     *
     */
    public function invalidate_snippets_cache_on_delete( $post_id ) {
        if ( get_post_type( $post_id ) === self::CODE_SNIPPET_POST_TYPE ) {
            Cache::delete( 'plover_code_snippets_cache_version' );
        }
    }

    /**
     * Invalidate snippets cache on update or create
     * @return void
     * @since 1.7.0
     *
     */
    public function invalidate_snippets_cache() {
        Cache::delete( 'plover_code_snippets_cache_version' );
    }

    /**
     * @return false|int|mixed
     * @since 1.7.0
     */
    private function get_snippets_cache_version() {
        $version = Cache::get( 'plover_code_snippets_cache_version' );
        if ( $version ) {
            $parts = explode( '|', $version, 2 );
            if ( !isset( $parts[1] ) || $parts[1] !== PLOVER_KIT_VERSION ) {
                $version = null;
            }
        }
        if ( !$version ) {
            $version = time() . '|' . PLOVER_KIT_VERSION;
            Cache::set( 'plover_code_snippets_cache_version', $version );
        }
        return $version;
    }

    /**
     * Register code snippet post type
     *
     * @return void
     */
    public function register_post_types() {
        register_post_type( self::CODE_SNIPPET_POST_TYPE, array(
            'labels'                         => array(
                'name'          => __( 'Code Snippets', 'plover-kit' ),
                'singular_name' => __( 'Code Snippet', 'plover-kit' ),
            ),
            'public'                         => false,
            'show_ui'                        => false,
            'hierarchical'                   => false,
            'capabilities'                   => array(
                'read'                   => 'manage_options',
                'read_private_posts'     => 'manage_options',
                'create_posts'           => 'manage_options',
                'publish_posts'          => 'manage_options',
                'edit_posts'             => 'manage_options',
                'edit_others_posts'      => 'manage_options',
                'edit_published_posts'   => 'manage_options',
                'delete_posts'           => 'manage_options',
                'delete_others_posts'    => 'manage_options',
                'delete_published_posts' => 'manage_options',
            ),
            'map_meta_cap'                   => true,
            'query_var'                      => false,
            'rewrite'                        => false,
            'show_in_rest'                   => true,
            'rest_base'                      => 'code-snippets',
            'rest_namespace'                 => 'plover-kit/v1',
            'rest_controller_class'          => CodeSnippetsController::class,
            'supports'                       => array(
                'title',
                'editor',
                'author',
                'custom-fields'
            ),
            'autosave_rest_controller_class' => 'stdClass',
        ) );
        foreach ( $this->get_post_metas_schema() as $meta_key => $meta_options ) {
            register_post_meta( self::CODE_SNIPPET_POST_TYPE, $meta_key, array_merge( $meta_options, array(
                'auth_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ) ) );
        }
    }

    /**
     * Register blocks
     *
     * @return void
     */
    public function register_blocks() {
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/code-snippet' ), array(
            'render_callback' => array($this, 'render_code_snippet_block'),
        ) );
    }

    /**
     * Execute code snippet block
     *
     * @param $attributes
     *
     * @return string
     * @since 1.7.0
     *
     */
    public function render_code_snippet_block( $attributes, $content, $block ) {
        return '';
    }

    /**
     * @param $snippet
     *
     * @return bool
     * @since 1.7.0
     */
    private function should_execute_code_snippet( $snippet ) {
        $should_execute = true;
        // Don't run inline code snippet
        if ( $should_execute && ($snippet['location'] ?? '') === 'inline' ) {
            $should_execute = false;
        }
        return apply_filters( 'plover-kit/should_execute_snippet', $should_execute, $snippet );
    }

    /**
     * Preset priority
     *
     * @return array[]|int
     */
    private function get_priority( $slug = null ) {
        $options = array(
            'very_high' => array(
                'value' => 1,
                'label' => __( 'Very High', 'plover-kit' ),
            ),
            'high'      => array(
                'value' => 5,
                'label' => __( 'High', 'plover-kit' ),
            ),
            'normal'    => array(
                'value' => 10,
                'label' => __( 'Normal', 'plover-kit' ),
            ),
            'low'       => array(
                'value' => 20,
                'label' => __( 'Low', 'plover-kit' ),
            ),
            'very_low'  => array(
                'value' => PHP_INT_MAX,
                'label' => __( 'Very Low', 'plover-kit' ),
            ),
        );
        if ( $slug === null ) {
            return $options;
        }
        return ( isset( $options[$slug] ) ? $options[$slug]['value'] : 10 );
    }

    /**
     * All post metas schema
     *
     * @return array[]
     */
    private function get_post_metas_schema() {
        $schema = array(
            'plover_kit_code_snippet_type'     => array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'default'           => 'html',
                'sanitize_callback' => function ( $value ) {
                    $allowed = array(
                        'html',
                        'css',
                        'js',
                        'php'
                    );
                    return ( in_array( $value, $allowed ) ? $value : 'html' );
                },
            ),
            'plover_kit_code_snippet_location' => array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => function ( $value ) {
                    $allowed = array(
                        'header',
                        'footer',
                        'frontend',
                        'admin',
                        'everywhere',
                        'inline'
                    );
                    return ( in_array( $value, $allowed ) ? $value : '' );
                },
            ),
            'plover_kit_code_snippet_priority' => array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'default'           => 'normal',
                'sanitize_callback' => function ( $value ) {
                    $priority = $this->get_priority();
                    return ( isset( $priority[$value] ) ? $value : 'normal' );
                },
            ),
        );
        return $schema;
    }

}
