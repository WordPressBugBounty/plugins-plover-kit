<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\StyleEngine;
/**
 * @since 1.6.5
 */
class ReadingProgress extends Extension {
    /**
     * Module name
     */
    const MODULE_NAME = 'plover_reading_progress';

    /**
     * @var null|bool
     */
    protected $show_reading_progress = null;

    /**
     * @return void
     */
    public function register() {
        $effects = array('rounded', 'striped');
        $base_fields = array(
            'preview'        => array(
                'label'   => __( 'Preview', 'plover-kit' ),
                'control' => Control::T_INLINE_PLACEHOLDER,
            ),
            'effects'        => array(
                'label'        => __( 'Progress bar effects', 'plover-kit' ),
                'default'      => array('rounded'),
                'control'      => Control::T_TAGS,
                'upsell'       => sprintf( _x( 'Upgrade to the %s edition to access additional pulse, glowy and breath effects.', '%s is upsell text and link', 'plover-kit' ), '<a class="accent" target="_blank" href="' . plover_upsell_url( [
                    'utm_campaign' => 'reading_progress_setting',
                ] ) . '">' . __( 'Premium', 'plover-kit' ) . '</a>' ),
                'control_args' => array(
                    'suggestions' => $effects,
                ),
            ),
            'size'           => array(
                'label'        => __( 'Progress bar thickness (pixels)', 'plover-kit' ),
                'default'      => 6,
                'control'      => Control::T_NUMBER,
                'control_args' => array(
                    'min' => 1,
                ),
            ),
            'progress_color' => array(
                'label'        => __( 'Progress bar color', 'plover-kit' ),
                'default'      => array(
                    'indicator' => 'linear-gradient(135deg,rgb(6,147,228) 0%,rgb(155,81,224) 100%)',
                    'track'     => 'transparent',
                ),
                'control'      => Control::T_COLORS,
                'control_args' => array(
                    'colors' => array(array(
                        'id'       => 'indicator',
                        'label'    => __( 'Indicator', 'plover-kit' ),
                        'gradient' => true,
                    ), array(
                        'id'       => 'track',
                        'label'    => __( 'Track', 'plover-kit' ),
                        'gradient' => true,
                    )),
                ),
            ),
        );
        $position_fields = array(
            'position_divider' => array(
                'is_setting' => false,
                'control'    => Control::T_DIVIDER,
            ),
            'position'         => array(
                'label'        => __( 'Progress bar position', 'plover-kit' ),
                'default'      => 'top',
                'control'      => Control::T_SELECT,
                'control_args' => array(
                    'options' => array(array(
                        'label' => __( 'Top', 'plover-kit' ),
                        'value' => 'top',
                    ), array(
                        'label' => __( 'Bottom', 'plover-kit' ),
                        'value' => 'bottom',
                    )),
                ),
                'upsell'       => sprintf( _x( 'Upgrade to the %s edition to sticky the progress bar to any element and detect the scroll progress of any scrollable element.', '%s is upsell text and link', 'plover-kit' ), '<a class="accent" target="_blank" href="' . plover_upsell_url( [
                    'utm_campaign' => 'reading_progress_setting',
                ] ) . '">' . __( 'Premium', 'plover-kit' ) . '</a>' ),
            ),
        );
        $percentage_fields = array(
            'percentage_divider'       => array(
                'is_setting' => false,
                'control'    => Control::T_DIVIDER,
            ),
            'percentage'               => array(
                'label'   => __( 'Show percentage text', 'plover-kit' ),
                'help'    => __( 'Display the percentage text in the progress bar.', 'plover-kit' ),
                'default' => 'no',
                'control' => Control::T_SWITCH,
            ),
            'percentage_text_color'    => array(
                'label'        => __( 'Percentage text Color', 'plover-kit' ),
                'default'      => array(
                    'text' => '#000000',
                ),
                'control'      => Control::T_COLORS,
                'control_args' => array(
                    'colors' => array(array(
                        'id'    => 'text',
                        'label' => __( 'Text', 'plover-kit' ),
                    )),
                ),
            ),
            'percentage_text_position' => array(
                'label'        => __( 'Percentage text position', 'plover-kit' ),
                'default'      => 'right',
                'control'      => Control::T_SELECT,
                'control_args' => array(
                    'options' => array(
                        array(
                            'label' => __( 'Left', 'plover-kit' ),
                            'value' => 'left',
                        ),
                        array(
                            'label' => __( 'Right', 'plover-kit' ),
                            'value' => 'right',
                        ),
                        array(
                            'label' => __( 'Left Inner', 'plover-kit' ),
                            'value' => 'left-inner',
                        ),
                        array(
                            'label' => __( 'Right Inner', 'plover-kit' ),
                            'value' => 'right-inner',
                        )
                    ),
                ),
            ),
        );
        $this->modules->register( self::MODULE_NAME, array(
            'recent'  => true,
            'enabled' => 'no',
            'label'   => __( 'Reading Progress', 'plover-kit' ),
            'excerpt' => __( 'Track the visitor\'s reading percentage, so the visitor always know where they stand.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/reading-progress.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/reading-progress',
            'fields'  => array_merge( $base_fields, $position_fields, $percentage_fields ),
            'group'   => 'blog-tools',
        ) );
        add_action( 'init', array($this, 'register_meta_settings'), 99 );
    }

    /**
     * Bootstrap the extension.
     *
     * @return void
     */
    public function boot() {
        // dashboard assets should always be queued.
        $this->scripts->enqueue_dashboard_asset( 'plover-reading-progress', array(
            'src'   => plover_kit()->app_url( 'assets/js/block-extensions/reading-progress/index.min.js' ),
            'path'  => plover_kit()->app_path( 'assets/js/block-extensions/reading-progress/index.min.js' ),
            'ver'   => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'asset' => plover_kit()->app_path( 'assets/js/block-extensions/reading-progress/index.min.asset.php' ),
        ) );
        $this->styles->enqueue_dashboard_asset( 'plover-reading-progress', array(
            'ver'  => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'rtl'  => 'replace',
            'src'  => plover_kit()->app_url( 'assets/js/frontend/reading-progress/style.min.css' ),
            'path' => plover_kit()->app_path( 'assets/js/frontend/reading-progress/style.min.css' ),
        ) );
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        // Block editor sidebar settings
        $this->scripts->enqueue_editor_asset( 'plover-reading-progress-meta-settings', array(
            'src'   => plover_kit()->app_url( 'assets/js/block-extensions/reading-progress/block-editor-sidebar/index.min.js' ),
            'path'  => plover_kit()->app_path( 'assets/js/block-extensions/reading-progress/block-editor-sidebar/index.min.js' ),
            'ver'   => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'asset' => plover_kit()->app_path( 'assets/js/block-extensions/reading-progress/block-editor-sidebar/index.min.asset.php' ),
        ) );
        // frontend script and style
        $this->scripts->enqueue_asset( 'plover-reading-progress', array(
            'ver'       => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'src'       => plover_kit()->app_url( 'assets/js/frontend/reading-progress/index.min.js' ),
            'path'      => plover_kit()->app_path( 'assets/js/frontend/reading-progress/index.min.js' ),
            'asset'     => plover_kit()->app_path( 'assets/js/frontend/reading-progress/index.min.asset.php' ),
            'condition' => [$this, 'should_show_reading_progress'],
        ) );
        $this->styles->enqueue_asset( 'plover-reading-progress', array(
            'ver'       => ( $this->core->is_debug() ? time() : PLOVER_KIT_VERSION ),
            'rtl'       => 'replace',
            'src'       => plover_kit()->app_url( 'assets/js/frontend/reading-progress/style.min.css' ),
            'path'      => plover_kit()->app_path( 'assets/js/frontend/reading-progress/style.min.css' ),
            'condition' => [$this, 'should_show_reading_progress'],
        ) );
        // Render reading progress html structure
        add_action( 'wp_body_open', array($this, 'render_reading_progress') );
    }

    /**
     * Register reading progress post meta settings
     *
     * @return void
     */
    public function register_meta_settings() {
        $post_types = array('post', 'page');
        $this->modules->add_field( self::MODULE_NAME, 'scope_divider', array(
            'is_setting' => false,
            'control'    => Control::T_DIVIDER,
        ) );
        $this->modules->add_field( self::MODULE_NAME, 'post_types', array(
            'label'        => __( 'Show progress bar for these post types', 'plover-kit' ),
            'help'         => __( 'Display the reading progress bar only for the selected post types.', 'plover-kit' ),
            'default'      => array('post', 'page'),
            'control'      => Control::T_TAGS,
            'control_args' => array(
                'suggestions' => $post_types,
            ),
            'upsell'       => sprintf( _x( 'Upgrade to the %s edition to display the progress bars on custom post types, homepage, the blog page and archive pages.', '%s is upsell text and link', 'plover-kit' ), '<a class="accent" target="_blank" href="' . plover_upsell_url( [
                'utm_campaign' => 'reading_progress_setting',
            ] ) . '">' . __( 'Premium', 'plover-kit' ) . '</a>' ),
        ) );
    }

    /**
     * Should show reading progress or not
     *
     * @return bool
     */
    public function should_show_reading_progress() {
        // For the same page, the results remain the same,
        // caching is used to reduce redundant calculations.
        if ( $this->show_reading_progress !== null ) {
            return $this->show_reading_progress;
        }
        $allowed_post_types = $this->settings->get( self::MODULE_NAME, 'post_types', array() );
        $allowed_post_types = array_intersect( $allowed_post_types, array('post', 'page') );
        $current_page_type = plover_kit_get_current_page_type();
        $this->show_reading_progress = in_array( $current_page_type, $allowed_post_types );
        return $this->show_reading_progress;
    }

    /**
     * Render reading progress html structure
     *
     * @return void
     */
    public function render_reading_progress() {
        if ( !$this->should_show_reading_progress() ) {
            return;
        }
        $effects = $this->settings->get( self::MODULE_NAME, 'effects' );
        $size = $this->settings->get( self::MODULE_NAME, 'size' );
        $colors = $this->settings->get( self::MODULE_NAME, 'progress_color' );
        $percentage = $this->settings->checked( self::MODULE_NAME, 'percentage' );
        $percentage_text_color = $this->settings->get( self::MODULE_NAME, 'percentage_text_color' );
        $percentage_text_position = $this->settings->get( self::MODULE_NAME, 'percentage_text_position' );
        $config = array(
            'position' => '',
        );
        if ( empty( $config['position'] ) || !in_array( $config['position'], array('top', 'bottom') ) ) {
            $config['position'] = $this->settings->get( self::MODULE_NAME, 'position' );
        }
        $styles = array(
            '--size'       => max( 1, absint( $size ) ) . 'px',
            '--track'      => $colors['track'] ?? '',
            '--indicator'  => $colors['indicator'] ?? '',
            '--text-color' => $percentage_text_color['text'] ?? '',
        );
        $inline_style = StyleEngine::compile_css( $styles );
        $classes = ( empty( $effects ) ? '' : ' is-' . implode( ' is-', $effects ) );
        ?>
        <div class="plover-reading-progress plover-reading-progress--track"
             style="<?php 
        echo esc_attr( $inline_style );
        ?>"
             data-reading-progress="<?php 
        echo esc_attr( wp_json_encode( $config ) );
        ?>">
            <div class="plover-reading-progress--indicator<?php 
        echo esc_attr( $classes );
        ?>">
				<?php 
        if ( in_array( 'striped', $effects ) ) {
            ?>
                    <span class="striped"></span>
				<?php 
        }
        ?>
				<?php 
        ?>
				<?php 
        if ( $percentage ) {
            ?>
                    <span class="<?php 
            echo esc_attr( "percentage placement-{$percentage_text_position} align-{$config['position']}" );
            ?>"></span>
				<?php 
        }
        ?>
            </div>
        </div>
		<?php 
    }

}
