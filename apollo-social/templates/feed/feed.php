<?php
/**
 * Feed Template - Apollo Social.
 *
 * Based on approved design: social - feed main.html
 * Renders social feed with composer, filter tabs, and post cards.
 * NOTE: This is a partial template included in Canvas layout - NO DOCTYPE/HTML/BODY tags.
 *
 * @package ApolloSocial
 */

if (! defined('ABSPATH')) {
    exit;
}

// FASE 2: Dados já vêm do FeedRenderer via CanvasBuilder.
$feed_posts = $view['data']['posts']        ?? [];
$user_data  = $view['data']['current_user'] ?? [];

$ajax_url      = admin_url('admin-ajax.php');
$rest_url      = rest_url('apollo/v1');
$nonce         = wp_create_nonce('wp_rest');
$comment_nonce = wp_create_nonce('apollo_comment_nonce');
?>

<!-- Feed Container - Centered Layout per approved design -->
<div class="min-h-screen flex justify-center px-4 py-6 md:px-6 lg:px-8">
  <div class="w-full max-w-2xl">

    <!-- COMPOSE BOX -->
    <section class="ap-compose-box">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
          <img src="<?php echo esc_url($user_data['avatar'] ?? ''); ?>" alt="<?php echo esc_attr($user_data['name'] ?? ''); ?>" style="width: 100%; height: 100%;">
        </div>
        <button class="ap-compose-input" style="width: 100%; text-align: left; cursor: pointer;">
          O que está acontecendo, <?php echo esc_html($user_data['name'] ?? 'usuário'); ?>?
        </button>
      </div>

      <!-- FILTER TABS -->
      <div style="display: flex; gap: 8px; overflow-x: auto;" class="no-scrollbar" id="feed-tabs">
        <button class="menutag" data-target="all" data-active="true">
          <i class="ri-apps-line"></i> Tudo
        </button>
        <button class="menutag" data-target="events">
          <i class="ri-calendar-event-line"></i> Eventos
        </button>
        <button class="menutag" data-target="community">
          <i class="ri-discuss-line"></i> Comunidades
        </button>
        <button class="menutag" data-target="market">
          <i class="ri-ticket-line"></i> Classificados
        </button>
      </div>
    </section>

    <!-- FEED CONTAINER -->
    <div id="feed-container" style="display: flex; flex-direction: column; gap: 0;">

      <!-- TAB: TUDO -->
      <div data-tab-panel="feed-all" class="feed-tab-content active">
        <?php if (empty($feed_posts)) : ?>
          <article class="ap-social-post feed-item">
            <div class="ap-social-card">
              <p class="ap-social-content" style="text-align: center; padding: 40px 20px;">
                Nenhum post ainda. Seja o primeiro a compartilhar!
              </p>
            </div>
          </article>
        <?php else : ?>
          <?php foreach ($feed_posts as $post_item) : ?>
            <?php
            $post_type_item = $post_item['type'] ?? 'user_post';
            $post_data = $post_item;

            if ($post_type_item === 'event') {
              include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-event.php';
            } elseif ($post_type_item === 'ad') {
              include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
            } elseif ($post_type_item === 'news') {
              include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
            } else {
              // Post de usuário (padrão).
              include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
            }
            ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- TAB: EVENTOS -->
      <div data-tab-panel="feed-events" class="feed-tab-content hidden">
        <article class="ap-social-post feed-item">
          <div class="ap-social-card">
            <p class="ap-social-content" style="text-align: center; padding: 40px 20px;">
              Eventos serão exibidos aqui.
            </p>
          </div>
        </article>
      </div>

      <!-- TAB: COMUNIDADES -->
      <div data-tab-panel="feed-communities" class="feed-tab-content hidden">
        <article class="ap-social-post feed-item">
          <div class="ap-social-card">
            <p class="ap-social-content" style="text-align: center; padding: 40px 20px;">
              Posts das comunidades serão exibidos aqui.
            </p>
          </div>
        </article>
      </div>

      <!-- TAB: SISTEMA -->
      <div data-tab-panel="feed-system" class="feed-tab-content hidden">
        <article class="ap-social-post feed-item">
          <div class="ap-social-card">
            <p class="ap-social-content" style="text-align: center; padding: 40px 20px;">
              Notificações do sistema serão exibidas aqui.
            </p>
          </div>
        </article>
      </div>

      <!-- Load More Button -->
      <div class="text-center py-4">
        <button id="apollo-feed-load-more" class="px-6 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors">
          <i class="ri-loader-4-line animate-spin hidden mr-2"></i>
          Carregar mais
        </button>
      </div>
    </div>

    <!-- LOADING INDICATOR -->
    <div id="loading-indicator" class="ap-loading hidden">
      <i class="ri-loader-4-line animate-spin"></i>
      Carregando mais posts...
    </div>

  </div>
