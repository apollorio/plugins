<?php
/**
 * Apollo Dashboard Layout - ShadCN New York Style
 *
 * Main dashboard layout template using ShadCN UI components.
 * Provides the full page structure with sidebar, header, and content area.
 *
 * Usage:
 *   Include this file and use apollo_render_dashboard_page() to render.
 *
 * Based on: shadcn/ui dashboard template (New York variant)
 * @see https://ui.shadcn.com/blocks/dashboard
 *
 * @package    ApolloSocial
 * @subpackage Dashboard
 * @since      1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load components
$components_dir = __DIR__ . '/components/';
require_once $components_dir . 'sidebar-provider.php';
require_once $components_dir . 'app-sidebar.php';
require_once $components_dir . 'site-header.php';
require_once $components_dir . 'section-cards.php';
require_once $components_dir . 'data-table.php';

/**
 * Render the complete dashboard page.
 *
 * @param array $args {
 *     Page configuration.
 *     @type string $title        Page title.
 *     @type array  $breadcrumbs  Breadcrumb items.
 *     @type array  $cards        Stats cards data.
 *     @type array  $table        Data table configuration.
 *     @type string $content      Custom content (alternative to cards/table).
 *     @type array  $sidebar_nav  Custom sidebar navigation.
 * }
 */
