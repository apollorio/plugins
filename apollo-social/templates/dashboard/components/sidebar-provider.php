<?php
/**
 * ShadCN UI Sidebar Provider Component (New York Style)
 *
 * Provides the sidebar context and layout wrapper for the Apollo Dashboard.
 * Based on shadcn/ui sidebar component with New York variant.
 *
 * Usage:
 *   <?php apollo_sidebar_provider_start($args); ?>
 *     <!-- sidebar + content -->
 *   <?php apollo_sidebar_provider_end(); ?>
 *
 * @package    ApolloSocial
 * @subpackage Dashboard/Components
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Start the Sidebar Provider wrapper.
 *
 * @param array $args {
 *     Configuration options.
 *     @type string $sidebar_width   CSS width for sidebar. Default 'calc(var(--spacing) * 72)'.
 *     @type string $header_height   CSS height for header. Default 'calc(var(--spacing) * 12)'.
 *     @type string $default_open    Initial sidebar state. Default 'true'.
 *     @type string $class           Additional CSS classes.
 * }
 */
function apollo_sidebar_provider_start( array $args = array() ) {
    $defaults = array(
        'sidebar_width'  => 'calc(var(--spacing) * 72)',
        'header_height'  => 'calc(var(--spacing) * 12)',
        'default_open'   => 'true',
        'class'          => '',
    );
    $args = wp_parse_args( $args, $defaults );

    $style_vars = sprintf(
        '--sidebar-width: %s; --header-height: %s;',
        esc_attr( $args['sidebar_width'] ),
        esc_attr( $args['header_height'] )
    );

    $classes = 'apollo-sidebar-provider group/sidebar-wrapper flex min-h-svh w-full';
    if ( ! empty( $args['class'] ) ) {
        $classes .= ' ' . esc_attr( $args['class'] );
    }
    ?>
    <div 
        class="<?php echo esc_attr( $classes ); ?>"
        style="<?php echo esc_attr( $style_vars ); ?>"
        data-sidebar-state="<?php echo esc_attr( $args['default_open'] === 'true' ? 'expanded' : 'collapsed' ); ?>"
        data-sidebar-collapsible="icon"
    >
    <?php
}

/**
 * End the Sidebar Provider wrapper.
 */
function apollo_sidebar_provider_end() {
    ?>
    </div>
    <?php
}

/**
 * Render the Sidebar Inset container (main content area).
 *
 * @param array $args Configuration options.
 */
function apollo_sidebar_inset_start( array $args = array() ) {
    $defaults = array(
        'class' => '',
    );
    $args = wp_parse_args( $args, $defaults );

    $classes = 'apollo-sidebar-inset relative flex min-h-svh flex-1 flex-col bg-background';
    $classes .= ' peer-data-[variant=inset]:min-h-[calc(100svh-theme(spacing.4))]';
    $classes .= ' md:peer-data-[variant=inset]:m-2 md:peer-data-[variant=inset]:ml-0';
    $classes .= ' md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow-sm';
    $classes .= ' md:peer-data-[state=collapsed]:peer-data-[variant=inset]:ml-2';

    if ( ! empty( $args['class'] ) ) {
        $classes .= ' ' . esc_attr( $args['class'] );
    }
    ?>
    <main class="<?php echo esc_attr( $classes ); ?>">
    <?php
}

/**
 * End the Sidebar Inset container.
 */
function apollo_sidebar_inset_end() {
    ?>
    </main>
    <?php
}