</div>

<script type="module">
// === FILTER TABS ===
const tabs = document.querySelectorAll('#feed-tabs button');
const tabContents = document.querySelectorAll('.feed-tab-content');

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    // Update active tab button
    tabs.forEach(t => t.setAttribute('data-active', 'false'));
    tab.setAttribute('data-active', 'true');

    // Show corresponding content
    const target = tab.getAttribute('data-target');
    tabContents.forEach(content => {
      if (content.getAttribute('data-tab-panel') === `feed-${target}`) {
        content.classList.remove('hidden');
        content.classList.add('active');
      } else {
        content.classList.add('hidden');
        content.classList.remove('active');
      }
    });
  });
});

// === INFINITE SCROLL ===
const feedContainer = document.getElementById('feed-container');
const loadingIndicator = document.getElementById('loading-indicator');
const loadMoreBtn = document.getElementById('apollo-feed-load-more');
let isLoading = false;

window.addEventListener('scroll', () => {
  if (isLoading) return;

  const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

  if (scrollTop + clientHeight >= scrollHeight - 200) {
    loadMorePosts();
  }
});

function loadMorePosts() {
  if (isLoading) return;

  isLoading = true;
  loadingIndicator.classList.remove('hidden');

  // Simulate API call
  setTimeout(() => {
    // Clone a random post for demo
    const posts = document.querySelectorAll('.ap-social-post');
    if (posts.length > 0) {
      const randomPost = posts[Math.floor(Math.random() * posts.length)];
      const clone = randomPost.cloneNode(true);
      feedContainer.insertBefore(clone, loadMoreBtn.parentElement);
    }

    loadingIndicator.classList.add('hidden');
    isLoading = false;
  }, 1500);
}

console.log('🚀 Apollo Social Feed initialized');
</script>
			<p class="text-[15px] text-slate-800">Apollo está finalizando etapa de segurança. Em breve: produção.</p>
			</article>
		</div>
		</div>
	</div>
		</div><!-- /LEFT COLUMN -->

		<!-- RIGHT COLUMN: SIDEBAR (Sticky) - STRICT MODE DESIGN SPEC -->
	<aside class="hidden lg:block space-y-4">

		<!-- Calendar Widget: Próximos 7 dias -->
		<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4">
		<div class="flex items-center justify-between mb-3">
			<h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500">Próximos 7 dias</h2>
			<a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="text-[11px] text-neutral-600 hover:underline">Ver agenda</a>
		</div>
		<div class="space-y-2" id="sidebar-upcoming-events">
			<?php
            // SIDEBAR: Fetch próximos eventos.
            $upcoming_args = [
                'post_type'      => 'event_listing',
                'post_status'    => 'publish',
                'posts_per_page' => 4,
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for date ordering.
                'meta_key' => '_event_start_date',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for date filtering.
                'meta_query' => [
				    [
				        'key'     => '_event_start_date',
				        'value'   => wp_date('Y-m-d'),
				        'compare' => '>=',
				        'type'    => 'DATE',
				    ],
                ],
            ];
$upcoming_events = get_posts($upcoming_args);

if (! empty($upcoming_events)) :
    foreach ($upcoming_events as $event) :
        $event_date = get_post_meta($event->ID, '_event_start_date', true);
        $date_obj   = $event_date ? DateTime::createFromFormat('Y-m-d', $event_date) : null;
        $day        = $date_obj ? $date_obj->format('d') : '--';
        $day_name   = $date_obj ? strtoupper(substr([ 'DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB' ][ $date_obj->format('w') ], 0, 3)) : '???';

        // Contar favoritos como "amigos vão".
        $favorites_count = max(0, (int) get_post_meta($event->ID, '_favorites_count', true));
        ?>
			<a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="flex gap-3 items-center p-2 hover:bg-slate-50 rounded-lg transition-colors group">
			<div class="h-10 w-10 rounded-lg border border-slate-200 flex flex-col items-center justify-center bg-white text-slate-900 group-hover:border-slate-400 transition-colors">
				<span class="text-[9px] uppercase font-bold"><?php echo esc_html($day_name); ?></span>
				<span class="text-[13px] font-bold"><?php echo esc_html($day); ?></span>
			</div>
			<div class="min-w-0 flex-1">
				<div class="text-[12px] font-bold truncate"><?php echo esc_html($event->post_title); ?></div>
					<?php if ($favorites_count > 0) : ?>
				<div class="text-[10px] text-slate-500"><?php echo esc_html($favorites_count); ?> pessoas vão</div>
				<?php else : ?>
				<div class="text-[10px] text-slate-500" data-ap-tooltip="Seja o primeiro a demonstrar interesse!">Seja o primeiro!</div>
				<?php endif; ?>
			</div>
			</a>
					<?php
    endforeach;