function apollo_render_dashboard_page( array $args = array() ) {
    $defaults = array(
        'title'       => 'Dashboard',
        'breadcrumbs' => array(
            array( 'label' => 'Início', 'url' => home_url( '/' ) ),
            array( 'label' => 'Dashboard', 'url' => '' ),
        ),
        'cards'       => array(),
        'table'       => array(),
        'content'     => '',
        'sidebar_nav' => array(),
    );
    $args = wp_parse_args( $args, $defaults );

    // Start output
    apollo_dashboard_head();
    ?>
    
    <?php apollo_sidebar_provider_start(); ?>
        
        <?php apollo_render_app_sidebar( array(
            'variant'   => 'inset',
            'nav_items' => ! empty( $args['sidebar_nav'] ) ? $args['sidebar_nav'] : apollo_get_default_nav_items(),
        ) ); ?>
        
        <?php apollo_sidebar_inset_start(); ?>
            
            <?php apollo_render_site_header( array(
                'breadcrumbs' => $args['breadcrumbs'],
                'title'       => $args['title'],
            ) ); ?>
            
            <div class="flex flex-1 flex-col">
                <div class="@container/main flex flex-1 flex-col gap-2">
                    <div class="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        
                        <?php if ( ! empty( $args['content'] ) ) : ?>
                            <!-- Custom Content -->
                            <div class="px-4 lg:px-6">
                                <?php echo $args['content']; // phpcs:ignore -- Custom content ?>
                            </div>
                        <?php else : ?>
                            
                            <!-- Stats Cards -->
                            <?php apollo_render_section_cards( $args['cards'] ); ?>
                            
                            <!-- Chart Area (Optional) -->
                            <?php if ( ! empty( $args['chart'] ) ) : ?>
                                <div class="px-4 lg:px-6">
                                    <?php apollo_render_chart_area( $args['chart'] ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Data Table -->
                            <?php if ( ! empty( $args['table'] ) ) : ?>
                                <?php apollo_render_data_table( $args['table'] ); ?>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
            
        <?php apollo_sidebar_inset_end(); ?>
        
    <?php apollo_sidebar_provider_end(); ?>
    
    <?php
    apollo_dashboard_footer();
}

/**
 * Output dashboard head/styles.
 */
function apollo_dashboard_head() {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> class="h-full">
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
        <?php wp_head(); ?>
        
        <!-- ShadCN CSS Variables (New York) -->
        <style>
            :root {
                /* Spacing */
                --spacing: 4px;
                
                /* Sidebar */
                --sidebar-width: calc(var(--spacing) * 72);
                --sidebar-width-collapsed: calc(var(--spacing) * 16);
                --header-height: calc(var(--spacing) * 12);
                
                /* New York Theme Colors */
                --background: 0 0% 100%;
                --foreground: 240 10% 3.9%;
                --card: 0 0% 100%;
                --card-foreground: 240 10% 3.9%;
                --popover: 0 0% 100%;
                --popover-foreground: 240 10% 3.9%;
                --primary: 240 5.9% 10%;
                --primary-foreground: 0 0% 98%;
                --secondary: 240 4.8% 95.9%;
                --secondary-foreground: 240 5.9% 10%;
                --muted: 240 4.8% 95.9%;
                --muted-foreground: 240 3.8% 46.1%;
                --accent: 240 4.8% 95.9%;
                --accent-foreground: 240 5.9% 10%;
                --destructive: 0 84.2% 60.2%;
                --destructive-foreground: 0 0% 98%;
                --border: 240 5.9% 90%;
                --input: 240 5.9% 90%;
                --ring: 240 5.9% 10%;
                --radius: 0.5rem;
                
                /* Sidebar specific */
                --sidebar: 0 0% 98%;
                --sidebar-foreground: 240 5.3% 26.1%;
                --sidebar-accent: 240 4.8% 95.9%;
                --sidebar-accent-foreground: 240 5.9% 10%;
                --sidebar-border: 240 5.9% 90%;
            }
            
            .dark {
                --background: 240 10% 3.9%;
                --foreground: 0 0% 98%;
                --card: 240 10% 3.9%;
                --card-foreground: 0 0% 98%;
                --popover: 240 10% 3.9%;
                --popover-foreground: 0 0% 98%;
                --primary: 0 0% 98%;
                --primary-foreground: 240 5.9% 10%;
                --secondary: 240 3.7% 15.9%;
                --secondary-foreground: 0 0% 98%;
                --muted: 240 3.7% 15.9%;
                --muted-foreground: 240 5% 64.9%;
                --accent: 240 3.7% 15.9%;
                --accent-foreground: 0 0% 98%;
                --destructive: 0 62.8% 30.6%;
                --destructive-foreground: 0 0% 98%;
                --border: 240 3.7% 15.9%;
                --input: 240 3.7% 15.9%;
                --ring: 240 4.9% 83.9%;
                
                --sidebar: 240 5.9% 10%;
                --sidebar-foreground: 240 4.8% 95.9%;
                --sidebar-accent: 240 3.7% 15.9%;
                --sidebar-accent-foreground: 240 4.8% 95.9%;
                --sidebar-border: 240 3.7% 15.9%;
            }
            
            /* Base styles */
            *, ::before, ::after {
                box-sizing: border-box;
                border-color: hsl(var(--border));
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: hsl(var(--background));
                color: hsl(var(--foreground));
                margin: 0;
                min-height: 100svh;
            }
            
            /* Color utilities */
            .bg-background { background-color: hsl(var(--background)); }
            .bg-foreground { background-color: hsl(var(--foreground)); }
            .bg-card { background-color: hsl(var(--card)); }
            .bg-popover { background-color: hsl(var(--popover)); }
            .bg-primary { background-color: hsl(var(--primary)); }
            .bg-secondary { background-color: hsl(var(--secondary)); }
            .bg-muted { background-color: hsl(var(--muted)); }
            .bg-accent { background-color: hsl(var(--accent)); }
            .bg-destructive { background-color: hsl(var(--destructive)); }
            .bg-sidebar { background-color: hsl(var(--sidebar)); }
            .bg-sidebar-accent { background-color: hsl(var(--sidebar-accent)); }
            
            .text-foreground { color: hsl(var(--foreground)); }
            .text-card-foreground { color: hsl(var(--card-foreground)); }
            .text-popover-foreground { color: hsl(var(--popover-foreground)); }
            .text-primary { color: hsl(var(--primary)); }
            .text-primary-foreground { color: hsl(var(--primary-foreground)); }
            .text-secondary-foreground { color: hsl(var(--secondary-foreground)); }
            .text-muted-foreground { color: hsl(var(--muted-foreground)); }
            .text-accent-foreground { color: hsl(var(--accent-foreground)); }
            .text-destructive { color: hsl(var(--destructive)); }
            .text-destructive-foreground { color: hsl(var(--destructive-foreground)); }
            .text-sidebar-foreground { color: hsl(var(--sidebar-foreground)); }
            .text-sidebar-accent-foreground { color: hsl(var(--sidebar-accent-foreground)); }
            
            .border-border { border-color: hsl(var(--border)); }
            .border-input { border-color: hsl(var(--input)); }
            
            .ring-ring { --tw-ring-color: hsl(var(--ring)); }
            
            /* Hover states */
            .hover\:bg-accent:hover { background-color: hsl(var(--accent)); }
            .hover\:bg-muted:hover { background-color: hsl(var(--muted)); }
            .hover\:bg-sidebar-accent:hover { background-color: hsl(var(--sidebar-accent)); }
            .hover\:bg-primary\/90:hover { background-color: hsl(var(--primary) / 0.9); }
            .hover\:bg-destructive\/10:hover { background-color: hsl(var(--destructive) / 0.1); }
            .hover\:text-foreground:hover { color: hsl(var(--foreground)); }
            .hover\:text-sidebar-accent-foreground:hover { color: hsl(var(--sidebar-accent-foreground)); }
            
            /* Focus states */
            .focus\:ring-ring:focus { --tw-ring-color: hsl(var(--ring)); }
            
            /* Transitions */
            .transition-colors {
                transition-property: color, background-color, border-color;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                transition-duration: 150ms;
            }
            
            /* Animations */
            @keyframes ping {
                75%, 100% { transform: scale(2); opacity: 0; }
            }
            .animate-ping {
                animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
            }
            
            /* Sidebar state transitions */
            .apollo-sidebar-provider[data-sidebar-state="collapsed"] .apollo-app-sidebar {
                width: var(--sidebar-width-collapsed);
            }
            
            .apollo-sidebar-provider[data-sidebar-state="collapsed"] [class*="group-data-[state=collapsed]:hidden"] {
                display: none !important;
            }
            
            /* Mobile sidebar */
            .apollo-sidebar-mobile[data-active="true"] {
                display: block;
            }
            
            /* Dropdown animations */
            .apollo-dropdown[data-active="true"] {
                display: block;
            }
            
            /* Container queries */
            @container main (min-width: 640px) {
                .\@xl\/main\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @container main (min-width: 1024px) {
                .\@5xl\/main\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }
            
            /* Custom Tooltips */
            [data-tooltip] {
                position: relative;
                cursor: help;
            }
            
            [data-tooltip]::before,
            [data-tooltip]::after {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.2s ease;
                pointer-events: none;
                z-index: 100;
            }
            
            [data-tooltip]::before {
                content: attr(data-tooltip);
                bottom: calc(100% + 8px);
                padding: 8px 12px;
                background: hsl(240 10% 3.9%);
                color: hsl(0 0% 98%);
                font-size: 0.75rem;
                font-weight: 500;
                line-height: 1.4;
                white-space: nowrap;
                border-radius: 6px;
                border: 1px solid hsl(240 3.7% 15.9%);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            [data-tooltip]::after {
                content: '';
                bottom: calc(100% + 4px);
                border: 4px solid transparent;
                border-top-color: hsl(240 10% 3.9%);
            }
            
            [data-tooltip]:hover::before,
            [data-tooltip]:hover::after {
                opacity: 1;
                visibility: visible;
            }
            
            /* Tooltip positions */
            [data-tooltip-position="bottom"]::before {
                bottom: auto;
                top: calc(100% + 8px);
            }
            
            [data-tooltip-position="bottom"]::after {
                bottom: auto;
                top: calc(100% + 4px);
                border-top-color: transparent;
                border-bottom-color: hsl(240 10% 3.9%);
            }
            
            [data-tooltip-position="left"]::before {
                left: auto;
                right: calc(100% + 8px);
                bottom: auto;
                top: 50%;
                transform: translateY(-50%);
            }
            
            [data-tooltip-position="left"]::after {
                left: auto;
                right: calc(100% + 4px);
                bottom: auto;
                top: 50%;
                transform: translateY(-50%);
                border-top-color: transparent;
                border-left-color: hsl(240 10% 3.9%);
            }
            
            [data-tooltip-position="right"]::before {
                left: calc(100% + 8px);
                bottom: auto;
                top: 50%;
                transform: translateY(-50%);
            }
            
            [data-tooltip-position="right"]::after {
                left: calc(100% + 4px);
                bottom: auto;
                top: 50%;
                transform: translateY(-50%);
                border-top-color: transparent;
                border-right-color: hsl(240 10% 3.9%);
            }
            
            /* Multiline tooltip */
            [data-tooltip-multiline]::before {
                white-space: pre-wrap;
                max-width: 280px;
                text-align: left;
            }
            
            /* Stat card info icons */
            .stat-info-icon {
                width: 16px;
                height: 16px;
                color: hsl(var(--muted-foreground));
                cursor: help;
                opacity: 0.6;
                transition: opacity 0.15s ease;
            }
            
            .stat-info-icon:hover {
                opacity: 1;
            }
        </style>
    </head>
    <body class="h-full antialiased">
    <?php
}

/**
 * Output dashboard footer/scripts.
 */
function apollo_dashboard_footer() {
    ?>
    <?php wp_footer(); ?>
    
    <!-- Dashboard JavaScript -->
    <script>
    (function() {
        'use strict';
        
        // Sidebar toggle
        document.querySelectorAll('.apollo-sidebar-trigger').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var provider = document.querySelector('.apollo-sidebar-provider');
                var currentState = provider.getAttribute('data-sidebar-state');
                var newState = currentState === 'expanded' ? 'collapsed' : 'expanded';
                provider.setAttribute('data-sidebar-state', newState);
                localStorage.setItem('apollo-sidebar-state', newState);
            });
        });
        
        // Restore sidebar state
        var savedState = localStorage.getItem('apollo-sidebar-state');
        if (savedState) {
            var provider = document.querySelector('.apollo-sidebar-provider');
            if (provider) {
                provider.setAttribute('data-sidebar-state', savedState);
            }
        }
        
        // Mobile sidebar
        document.querySelectorAll('[data-mobile-sidebar-open]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var mobileSidebar = document.querySelector('.apollo-sidebar-mobile');
                if (mobileSidebar) {
                    mobileSidebar.setAttribute('data-active', 'true');
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        document.querySelectorAll('[data-sidebar-close]').forEach(function(el) {
            el.addEventListener('click', function() {
                var mobileSidebar = document.querySelector('.apollo-sidebar-mobile');
                if (mobileSidebar) {
                    mobileSidebar.removeAttribute('data-active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Dropdowns
        document.querySelectorAll('[data-dropdown]').forEach(function(dropdown) {
            var trigger = dropdown.querySelector('[data-dropdown-trigger]');
            var content = dropdown.querySelector('[data-dropdown-content]');
            
            if (trigger && content) {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var isActive = content.getAttribute('data-active') === 'true';
                    
                    // Close all other dropdowns
                    document.querySelectorAll('[data-dropdown-content]').forEach(function(d) {
                        d.removeAttribute('data-active');
                    });
                    
                    if (!isActive) {
                        content.setAttribute('data-active', 'true');
                    }
                });
            }
        });
        
        // User menu
        document.querySelectorAll('[data-user-menu]').forEach(function(menu) {
            var trigger = menu.querySelector('[data-user-menu-trigger]');
            var dropdown = menu.querySelector('[data-user-dropdown]');
            
            if (trigger && dropdown) {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var isActive = dropdown.getAttribute('data-active') === 'true';
                    dropdown.setAttribute('data-active', isActive ? 'false' : 'true');
                });
            }
        });
        
        // Close dropdowns on outside click
        document.addEventListener('click', function() {
            document.querySelectorAll('[data-dropdown-content], [data-user-dropdown]').forEach(function(d) {
                d.removeAttribute('data-active');
            });
        });
        
        // Theme toggle
        document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('apollo-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            });
        });
        
        // Restore theme
        var savedTheme = localStorage.getItem('apollo-theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }
        
        // Table search
        document.querySelectorAll('[data-table-search]').forEach(function(input) {
            var tableId = input.getAttribute('data-table-search');
            var table = document.getElementById(tableId);
            
            if (table) {
                input.addEventListener('input', function() {
                    var query = this.value.toLowerCase();
                    var rows = table.querySelectorAll('tbody tr[data-row-index]');
                    
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }
        });
        
        // Table sorting
        document.querySelectorAll('th[data-sortable]').forEach(function(th) {
            th.addEventListener('click', function() {
                var table = this.closest('table');
                var tbody = table.querySelector('tbody');
                var rows = Array.from(tbody.querySelectorAll('tr[data-row-index]'));
                var column = this.getAttribute('data-column');
                var colIndex = Array.from(this.parentElement.children).indexOf(this);
                var isAsc = this.getAttribute('data-sort') !== 'asc';
                
                // Reset all sort indicators
                table.querySelectorAll('th[data-sortable]').forEach(function(h) {
                    h.removeAttribute('data-sort');
                });
                this.setAttribute('data-sort', isAsc ? 'asc' : 'desc');
                
                rows.sort(function(a, b) {
                    var aVal = a.children[colIndex].textContent.trim();
                    var bVal = b.children[colIndex].textContent.trim();
                    
                    // Try numeric sort
                    var aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
                    var bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return isAsc ? aNum - bNum : bNum - aNum;
                    }
                    
                    return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                });
                
                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            });
        });
        
    })();
    </script>
    </body>
    </html>
    <?php
}

/**
 * Render an interactive chart area.
 *
 * @param array $chart Chart configuration.
 */
function apollo_render_chart_area( array $chart ) {
    $title = $chart['title'] ?? 'Atividade';
    $data = $chart['data'] ?? array();
    ?>
    <div class="rounded-xl border bg-card p-4 lg:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold"><?php echo esc_html( $title ); ?></h3>
                <p class="text-sm text-muted-foreground">Últimos 30 dias</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="inline-flex h-8 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-accent">
                    <span>Período</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="h-64 flex items-center justify-center text-muted-foreground">
            <!-- Chart placeholder - integrate with Chart.js or similar -->
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2 opacity-50">
                    <path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>
                </svg>
                <p class="text-sm">Gráfico de atividade</p>
            </div>
        </div>
    </div>
    <?php
}
