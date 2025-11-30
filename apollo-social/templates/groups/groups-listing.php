<?php
/**
 * Groups/Communities Listing Template
 * DESIGN LIBRARY: Matches approved HTML from 'social groups-community-nucleo listing.md'
 * Card grid for public communities and private nucleos
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Determine if showing nucleos (private) or comunidades (public)
$is_nucleo = isset($args['type']) && $args['type'] === 'nucleo';
$page_title = $is_nucleo ? __('Núcleos', 'apollo-social') : __('Comunidades da Cena', 'apollo-social');
$page_subtitle = $is_nucleo 
    ? __('Grupos privados para equipes, produção e projetos fechados', 'apollo-social')
    : __('Grupos públicos para discutir, organizar e fortalecer a noite', 'apollo-social');

// Get current user
$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$current_user_avatar = get_avatar_url($current_user_id, ['size' => 64]);

// Get filter
$current_filter = isset($_GET['filter']) ? sanitize_text_field(wp_unslash($_GET['filter'])) : 'all';

// Query groups
$groups_args = [
    'post_type'      => 'apollo_group',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => 'meta_value_num',
    'meta_key'       => '_group_members_count',
    'order'          => 'DESC',
    'meta_query'     => [
        [
            'key'     => '_group_is_private',
            'value'   => $is_nucleo ? '1' : '',
            'compare' => $is_nucleo ? '=' : 'NOT EXISTS',
        ],
    ],
];

$groups = get_posts($groups_args);

// Get unique tags for filters
$all_tags = [];
foreach ($groups as $group) {
    $tags = get_post_meta($group->ID, '_group_tags', true);
    if ($tags) {
        $tag_array = is_array($tags) ? $tags : array_map('trim', explode(',', $tags));
        foreach ($tag_array as $tag) {
            $slug = sanitize_title($tag);
            if (!isset($all_tags[$slug])) {
                $all_tags[$slug] = $tag;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full w-full">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?php echo esc_html($page_title); ?> - Apollo::Rio</title>
    
    <!-- Apollo Design System -->
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <!-- Remixicon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    
    <?php wp_head(); ?>
</head>
<body class="apollo-canvas min-h-screen">
<div class="min-h-screen flex">

    <!-- SIDEBAR DESKTOP -->
    <aside class="hidden md:flex md:flex-col w-64 border-r border-slate-200 bg-white/95 backdrop-blur-xl" data-tooltip="<?php esc_attr_e('Menu de navegação', 'apollo-social'); ?>">
        <!-- Logo -->
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
            <div class="h-9 w-9 rounded-[8px] bg-slate-900 flex items-center justify-center text-white" data-tooltip="<?php esc_attr_e('Apollo::Rio', 'apollo-social'); ?>">
                <i class="ri-command-fill text-lg"></i>
            </div>
            <div class="flex flex-col leading-tight">
                <span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]"><?php esc_html_e('plataforma', 'apollo-social'); ?></span>
                <span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
            <div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider"><?php esc_html_e('Navegação', 'apollo-social'); ?></div>

            <a href="<?php echo esc_url(home_url('/feed/')); ?>" data-tooltip="<?php esc_attr_e('Ver feed', 'apollo-social'); ?>">
                <i class="ri-building-3-line"></i>
                <span><?php esc_html_e('Feed', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/eventos/')); ?>" data-tooltip="<?php esc_attr_e('Ver eventos', 'apollo-social'); ?>">
                <i class="ri-calendar-event-line"></i>
                <span><?php esc_html_e('Eventos', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" <?php echo !$is_nucleo ? 'aria-current="page"' : ''; ?> data-tooltip="<?php esc_attr_e('Ver comunidades', 'apollo-social'); ?>">
                <i class="ri-user-community-fill"></i>
                <span><?php esc_html_e('Comunidades', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/nucleos/')); ?>" <?php echo $is_nucleo ? 'aria-current="page"' : ''; ?> data-tooltip="<?php esc_attr_e('Ver núcleos', 'apollo-social'); ?>">
                <i class="ri-team-fill"></i>
                <span><?php esc_html_e('Núcleos', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/classificados/')); ?>" data-tooltip="<?php esc_attr_e('Ver classificados', 'apollo-social'); ?>">
                <i class="ri-megaphone-line"></i>
                <span><?php esc_html_e('Classificados', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/docs/')); ?>" data-tooltip="<?php esc_attr_e('Ver documentos', 'apollo-social'); ?>">
                <i class="ri-file-text-line"></i>
                <span><?php esc_html_e('Docs & Contratos', 'apollo-social'); ?></span>
            </a>

            <a href="<?php echo esc_url(home_url('/perfil/')); ?>" data-tooltip="<?php esc_attr_e('Ver perfil', 'apollo-social'); ?>">
                <i class="ri-user-smile-fill"></i>
                <span><?php esc_html_e('Perfil', 'apollo-social'); ?></span>
            </a>

            <div class="mt-4 px-1 mb-1 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider"><?php esc_html_e('Configurações', 'apollo-social'); ?></div>
            <a href="<?php echo esc_url(home_url('/ajustes/')); ?>" data-tooltip="<?php esc_attr_e('Ajustes', 'apollo-social'); ?>">
                <i class="ri-settings-6-line"></i>
                <span><?php esc_html_e('Ajustes', 'apollo-social'); ?></span>
            </a>
        </nav>

        <!-- User Footer -->
        <?php if ($current_user_id): ?>
        <div class="border-t border-slate-100 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100" data-tooltip="<?php echo esc_attr($current_user->display_name); ?>">
                    <img src="<?php echo esc_url($current_user_avatar); ?>" class="h-full w-full object-cover" alt="<?php echo esc_attr($current_user->display_name); ?>">
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html($current_user->display_name); ?></span>
                    <span class="text-[10px] text-slate-500">@<?php echo esc_html($current_user->user_login); ?></span>
                </div>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="ml-auto text-slate-400 hover:text-slate-700" data-tooltip="<?php esc_attr_e('Sair', 'apollo-social'); ?>">
                    <i class="ri-logout-circle-r-line text-base"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- MAIN COLUMN -->
    <div class="flex-1 flex flex-col min-h-screen bg-slate-50/60">

        <!-- HEADER -->
        <header class="sticky top-0 z-40 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6" data-tooltip="<?php esc_attr_e('Cabeçalho da página', 'apollo-social'); ?>">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-[6px] bg-slate-900 flex items-center justify-center md:hidden text-white" data-tooltip="<?php echo esc_attr($page_title); ?>">
                    <i class="ri-group-line text-[20px]"></i>
                </div>

                <div class="flex flex-col leading-none">
                    <h1 class="text-[15px] font-extrabold mt-2 text-slate-900" data-tooltip="<?php echo esc_attr($page_title); ?>">
                        <?php echo esc_html($page_title); ?>
                    </h1>
                    <p class="text-[12px] text-slate-500" data-tooltip="<?php echo esc_attr($page_subtitle); ?>">
                        <?php echo esc_html($page_subtitle); ?>
                    </p>
                </div>
            </div>

            <!-- Desktop controls -->
            <div class="hidden md:flex items-center gap-3 text-[12px]">
                <div class="relative group" data-tooltip="<?php esc_attr_e('Buscar grupos', 'apollo-social'); ?>">
                    <i class="ri-search-line text-slate-400 absolute left-3 top-1.5 text-xs group-focus-within:text-slate-600"></i>
                    <input
                        type="text"
                        placeholder="<?php esc_attr_e('Buscar comunidades...', 'apollo-social'); ?>"
                        class="pl-8 pr-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-[12px] w-64 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:bg-white transition-all"
                        id="search-communities"
                        data-tooltip="<?php esc_attr_e('Digite para filtrar', 'apollo-social'); ?>"
                    >
                </div>

                <?php if ($current_user_id): ?>
                <button class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 transition-all" data-tooltip="<?php echo esc_attr($current_user->display_name); ?>">
                    <img src="<?php echo esc_url($current_user_avatar); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="h-full w-full rounded-full object-cover">
                </button>
                <?php endif; ?>
            </div>

            <!-- Mobile actions -->
            <div class="flex md:hidden items-center gap-2">
                <button class="text-slate-500 hover:text-slate-900" data-tooltip="<?php esc_attr_e('Buscar', 'apollo-social'); ?>">
                    <i class="ri-search-line text-xl"></i>
                </button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-0 md:px-6 py-4 md:py-6 pb-24 md:pb-8">
            <div class="w-full max-w-6xl mx-auto flex flex-col gap-4">

                <!-- Filters -->
                <section class="bg-white/90 border border-slate-200 md:rounded-2xl px-4 py-3 md:px-5 md:py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3" data-tooltip="<?php esc_attr_e('Filtros de busca', 'apollo-social'); ?>">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-white text-xs" data-tooltip="<?php echo esc_attr($is_nucleo ? __('Grupos privados', 'apollo-social') : __('Grupos públicos', 'apollo-social')); ?>">
                            <i class="ri-hashtag"></i>
                        </span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html($is_nucleo ? __('Núcleos privados', 'apollo-social') : __('Comunidades públicas', 'apollo-social')); ?></span>
                            <span class="text-[11px] text-slate-500"><?php echo esc_html($is_nucleo ? __('Solicite acesso ou seja convidado para participar.', 'apollo-social') : __('Entre, leia as regras e conecte-se.', 'apollo-social')); ?></span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <button class="px-3 py-1 rounded-full border <?php echo $current_filter === 'all' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:bg-slate-50'; ?> font-medium" data-filter="all" data-tooltip="<?php esc_attr_e('Mostrar todos', 'apollo-social'); ?>">
                            <?php esc_html_e('Todas', 'apollo-social'); ?>
                        </button>
                        <?php foreach (array_slice($all_tags, 0, 4) as $slug => $tag): ?>
                        <button class="px-3 py-1 rounded-full border <?php echo $current_filter === $slug ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:bg-slate-50'; ?>" data-filter="<?php echo esc_attr($slug); ?>" data-tooltip="<?php echo esc_attr(sprintf(__('Filtrar por %s', 'apollo-social'), $tag)); ?>">
                            <?php echo esc_html($tag); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Groups Grid -->
                <section class="mt-2" data-tooltip="<?php esc_attr_e('Lista de grupos', 'apollo-social'); ?>">
                    <?php if (empty($groups)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center" data-tooltip="<?php esc_attr_e('Nenhum grupo encontrado', 'apollo-social'); ?>">
                        <div class="h-16 w-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                            <i class="ri-group-line text-3xl text-slate-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2"><?php echo esc_html($is_nucleo ? __('Nenhum núcleo encontrado', 'apollo-social') : __('Nenhuma comunidade encontrada', 'apollo-social')); ?></h3>
                        <p class="text-sm text-slate-500"><?php esc_html_e('Seja o primeiro a criar um grupo!', 'apollo-social'); ?></p>
                    </div>
                    <?php else: ?>
                    <div id="communities-grid" class="flex flex-wrap justify-start gap-4 md:gap-5" style="row-gap: 18px;">
                        <?php foreach ($groups as $group):
                            $group_id = $group->ID;
                            $group_title = get_the_title($group_id);
                            $group_description = get_post_meta($group_id, '_group_description', true) ?: wp_trim_words($group->post_content, 15);
                            $group_cover = get_post_meta($group_id, '_group_cover', true);
                            $group_avatar = get_post_meta($group_id, '_group_avatar', true);
                            $members_count = (int) get_post_meta($group_id, '_group_members_count', true);
                            $posts_count = (int) get_post_meta($group_id, '_group_posts_count', true);
                            $is_verified = (bool) get_post_meta($group_id, '_group_is_verified', true);
                            $group_tags = get_post_meta($group_id, '_group_tags', true);
                            $tags_string = is_array($group_tags) ? implode(',', $group_tags) : ($group_tags ?: '');
                            
                            // Fallback cover
                            if (!$group_cover) {
                                $group_cover = 'https://images.pexels.com/photos/167404/pexels-photo-167404.jpeg';
                            }
                            
                            // Check membership
                            $is_member = false;
                            if ($current_user_id) {
                                $memberships = get_user_meta($current_user_id, '_group_memberships', true);
                                if (is_array($memberships) && in_array($group_id, $memberships, true)) {
                                    $is_member = true;
                                }
                            }
                        ?>
                        <article class="community-card" data-tags="<?php echo esc_attr(strtolower($tags_string)); ?>" data-group-id="<?php echo esc_attr($group_id); ?>" data-tooltip="<?php echo esc_attr($group_title); ?>">
                            <figure class="community-card__media">
                                <img class="community-card__avatar" src="<?php echo esc_url($group_cover); ?>" alt="<?php echo esc_attr($group_title); ?>" loading="lazy" data-tooltip="<?php echo esc_attr(sprintf(__('Imagem: %s', 'apollo-social'), $group_title)); ?>">
                            </figure>
                            <div class="community-card__body">
                                <div class="community-card__header">
                                    <h2 class="community-card__name" data-tooltip="<?php echo esc_attr($group_title); ?>"><?php echo esc_html($group_title); ?></h2>
                                    <?php if ($is_verified): ?>
                                    <svg fill="#22c55e" class="community-card__badge" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" data-tooltip="<?php esc_attr_e('Grupo verificado', 'apollo-social'); ?>">
                                        <path d="M23,12L20.56,9.22L20.9,5.54L17.29,4.72L15.4,1.54L12,3L8.6,1.54L6.71,4.72L3.1,5.53L3.44,9.21L1,12L3.44,14.78L3.1,18.47L6.71,19.29L8.6,22.47L12,21L15.4,22.46L17.29,19.28L20.9,18.46L20.56,14.78L23,12M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z" />
                                    </svg>
                                    <?php endif; ?>
                                </div>
                                <p class="community-card__description" data-tooltip="<?php echo esc_attr($group_description); ?>">
                                    <?php echo esc_html($group_description); ?>
                                </p>
                                <div class="community-card__footer">
                                    <div class="community-card__stats">
                                        <div class="community-card__stat" data-tooltip="<?php echo esc_attr(sprintf(_n('%d membro', '%d membros', $members_count, 'apollo-social'), $members_count)); ?>">
                                            <i class="ri-user-community-fill"></i>
                                            <span class="community-card__stat-value text-[9.5px]"><?php echo esc_html(number_format_i18n($members_count)); ?> <?php esc_html_e('clubbers', 'apollo-social'); ?></span>
                                        </div>
                                        <div class="community-card__stat" data-tooltip="<?php echo esc_attr(sprintf(_n('%d post', '%d posts', $posts_count, 'apollo-social'), $posts_count)); ?>">
                                            <i class="ri-chat-poll-fill"></i>
                                            <span class="text-[9.5px]">+<?php echo esc_html(number_format_i18n($posts_count)); ?> posts</span>
                                        </div>
                                    </div>
                                    <div class="community-card__actions">
                                        <?php if ($is_member): ?>
                                        <a href="<?php echo esc_url(get_permalink($group_id)); ?>" class="community-card__follow-btn" data-tooltip="<?php esc_attr_e('Ver grupo', 'apollo-social'); ?>">
                                            <i class="ri-eye-line text-white"></i>
                                            <span class="text-white text-[9.5px]"><?php esc_html_e('Ver', 'apollo-social'); ?></span>
                                        </a>
                                        <?php else: ?>
                                        <button type="button" class="community-card__follow-btn" data-action="join-group" data-group-id="<?php echo esc_attr($group_id); ?>" data-tooltip="<?php echo esc_attr($is_nucleo ? __('Solicitar acesso', 'apollo-social') : __('Entrar no grupo', 'apollo-social')); ?>">
                                            <i class="ri-add-fill text-white"></i>
                                            <span class="text-white text-[9.5px]"><?php echo esc_html($is_nucleo ? __('Solicitar', 'apollo-social') : __('Entrar', 'apollo-social')); ?></span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <!-- BOTTOM NAV MOBILE -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/50 pb-safe z-50" data-tooltip="<?php esc_attr_e('Navegação mobile', 'apollo-social'); ?>">
            <div class="max-w-2xl mx-auto w-full px-4 py-2 flex items-end justify-between h-[60px]">
                <a href="<?php echo esc_url(home_url('/feed/')); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e('Feed', 'apollo-social'); ?>">
                    <i class="ri-home-5-line"></i>
                    <span><?php esc_html_e('Feed', 'apollo-social'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e('Agenda', 'apollo-social'); ?>">
                    <i class="ri-calendar-line"></i>
                    <span><?php esc_html_e('Agenda', 'apollo-social'); ?></span>
                </a>
                <div class="relative -top-5">
                    <button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(15,23,42,0.6)]" data-tooltip="<?php esc_attr_e('Criar novo', 'apollo-social'); ?>">
                        <i class="ri-add-line text-3xl"></i>
                    </button>
                </div>
                <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="nav-btn <?php echo !$is_nucleo ? 'active' : ''; ?> w-14 pb-1" data-tooltip="<?php esc_attr_e('Comunidades', 'apollo-social'); ?>">
                    <i class="ri-user-community-fill"></i>
                    <span><?php esc_html_e('Comunidades', 'apollo-social'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/perfil/')); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e('Perfil', 'apollo-social'); ?>">
                    <i class="ri-user-3-line"></i>
                    <span><?php esc_html_e('Perfil', 'apollo-social'); ?></span>
                </a>
            </div>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('[data-filter]');
    const cards = document.querySelectorAll('.community-card');
    const search = document.getElementById('search-communities');

    // Filter by tags
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.getAttribute('data-filter');

            buttons.forEach(b => {
                b.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                b.classList.add('border-slate-200', 'text-slate-700');
            });
            btn.classList.add('bg-slate-900', 'text-white', 'border-slate-900');

            cards.forEach(card => {
                const tags = (card.getAttribute('data-tags') || '').toLowerCase();
                if (filter === 'all' || tags.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Search filter
    if (search) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            cards.forEach(card => {
                const name = (card.querySelector('.community-card__name')?.textContent || '').toLowerCase();
                const desc = (card.querySelector('.community-card__description')?.textContent || '').toLowerCase();
                if (!q || name.includes(q) || desc.includes(q)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Join group action
    document.querySelectorAll('[data-action="join-group"]').forEach(btn => {
        btn.addEventListener('click', async function() {
            const groupId = this.dataset.groupId;
            try {
                const response = await fetch('<?php echo esc_url(rest_url('apollo-social/v1/groups/join')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'
                    },
                    body: JSON.stringify({ group_id: groupId })
                });
                const data = await response.json();
                if (data.success) {
                    this.innerHTML = '<i class="ri-check-line text-white"></i><span class="text-white text-[9.5px]"><?php echo esc_js(__('Entrou!', 'apollo-social')); ?></span>';
                    this.disabled = true;
                } else {
                    alert(data.message || '<?php echo esc_js(__('Erro ao entrar no grupo', 'apollo-social')); ?>');
                }
            } catch (e) {
                console.error(e);
            }
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>