else :
    ?>
			<div class="text-center py-4 text-slate-400" data-ap-tooltip="Eventos nos próximos 7 dias aparecerão aqui">
			<i class="ri-calendar-event-line text-2xl opacity-50"></i>
			<p class="text-[11px] mt-1">Nenhum evento nos próximos dias</p>
			</div>
			<?php endif; ?>
		</div>
		</div>

		<!-- Trending Communities -->
		<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4">
		<h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500 mb-3">Comunidades em alta</h2>
		<div class="space-y-3" id="sidebar-communities">
			<?php
            // SIDEBAR: Fetch grupos/comunidades.
            $groups_args = [
    'post_type'      => 'apollo_group',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'orderby'        => 'meta_value_num',
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for member count ordering.
    'meta_key' => '_group_members_count',
    'order'    => 'DESC',
            ];
$groups = get_posts($groups_args);

if (! empty($groups)) :
    $colors = [
        [
            'bg'   => 'bg-purple-100',
            'text' => 'text-purple-600',
        ],
        [
            'bg'   => 'bg-pink-100',
            'text' => 'text-pink-600',
        ],
        [
            'bg'   => 'bg-blue-100',
            'text' => 'text-blue-600',
        ],
        [
            'bg'   => 'bg-emerald-100',
            'text' => 'text-emerald-600',
        ],
    ];
    $i = 0;
    foreach ($groups as $group) :
        $color         = $colors[ $i % count($colors) ];
        $members       = (int) get_post_meta($group->ID, '_group_members_count', true);
        $activity_meta = get_post_meta($group->ID, '_group_activity_label', true);
        $activity      = ! empty($activity_meta) ? $activity_meta : 'Ativo';
        ?>
			<div class="flex items-center gap-3">
			<div class="h-8 w-8 rounded <?php echo esc_attr($color['bg'] . ' ' . $color['text']); ?> flex items-center justify-center">
				<i class="ri-group-line"></i>
			</div>
			<div class="flex-1 min-w-0">
				<div class="text-[12px] font-bold truncate"><?php echo esc_html($group->post_title); ?></div>
				<div class="text-[10px] text-slate-500"><?php echo esc_html($activity); ?></div>
			</div>
			<a href="<?php echo esc_url(get_permalink($group->ID)); ?>" class="text-[11px] font-semibold text-neutral-600 hover:text-neutral-900">Entrar</a>
			</div>
					<?php
        ++$i;
    endforeach;
else :
    ?>
			<div class="text-center py-4 text-slate-400" data-ap-tooltip="Comunidades ativas aparecerão aqui">
			<i class="ri-team-line text-2xl opacity-50"></i>
			<p class="text-[11px] mt-1">Nenhuma comunidade ativa</p>
			</div>
			<?php endif; ?>
		</div>
		</div>

		<!-- Footer Links -->
		<div class="flex flex-wrap gap-2 text-[10px] text-slate-400 px-2">
		<a href="<?php echo esc_url(home_url('/privacidade/')); ?>" class="hover:underline">Privacidade</a>
		<span>·</span>
		<a href="<?php echo esc_url(home_url('/termos/')); ?>" class="hover:underline">Termos</a>
		<span>·</span>
		<a href="<?php echo esc_url(home_url('/business/')); ?>" class="hover:underline">Apollo Business</a>
		<span>·</span>
		<span>© <?php echo esc_html(wp_date('Y')); ?> Apollo Rio</span>
		</div>
	</aside>
	</main>
</div>

