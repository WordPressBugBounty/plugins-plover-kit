<?php

namespace Plover\Kit\Extensions;

use Plover\Core\Services\Blocks\Blocks;
use Plover\Core\Services\Extensions\Contract\Extension;
use Plover\Core\Services\Settings\Control;
use Plover\Core\Toolkits\Html\Document;
use Plover\Core\Toolkits\StyleEngine;
/**
 * @since 1.3.0
 */
class TableOfContents extends Extension {
    const MODULE_NAME = 'plover_toc';

    const TOC_BLOCK_NAME = 'plover-kit/toc';

    /**
     * Extension register
     *
     * @return void
     * @throws \Exception
     */
    public function register() {
        $fields = array(
            'smooth_scrolling' => array(
                'label'   => __( 'Force Smooth scrolling', 'plover-kit' ),
                'help'    => __( 'Click on the table of contents link and the window scrolls smoothly to the target.', 'plover-kit' ),
                'default' => 'yes',
                'control' => Control::T_SWITCH,
            ),
            'upsell'           => array(
                'control' => Control::T_PLACEHOLDER,
            ),
        );
        $this->modules->register( self::MODULE_NAME, array(
            'label'   => __( 'Table of Contents', 'plover-kit' ),
            'excerpt' => __( 'Introduce a Table of Contents block to your posts and pages.', 'plover-kit' ),
            'icon'    => esc_url( plover_kit()->app_url( 'assets/images/table-of-contents.png' ) ),
            'doc'     => 'https://wpplover.com/docs/plover-kit/modules/table-of-contents/',
            'fields'  => $fields,
        ) );
    }

    /**
     * Extension bootstrap
     *
     * @param Blocks $blocks
     *
     * @return void
     */
    public function boot( Blocks $blocks ) {
        // module is disabled.
        if ( !$this->settings->checked( self::MODULE_NAME ) ) {
            return;
        }
        if ( $this->settings->checked( self::MODULE_NAME, 'smooth_scrolling' ) ) {
            $this->styles->enqueue_asset( 'plover-kit-toc-smooth-scrolling-css', array(
                'raw'      => 'html{scroll-behavior: smooth}',
                'keywords' => ['wp-block-plover-kit-toc'],
            ) );
        }
        $blocks->extend_block_supports( self::TOC_BLOCK_NAME, [
            'ploverShadow' => [
                'text'            => true,
                'box'             => true,
                'defaultControls' => [
                    'text' => true,
                ],
            ],
        ] );
        add_action( 'init', [$this, 'register_blocks'] );
        add_filter(
            'render_block',
            [$this, 'add_anchor_to_heading'],
            11,
            2
        );
        add_filter( 'plover-kit/resolve_heading_block', [$this, 'resolve_heading_block'] );
        add_filter( 'plover_core_editor_data', [$this, 'localize_editor_data'] );
    }

    /**
     * Make sure all heading block has anchor.
     *
     * @param $block_content
     * @param $block
     *
     * @return string
     */
    public function add_anchor_to_heading( $block_content, $block ) {
        $know_headings = $this->known_heading_blocks();
        if ( !isset( $know_headings[$block['blockName']] ) ) {
            return $block_content;
        }
        $html = new Document($block_content);
        $heading = $html->get_element_by_tags_priority( [
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6'
        ] );
        if ( !$heading ) {
            return $block_content;
        }
        // add anchor
        if ( !$heading->get_attribute( 'id' ) ) {
            $heading_text = trim( $heading->get_dom_element()->textContent );
            $heading->set_attribute( 'id', $this->sanitize_title( $heading_text ) );
        }
        return $html->save_html();
    }

    /**
     * Register table of content block
     *
     * @return void
     */
    public function register_blocks() {
        register_block_type_from_metadata( plover_kit()->app_path( 'assets/js/toc' ), array(
            'render_callback' => [$this, 'render_block'],
        ) );
    }

