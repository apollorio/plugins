<?php
/**
 * Apollo Social - Base Layout Template
 * 
 * Layout oficial com navbar fixa, aside (sidebar) e bottom navigation mobile.
 * Baseado em: social - layout - official (aside navbar botton-responsive-bar).html
 * 
 * @package Apollo_Social
 * @since 2.0.0
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if (! defined('ABSPATH')) {
    exit;
}

// Enqueue assets
add_action(
    'wp_enqueue_scripts',
    function () {
        wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
        wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
        wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com', [], null, false);
        wp_enqueue_script('jquery', 'https://code.jquery.com/jquery-3.1.0.min.js', [], '3.1.0', true);
        wp_enqueue_script('motion-one', 'https://unpkg.com/@motionone/dom@10.16.4/dist/index.js', [], '10.16.4', true);
        
        // Inline Tailwind config
        wp_add_inline_script(
            'tailwind',
            '
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ["Urbanist", "system-ui", "sans-serif"] },
                    colors: { slate: { 850: "#1e293b" } }
                }
            }
        }
    ',
            'before'
        );
    },
    10
);

// Get current user
$current_user      = wp_get_current_user();
$user_avatar      = get_avatar_url($current_user->ID, ['size' => 40]);
$user_display_name = $current_user->display_name ?: $current_user->user_login;
$user_role         = 'Produtor'; // TODO: Get actual role

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <?php wp_head(); ?>
    
    <style>
        /* =========================================
           CORE CSS VARIABLES
           ========================================= */
        :root {
            --ap-font-primary: "Urbanist", system-ui, -apple-system, sans-serif;
            --ap-bg-main: #ffffff;
            --ap-bg-surface: #f8fafc;
            --ap-text-primary: rgba(19, 21, 23, 0.65);
            --ap-text-secondary: rgba(15, 23, 42, 0.88);
            --ap-text-muted: rgba(19, 21, 23, 0.4);
            --ap-orange-500: #FF6925;
            --ap-orange-600: #E55A1E;
            --glass-surface: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(226, 232, 240, 0.8);
            --glass-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
            --item-hover: rgba(15, 23, 42, 0.04);
            --card-bg: #ffffff;
            --nav-height: 70px;
            --menu-radius: 20px;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: var(--ap-font-primary);
            background-color: var(--ap-bg-main);
            color: var(--ap-text-secondary);
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.4);
            border-radius: 999px;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        /* =========================================
           NAVBAR - FIXED TOP
           ========================================= */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
            height: var(--nav-height);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: none;
        }

        @keyframes pulsar {
            0%, 100% {
                filter: brightness(1);
                box-shadow: 0px 4px 5px rgba(255, 165, 0, 0.07);
            }
            50% {
                filter: brightness(1.15) saturate(1.2);
                box-shadow: 0px 4px 15px rgba(255, 165, 0, 0.15);
            }
        }

        .clock-pill {
            padding: 0.4rem 1rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--ap-text-primary);
            cursor: default;
            margin-right: 0.5rem;
            background: transparent;
            border: none;
        }

        .nav-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ap-text-primary);
            transition: all 0.2s ease;
            position: relative;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        
        .nav-btn:hover,
        .nav-btn[aria-expanded="true"] {
            background: rgba(0,0,0,0.05);
            color: var(--ap-orange-500);
            transform: scale(1.05);
        }
        
        .nav-btn svg {
            width: 22px;
            height: 22px;
        }

        .badge {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            z-index: 10;
            pointer-events: none;
            background: transparent;
            box-shadow: none;
        }
        
        .badge[data-notif="true"] {
            background: #ff8c00;
            box-shadow: 0 0 10px #ff8c00;
            animation: pulsar-badge 2s infinite ease-in;
        }
        
        @keyframes pulsar-badge {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 140, 0, 0.7);
            }
            100% {
                transform: scale(1.03);
                filter: brightness(1.3) saturate(1.5);
                box-shadow: 0 0 0 15px rgba(255, 140, 0, 0);
            }
        }

        @media (min-width: 769px) {
            .navbar .clock-pill { display: block; }
            .navbar #btn-notif { display: flex; }
            .navbar #btn-apps { display: flex; }
            .navbar #btn-profile { display: flex; }
        }
        
        @media (max-width: 768px) {
            .navbar #btn-notif { display: none !important; }
            .navbar .clock-pill { display: none; }
            .navbar #btn-apps { display: flex !important; margin-left: auto; }
            .navbar #btn-profile { display: flex !important; }
        }

        /* =========================================
           SIDEBAR - NO STICKY!
           ========================================= */
        aside {
            position: static;
            overflow-y: visible;
        }
        
        .aprio-sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 10px 12px;
            margin-bottom: 4px;
            border-radius: 12px;
            border-left: 2px solid transparent;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .aprio-sidebar-nav a:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }
        
        .aprio-sidebar-nav a[aria-current="page"] {
            background-color: #f1f5f9;
            color: #0f172a;
            border-left-color: #0f172a;
            font-weight: 600;
        }

        /* =========================================
           DROPDOWN MENUS
           ========================================= */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(var(--nav-height) + 10px);
            right: 16px;
            width: 380px;
            max-width: 90vw;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: var(--menu-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            z-index: 999;
            flex-direction: column;
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow-y: auto;
            max-height: 85vh;
        }
        
        .dropdown-menu.active {
            display: flex;
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        @media (max-width: 768px) {
            #menu-notif {
                width: 80% !important;
                height: fit-content !important;
                max-height: 95vh !important;
                left: 50% !important;
                right: auto !important;
                transform: translateX(-50%) translateY(-10px) scale(0.98);
            }
            
            #menu-notif.active {
                transform: translateX(-50%) translateY(0) scale(1);
            }
            
            #menu-app {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                right: auto !important;
                width: 90vw !important;
                height: 90vh !important;
                max-width: none !important;
                max-height: none !important;
                transform: translate(-50%, -50%) scale(0.98);
                border-radius: var(--menu-radius);
                padding-top: 1.5rem;
            }
            
            #menu-app.active {
                transform: translate(-50%, -50%) scale(1);
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <!-- Logo Section -->
    <div class="flex items-center">
        <!-- Desktop Logo -->
        <div class="hidden md:flex h-16 items-center gap-3 ml-4" style="transform:scale(1.0);">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center z-20 shadow-md">
                <i class="ri-slack-fill text-white text-[21px]"></i>
            </div>
            <div class="ml-2 flex flex-col leading-tight">
                <span class="text-[15px] font-bold text-slate-900 opacity-95">Apollo::rio</span>
                <span class="text-[8.5px] font-regular center text-center text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
            </div>
        </div>

        <!-- Mobile Logo - Icon Only -->
        <div class="md:hidden h-16 flex items-center gap-3 ml-0">
            <div class="h-9 w-9 rounded-[12px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center z-20" style="animation: pulsar 6.2s ease-in-out infinite alternate; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);">
                <i class="ri-slack-fill text-white z-35 text-[20px]"></i>
            </div>
        </div>
    </div>

    <!-- Right Side Navigation Controls -->
    <div class="flex items-center gap-2">
        <!-- Clock -->
        <div class="clock-pill hidden sm:block" id="digital-clock"><?php echo esc_html(current_time('H:i:s')); ?></div>
        
        <!-- Notifications Button -->
        <button id="btn-notif" class="nav-btn" aria-label="Notificações" aria-expanded="false">
            <div class="badge" id="notif-badge" data-notif="true"></div>
            <i class="ri-gps-line text-xl"></i>
        </button>

        <!-- Apps Button -->
        <button id="btn-apps" class="nav-btn" aria-label="Aplicativos" aria-expanded="false">
            <i class="ri-grid-fill text-xl"></i>
        </button>

        <!-- Profile Avatar Button -->
        <button id="btn-profile" class="nav-btn font-bold bg-slate-100 rounded-full" style="width:36px; height:36px; font-size:12px;" aria-expanded="false">
            <?php echo esc_html(mb_substr($user_display_name, 0, 1, 'UTF-8')); ?>
        </button>
    </div>