<!-- Mobile Bottom Nav -->
<div class="md:hidden sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-slate-200/50 px-3 py-2 flex items-center justify-around">
	<button class="flex flex-col items-center gap-0.5 text-slate-600" title="<?php esc_attr_e( 'Descobrir Eventos', 'apollo-social' ); ?>">
	<i class="ri-calendar-line text-xl"></i>
	<span class="text-[9px]"><?php esc_html_e( 'Eventos', 'apollo-social' ); ?></span>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400">
	<i class="ri-bar-chart-line text-xl" title="<?php esc_attr_e( 'Comunidades', 'apollo-social' ); ?>"></i>
	<span class="text-[9px]"><?php esc_html_e( 'Comunas', 'apollo-social' ); ?></span>
	</button>
	<button id="btn-add-mobile" class="h-12 w-12 -mt-8 rounded-full bg-neutral-900 text-white flex items-center justify-center shadow-lg" title="<?php esc_attr_e( 'Adicionar Novo', 'apollo-social' ); ?>">
	<i class="ri-add-line text-2xl"></i>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400" title="<?php esc_attr_e( 'Chat', 'apollo-social' ); ?>">
	<i class="ri-team-line text-xl"></i>
	<span class="text-[9px]"><?php esc_html_e( 'Chats', 'apollo-social' ); ?></span>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400" title="<?php esc_attr_e( 'Configurações', 'apollo-social' ); ?>">
	<i class="ri-settings-3-line text-xl"></i>
	<span class="text-[9px]"><?php esc_html_e( 'Ajustes', 'apollo-social' ); ?></span>
	</button>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
	const { animate } = window.Motion || {};
	const root = document.getElementById("apollo-feed-root");
	if (!root) return;

	const tabs = document.querySelectorAll('[role="tab"]');
	const panels = document.querySelectorAll('[data-tab-panel]');

	function animatePanel(panel) {
	if (!animate) return;
	const cards = panel.querySelectorAll("[data-feed-card]");
	cards.forEach((card, index) => {
		animate(card,
		{ opacity: [0, 1], transform: ["translateY(30px)", "translateY(0px)"] },
		{ duration: 0.5, delay: index * 0.1, easing: [0.25, 0.8, 0.25, 1] }
		);
	});
	}

	function setActiveTab(targetId) {
	tabs.forEach((tab) => {
		const isActive = tab.getAttribute("data-tab-target") === targetId;
		tab.setAttribute("data-active", isActive ? "true" : "false");
	});

	panels.forEach((panel) => {
		if (panel.getAttribute("data-tab-panel") === targetId) {
		panel.classList.remove("hidden");
		animatePanel(panel);
		} else {
		panel.classList.add("hidden");
		}
	});
	}

	tabs.forEach((tab) => {
	tab.addEventListener("click", (e) => {
		e.preventDefault();
		const target = tab.getAttribute("data-tab-target");
		if (target) setActiveTab(target);
	});
	});

	setActiveTab("feed-all");

	// Like animation
	if (animate) {
	document.querySelectorAll(".ri-heart-3-line").forEach((heart) => {
		heart.closest('button')?.addEventListener("click", (e) => {
		e.preventDefault();
		animate(heart,
			{ scale: [1, 1.4, 1], rotate: [0, -20, 20, 0] },
			{ duration: 0.6, easing: "ease-in-out" }
		);
		heart.classList.toggle("ri-heart-3-line");
		heart.classList.toggle("ri-heart-3-fill");
		});
	});
	}
});
</script>

<style>
[data-feed-card] {
	animation: fadeIn 0.5s ease-out backwards;
}

[data-feed-card]:nth-child(1) { animation-delay: 0.1s; }
[data-feed-card]:nth-child(2) { animation-delay: 0.2s; }
[data-feed-card]:nth-child(3) { animation-delay: 0.3s; }
[data-feed-card]:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeIn {
	from {
	opacity: 0;
	transform: translateY(20px);
	}
	to {
	opacity: 1;
	transform: translateY(0);
	}
}

::-webkit-scrollbar {
	width: 6px;
	height: 6px;
}

::-webkit-scrollbar-track {
	background: transparent;
}

::-webkit-scrollbar-thumb {
	background: #cbd5e1;
	border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
	background: #94a3b8;
}

* {
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}
</style>

<script>
window.apolloFeedData = {
	ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
	restUrl: <?php echo wp_json_encode($rest_url); ?>,
	nonce: <?php echo wp_json_encode($nonce); ?>,
	commentNonce: <?php echo wp_json_encode($comment_nonce); ?>,
	currentUserId: <?php echo absint($user_data['id'] ?? 0); ?>,
	posts: <?php echo wp_json_encode($feed_posts); ?>
};
</script>
<?php