    /**
     * TOC block server side render
     *
     * @param $attributes
     *
     * @return string
     */
    public function render_block( $attributes ) {
        $heading_levels = $attributes['headingLevels'] ?? array();
        if ( empty( $heading_levels ) ) {
            // No selected heading levels
            return '';
        }
        $headings = $this->get_post_headings( $heading_levels, $attributes['onlyIncludeCurrentPage'] ?? false );
        $heading_tree = $this->linear_to_nested_heading_list( $headings );
        if ( empty( $heading_tree ) ) {
            // No available headings
            return '';
        }
        $toc_html = $this->generate_toc( $heading_tree, $attributes );
        if ( empty( $toc_html ) ) {
            return '';
        }
        $wrap_tag = ( ($attributes['tagName'] ?? 'nav') === 'nav' ? 'nav' : 'div' );
        $gap = StyleEngine::get_block_gap_value( $attributes );
        $extra_attrs = [];
        $block_style = [];
        $block_classes = [];
        if ( $gap ) {
            $block_style['--plover--style--block-gap'] = $gap;
        }
        if ( $attributes['indent'] ?? true ) {
            $block_classes[] = 'has-indent';
        }
        if ( $wrap_tag === 'div' ) {
            $extra_attrs['role'] = 'navigation';
        }
        if ( !empty( $block_classes ) ) {
            $extra_attrs['class'] = implode( ' ', $block_classes );
        }
        if ( !empty( $block_style ) ) {
            $extra_attrs['style'] = StyleEngine::compile_css( $block_style );
        }
        $wrapper_attrs = get_block_wrapper_attributes( $extra_attrs );
        $pre_html = '<' . $wrap_tag . ' aria-label="' . __( 'Table of Contents', 'plover-kit' ) . '" ' . $wrapper_attrs . '>';
        $post_html = '</' . $wrap_tag . '>';
        return $pre_html . $toc_html . $post_html;
    }