</nav>

<!-- MAIN LAYOUT -->
<div class="flex pt-[70px]">
    <!-- SIDEBAR -->
    <aside class="hidden md:flex flex-col w-64 mr-6 ml-4 pb-2">
        <nav class="aprio-sidebar-nav flex-1 pl-4 pr-3 mt-1 pt-2 pb-2 overflow-y-auto no-scrollbar text-[13px]">
            <!-- Navigation Section -->
            <div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Navegação</div>
            <a href="<?php echo esc_url(home_url('/feed/')); ?>"><i class="ri-building-3-line"></i><span>Feed</span></a>
            <a href="<?php echo esc_url(home_url('/eventos/')); ?>"><i class="ri-calendar-event-line"></i><span>Eventos</span></a>
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>"><i class="ri-user-community-fill"></i><span>Comunidades</span></a>
            <a href="<?php echo esc_url(home_url('/nucleos/')); ?>"><i class="ri-team-fill"></i><span>Núcleos</span></a>
            <a href="<?php echo esc_url(home_url('/classificados/')); ?>"><i class="ri-megaphone-line"></i><span>Classificados</span></a>
            <a href="<?php echo esc_url(home_url('/documentos/')); ?>"><i class="ri-file-text-line"></i><span>Docs &amp; Contratos</span></a>
            <a href="<?php echo esc_url(get_author_posts_url($current_user->ID)); ?>"><i class="ri-user-smile-fill"></i><span>Perfil</span></a>

            <!-- Cena::rio Section -->
            <div class="mt-6 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Cena::rio</div>
            <a href="<?php echo esc_url(home_url('/agenda/')); ?>"><i class="ri-calendar-line"></i><span>Agenda</span></a>
            <a href="<?php echo esc_url(home_url('/fornecedores/')); ?>" aria-current="page">
                <i class="ri-bar-chart-grouped-line"></i>
                <span>Fornecedores</span>
            </a>
            <a href="<?php echo esc_url(home_url('/documentos/')); ?>"><i class="ri-file-text-line"></i><span>Documentos</span></a>

            <!-- Quick Access Section -->
            <div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Acesso Rápido</div>
            <a href="<?php echo esc_url(admin_url('options-general.php')); ?>"><i class="ri-settings-6-line"></i><span>Ajustes</span></a>
        </nav>

        <!-- User Profile Card -->
        <div class="mt-auto pt-4 border-t border-slate-100 flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-orange-100 overflow-hidden">
                <img src="<?php echo esc_url($user_avatar); ?>" alt="<?php echo esc_attr($user_display_name); ?>" class="object-cover w-full h-full">
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-900"><?php echo esc_html($user_display_name); ?></span>
                <span class="text-[10px] text-slate-500"><?php echo esc_html($user_role); ?></span>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 px-4 md:px-6 py-6">
        <?php
        // Content will be included here via get_template_part or direct include
        if (isset($content_callback) && is_callable($content_callback)) {
            call_user_func($content_callback);
        } elseif (isset($content_template) && file_exists($content_template)) {
            // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
            include $content_template;
        } elseif (isset($content_html)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $content_html;
        } else {
            // Default: render feed if no content specified
            $plugin_dir = defined('APOLLO_SOCIAL_PLUGIN_DIR') ? APOLLO_SOCIAL_PLUGIN_DIR : dirname(__DIR__);
            $feed_template = $plugin_dir . '/templates/feed/feed.php';
            if (file_exists($feed_template)) {
                // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
                include $feed_template;
            }
        }
        ?>
    </main>
