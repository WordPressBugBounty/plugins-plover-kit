<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
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
        add_action( 'init', [$this, 'register_post_types'] );
        add_action( 'init', [$this, 'register_blocks'] );
        // Disable gutenberg for code snippets
        add_filter(
            'use_block_editor_for_post_type',
            [$this, 'disable_gutenberg_for_code_snippets'],
            10,
            2
        );
        // Add custom meta boxes
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'save_post', array($this, 'save_meta_boxes') );
        // Output frontend scripts
        foreach ( $this->get_priority_options() as $priority_slug => $priority_option ) {
            add_action( 'wp_head', function () use($priority_slug, $priority_option) {
                $this->wp_head_code_snippets( $priority_slug );
            }, $priority_option['value'] );
            add_action( 'wp_footer', function () use($priority_slug, $priority_option) {
                $this->wp_footer_code_snippets( $priority_slug );
            }, $priority_option['value'] );
        }
    }

    /**
     * Register code snippet post type
     *
     * @return void
     */
    public function register_post_types() {
        register_post_type( self::CODE_SNIPPET_POST_TYPE, array(
            'labels'       => array(
                'name'          => __( 'Code Snippets', 'plover-kit' ),
                'singular_name' => __( 'Code Snippet', 'plover-kit' ),
            ),
            'public'       => false,
            'show_ui'      => plover_kit_is_debug() && current_user_can( 'manage_options' ),
            'hierarchical' => false,
            'capabilities' => array(
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
            'map_meta_cap' => true,
            'query_var'    => false,
            'rewrite'      => false,
            'show_in_rest' => true,
            'supports'     => array(
                'title',
                'editor',
                'author',
                'custom-fields'
            ),
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
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/code-snippet' ) );
    }

    /**
     * @param $current_status
     * @param $post_type
     *
     * @return false
     */
    public function disable_gutenberg_for_code_snippets( $current_status, $post_type ) {
        if ( $post_type === self::CODE_SNIPPET_POST_TYPE ) {
            return false;
        }
        return $current_status;
    }

    /**
     * Add meta boxes for code snippet post type, debug only
     *
     * @return void
     */
    public function add_meta_boxes() {
        $screen = self::CODE_SNIPPET_POST_TYPE;
        add_meta_box(
            'plover_kit_code_snippet_attributes',
            __( 'Code Snippets Attributes', 'plover-kit' ),
            array($this, 'render_meta_box'),
            $screen,
            'side'
        );
    }

    /**
     * Save meta boxes
     *
     * @param $post_id
     *
     * @return void
     */
    public function save_meta_boxes( $post_id ) {
        foreach ( $this->get_post_metas_schema() as $meta_key => $meta_options ) {
            if ( array_key_exists( $meta_key, $_POST ) ) {
                update_post_meta( $post_id, $meta_key, $_POST[$meta_key] );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    /**
     * Render code snippets meta boxes
     *
     * @param $post
     *
     * @return void
     */
    public function render_meta_box( $post ) {
        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'plover_kit_code_snippet_attributes', 'plover_kit_code_snippet_attributes_nonce' );
        // Use get_post_meta to retrieve an existing value from the database.
        $location = get_post_meta( $post->ID, 'plover_kit_code_snippet_location', true );
        $priority = get_post_meta( $post->ID, 'plover_kit_code_snippet_priority', true );
        // Display the form, using the current value.
        ?>
        <p>
            <label for="plover_kit_code_snippet_location"
                   style="display: block; font-weight: bold; margin-bottom: 4px">
				<?php 
        esc_html_e( 'Location', 'plover-kit' );
        ?>
            </label>
            <select name="plover_kit_code_snippet_location" style="width: 100%">
                <option value="header" <?php 
        selected( 'header', esc_attr( $location ) );
        ?>>
					<?php 
        esc_html_e( 'Header', 'plover-kit' );
        ?>
                </option>
                <option value="footer" <?php 
        selected( 'footer', esc_attr( $location ) );
        ?>>
					<?php 
        esc_html_e( 'Footer', 'plover-kit' );
        ?>
                </option>
            </select>
        </p>

        <p>
            <label for="plover_kit_code_snippet_priority"
                   style="display: block; font-weight: bold; margin-bottom: 4px">
				<?php 
        esc_html_e( 'Priority', 'plover-kit' );
        ?>
            </label>
            <select name="plover_kit_code_snippet_priority" style="width: 100%">
				<?php 
        foreach ( $this->get_priority_options() as $slug => $priority_option ) {
            ?>
                    <option value="<?php 
            echo esc_attr( $slug );
            ?>" <?php 
            selected( esc_attr( $slug ), esc_attr( $priority ) );
            ?>>
						<?php 
            echo esc_html( $priority_option['label'] );
            ?>
                    </option>
				<?php 
        }
        ?>
            </select>
            <span style="font-size: 12px; opacity: 0.85">
				<?php 
        esc_html_e( 'Used to specify the order in which code snippets are output, the higher the priority the earlier they are output.', 'plover-kit' );
        ?>
            </span>
        </p>
		<?php 
    }

    /**
     * Output header code snippets
     *
     * @param $priority
     *
     * @return void
     */
    public function wp_head_code_snippets( $priority ) {
        /**
         * Filter to add or exclude code snippets to and from the frontend header.
         *
         * @since 1.2.0
         */
        if ( apply_filters( 'plover-kit/header_code_snippets', true ) ) {
            if ( apply_filters( 'plover-kit/header_code_snippets_priority_' . $priority, true ) ) {
                $this->print_code_snippets( 'header', $priority );
            }
        }
    }

    /**
     * Output footer code snippets
     *
     * @param $priority
     *
     * @return void
     */
    public function wp_footer_code_snippets( $priority ) {
        /**
         * Filter to add or exclude code snippets to and from the frontend header.
         *
         * @since 1.2.0
         */
        if ( apply_filters( 'plover-kit/footer_code_snippets', true ) ) {
            if ( apply_filters( 'plover-kit/footer_code_snippets_priority_' . $priority, true ) ) {
                $this->print_code_snippets( 'footer', $priority );
            }
        }
    }

    /**
     * Print code snippets for given location
     *
     * @param $location
     *
     * @return void
     */
    protected function print_code_snippets( $location, $priority ) {
        // Ignore admin, feed, robots or track backs.
        if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
            return;
        }
        $meta_query = array();
        $post_metas = $this->get_post_metas_schema();
        $metas = [
            'plover_kit_code_snippet_location' => $location,
            'plover_kit_code_snippet_priority' => $priority,
        ];
        foreach ( $metas as $meta_key => $meta_value ) {
            if ( $meta_value === $post_metas[$meta_key]['default'] ) {
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => $meta_key,
                        'value'   => '',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => $meta_key,
                        'value'   => $meta_value,
                        'compare' => '=',
                    ),
                );
            } else {
                $meta_query[] = array(
                    'key'     => $meta_key,
                    'value'   => $meta_value,
                    'compare' => '=',
                );
            }
        }
        $args = array(
            'posts_per_page' => -1,
            'orderby'        => 'post_date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'post_type'      => self::CODE_SNIPPET_POST_TYPE,
            'meta_query'     => $meta_query,
        );
        $snippets = get_posts( $args );
        $script = '';
        foreach ( $snippets as $snippet ) {
            if ( $this->should_print_code_snippet( $snippet ) ) {
                $script .= $snippet->post_content;
            }
        }
        if ( '' === trim( $script ) || !$script ) {
            return;
        }
        // Output.
        echo wp_unslash( $script ) . PHP_EOL;
        // @codingStandardsIgnoreLine.
    }

    /**
     * @param $snippet
     *
     * @return bool
     */
    protected function should_print_code_snippet( $snippet ) {
        return true;
    }

    /**
     * Preset priority
     *
     * @return array[]
     */
    protected function get_priority_options() {
        return array(
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
    }

    /**
     * All post metas schema
     *
     * @return array[]
     */
    protected function get_post_metas_schema() {
        $schema = array(
            'plover_kit_code_snippet_location' => array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
                'default'      => 'header',
            ),
            'plover_kit_code_snippet_priority' => array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
                'default'      => 'normal',
            ),
        );
        return $schema;
    }

}