    /**
     * Resolve known heading blocks
     *
     * @param $block
     *
     * @return array|false
     */
    public function resolve_heading_block( $block ) {
        $block_name = $block['blockName'] ?? null;
        $known_headings = $this->known_heading_blocks();
        if ( isset( $known_headings[$block_name] ) && isset( $block['innerHTML'] ) ) {
            if ( preg_match( "/(<h1|<h2|<h3|<h4|<h5|<h6)/i", $block['innerHTML'], $matches ) ) {
                $level = absint( substr( $matches[0], 2 ) );
                return array(
                    'html'  => $block['innerHTML'],
                    'level' => $level,
                );
            }
        }
        return false;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function localize_editor_data( $data ) {
        $toc_data = [
            'known_heading_blocks' => $this->known_heading_blocks(),
        ];
        $data['extensions']['toc'] = $toc_data;
        return $data;
    }

    /**
     * Known heading blocks name, extendable by developer
     *
     * @return array
     */
    protected function known_heading_blocks() {
        return apply_filters( 'plover-kit/toc_heading_blocks', [
            'core/heading'            => [
                'level'    => 'level',
                'content'  => 'content',
                'levelMap' => [
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                ],
            ],
            'generateblocks/headline' => [
                'level'    => 'element',
                'content'  => 'content',
                'levelMap' => [
                    'h1' => 1,
                    'h2' => 2,
                    'h3' => 3,
                    'h4' => 4,
                    'h5' => 5,
                    'h6' => 6,
                ],
            ],
            'kenta-blocks/heading'    => [
                'level'    => 'markup',
                'content'  => 'content',
                'levelMap' => [
                    'h1' => 1,
                    'h2' => 2,
                    'h3' => 3,
                    'h4' => 4,
                    'h5' => 5,
                    'h6' => 6,
                ],
            ],
        ] );
    }

    /**
     * Get all headings from current post
     *
     * @param $heading_levels
     * @param $only_current_page
     *
     * @return array
     */
    protected function get_post_headings( $heading_levels, $only_current_page ) {
        $post = get_post();
        $blocks = ( !is_null( $post ) && !is_null( $post->post_content ) ? parse_blocks( $post->post_content ) : '' );
        $current_page = max( absint( get_query_var( 'page' ) ), 1 );
        $headings = $this->retrieve_headings_form_blocks( $blocks );
        return array_values( 
            // reset index
            array_filter( $headings, function ( $heading ) use($heading_levels, $only_current_page, $current_page) {
                // skip unselected levels
                if ( !in_array( $heading['level'], $heading_levels ) ) {
                    return false;
                }
                // skip headings with ignore class
                preg_match( '/class="([^"]+)"/', $heading['html'], $matches );
                if ( !empty( $matches[1] ) && strpos( $matches[1], 'plover-kit-toc__hidden' ) !== false ) {
                    return false;
                }
                if ( $only_current_page ) {
                    // skip non-current page headings
                    return $heading['page'] === $current_page;
                }
                return true;
            } )
         );
    }

    /**
     * Retrieve all headings from blocks
     *
     * @param $blocks
     *
     * @return array
     */
    protected function retrieve_headings_form_blocks( $blocks, &$page = 1 ) {
        $headings = [];
        if ( !is_array( $blocks ) || empty( $blocks ) ) {
            return $headings;
        }
        $known_headings = $this->known_heading_blocks();
        foreach ( $blocks as $block ) {
            $block_name = $block['blockName'] ?? null;
            if ( $block_name === 'core/nextpage' ) {
                $page++;
            }
            if ( $block_name === 'core/block' && isset( $block['attrs']['ref'] ) ) {
                // search headings in reusable blocks
                $post = get_post( $block['attrs']['ref'] );
                if ( $post ) {
                    $reusable_blocks = parse_blocks( $post->post_content );
                    $headings = array_merge( $headings, $this->retrieve_headings_form_blocks( $reusable_blocks, $page ) );
                }
            } else {
                if ( !empty( $block['innerBlocks'] ) ) {
                    // search in inner blocks
                    $headings = array_merge( $headings, $this->retrieve_headings_form_blocks( $block['innerBlocks'], $page ) );
                }
            }
            // handle hading block
            if ( isset( $known_headings[$block_name] ) ) {
                $heading = apply_filters( 'plover-kit/resolve_heading_block', $block );
                if ( is_array( $heading ) && !empty( $heading ) ) {
                    $headings[] = array_merge( $heading, [
                        'page' => $page,
                    ] );
                }
            }
        }
        return $headings;
    }

    /**
     * Takes a flat list of heading parameters and nests them based on each header's
     * immediate parent's level.
     *
     * @param $headings
     *
     * @return array
     */
    protected function linear_to_nested_heading_list( $headings ) {
        if ( empty( $headings ) ) {
            return [];
        }
        $nested_headings = [];
        // We need to reset the initial position when the first title level is not the highest level
        $first_index = 0;
        foreach ( $headings as $index => $heading ) {
            if ( $heading['level'] <= $headings[$first_index]['level'] ) {
                // New group
                $first_index = $index;
                // Check that the next iteration will return a value.
                // If it does and the next level is greater than the current level,
                // the next iteration becomes a child of the current iteration.
                if ( ($headings[$index + 1]['level'] ?? 0) > $heading['level'] ) {
                    // We must calculate the last index before the next iteration that
                    // has the same level (siblings). We then use this index to slice
                    // the array for use in recursion. This prevents duplicate nodes.
                    $end_of_slice = count( $headings );
                    for ($i = $index + 1; $i < $end_of_slice; $i++) {
                        if ( $headings[$i]['level'] <= $heading['level'] ) {
                            $end_of_slice = $i;
                            break;
                        }
                    }
                    // We found a child node: Push a new node onto the return array
                    // with children.
                    $nested_headings[] = array(
                        'heading'  => $heading,
                        'children' => $this->linear_to_nested_heading_list( array_slice( $headings, $index + 1, $end_of_slice - $index - 1 ) ),
                    );
                } else {
                    // No child node: Push a new node onto the return array.
                    $nested_headings[] = array(
                        'heading'  => $heading,
                        'children' => null,
                    );
                }
            }
        }
        return $nested_headings;
    }

    /**
     * Generate table of contents
     *
     * @param $heading_tree
     * @param $attributes
     *
     * @return string
     */
    protected function generate_toc( $heading_tree, $attributes ) {
        $current_page = max( absint( get_query_var( 'page' ) ), 1 );
        $absolute_urls = $attributes['absoluteUrls'] ?? false;
        $list_tag = ( $attributes['ordered'] ? 'ol' : 'ul' );
        $permalink = get_permalink();
        $html = '<' . $list_tag . '>';
        foreach ( $heading_tree as $node ) {
            $heading = $node['heading'];
            $content = trim( strip_tags( $heading['html'] ) );
            $anchor = ( $this->extract_id( $heading['html'] ) ?: $this->sanitize_title( $content ) );
            $link = '';
            if ( $absolute_urls || $heading['page'] !== $current_page ) {
                $link = add_query_arg( [
                    'page' => $heading['page'],
                ], $permalink );
            }
            $html .= '<li><a class="plover-kit-toc__entry" href="' . $link . '#' . $anchor . '">' . $content . '</a>' . PHP_EOL;
            if ( !empty( $node['children'] ) ) {
                $html .= $this->generate_toc( $node['children'], $attributes );
            }
            $html .= '</li>' . PHP_EOL;
        }
        $html .= '</' . $list_tag . '>';
        return $html;
    }

    /**
     * @param $headline
     *
     * @return false|string
     */
    protected function extract_id( $headline ) {
        $pattern = '/id="([^"]*)"/';
        preg_match( $pattern, $headline, $matches );
        $id = $matches[1] ?? false;
        if ( $id != false ) {
            return $id;
        }
        return false;
    }

    /**
     * @param $string
     *
     * @return string
     */
    protected function sanitize_title( $string ) {
        // remove punctuation
        $zero_punctuation = preg_replace( "/\\p{P}/u", "", $string );
        // remove non-breaking spaces
        $html_wo_nbs = str_replace( "&nbsp;", " ", $zero_punctuation );
        // remove umlauts and accents
        $string_without_accents = remove_accents( $html_wo_nbs );
        // Sanitizes a title, replacing whitespace and a few other characters with dashes.
        $sanitized_string = sanitize_title_with_dashes( $string_without_accents );
        // Encode for use in an url
        $urlencoded = urlencode( $sanitized_string );
        return $urlencoded;
    }

}