</div>

<!-- Mobile Bottom Navigation -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 pb-safe">
    <div class="max-w-md mx-auto w-full px-6 py-2 flex items-end justify-between h-[60px]">
        <div class="flex flex-col items-center justify-center w-14 gap-1">
            <i class="ri-calendar-line text-xl text-slate-400"></i>
            <span class="text-[10px] text-slate-400">Agenda</span>
        </div>
        <div class="flex flex-col items-center justify-center w-14 gap-1">
            <i class="ri-bar-chart-grouped-line text-xl text-slate-900"></i>
            <span class="text-[10px] font-bold text-slate-900">Pro</span>
        </div>
        <div class="relative -top-6">
            <button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/30 active:scale-95 transition-transform">
                <i class="ri-add-line text-2xl"></i>
            </button>
        </div>
        <div class="flex flex-col items-center justify-center w-14 gap-1">
            <i class="ri-file-text-line text-xl text-slate-400"></i>
            <span class="text-[10px] text-slate-400">Docs</span>
        </div>
        <div class="flex flex-col items-center justify-center w-14 gap-1">
            <i class="ri-user-3-line text-xl text-slate-400"></i>
            <span class="text-[10px] text-slate-400">Perfil</span>
        </div>
    </div>
</div>

<!-- NOTIFICATION DROPDOWN -->
<div id="menu-notif" class="dropdown-menu" style="display: none;">
    <div class="section-title">Notificações</div>
    <div id="notif-list">
        <!-- Dynamic notifications will be loaded here -->
    </div>
    <div class="load-more-notif" id="load-more-notif">
        Carregar mais notificações
    </div>
</div>

<!-- APPS MENU DROPDOWN -->
<div id="menu-app" class="dropdown-menu" style="display: none;">
    <div class="section-title">Aplicativos</div>
    <div class="apps-grid">
        <!-- Apps will be loaded here -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Digital Clock
    function updateClock() {
        const now = new Date();
        const time = now.toLocaleTimeString('pt-BR', { hour12: false });
        const clockEl = document.getElementById('digital-clock');
        if (clockEl) clockEl.textContent = time;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Dropdown Toggles
    document.getElementById('btn-notif')?.addEventListener('click', function(e) {
        e.stopPropagation();
        const menu = document.getElementById('menu-notif');
        const isActive = menu.classList.contains('active');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        if (!isActive) menu.classList.add('active');
        this.setAttribute('aria-expanded', !isActive);
    });

    document.getElementById('btn-apps')?.addEventListener('click', function(e) {
        e.stopPropagation();
        const menu = document.getElementById('menu-app');
        const isActive = menu.classList.contains('active');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        if (!isActive) menu.classList.add('active');
        this.setAttribute('aria-expanded', !isActive);
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-btn') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        }
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>

