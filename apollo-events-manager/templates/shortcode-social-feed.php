<?php
/**
 * Social Feed Template
 * Shortcode: [apollo_social_feed]
 * 
 * @package Apollo_Events_Manager
 */

if (!defined('ABSPATH')) exit;

// Get current user
$current_user = wp_get_current_user();
$user_id = is_user_logged_in() ? $current_user->ID : 0;
$display_name = is_user_logged_in() ? $current_user->display_name : 'Visitante';
$avatar_url = is_user_logged_in() ? get_avatar_url($user_id, array('size' => 40)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=Guest';

// Get greeting
$hour = (int) date('H');
$greeting = ($hour < 12) ? 'bom dia' : (($hour < 18) ? 'boa tarde' : 'boa noite');

// Enqueue assets
wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com', [], null, true);
wp_enqueue_style('apollo-uni', 'https://assets.apollo.rio.br/uni.css', [], null);
wp_enqueue_script('motion', 'https://cdn.jsdelivr.net/npm/motion@10.17.0/dist/motion.js', [], null, true);
wp_enqueue_script('apollo-base', 'https://assets.apollo.rio.br/base.js', [], null, true);

// Get recent events for feed
$recent_events = get_posts(array(
    'post_type' => 'event_listing',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-slate-50 antialiased">
<head>
  <meta charset="UTF-8" />
  <title>Apollo :: Feed Social</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
</head>
<body>

  <!-- TOP HEADER (Sticky) -->
  <header class="sticky top-0 z-40 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6">
    <div class="flex items-center gap-3">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="h-9 w-9 rounded-[4px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
        <i class="ri-slack-fill text-white text-[28px]"></i>
      </a>
      <div class="flex flex-col leading-none">
        <h1 class="text-[16px] font-extrabold mt-2 text-slate-900">
          <?php echo esc_html($display_name); ?>, <span id="check-time-for-greetings"><?php echo esc_html($greeting); ?></span>!
        </h1>
        <p class="text-[13px] text-slate-500">
          @<?php echo esc_html(is_user_logged_in() ? $current_user->user_login : 'visitante'); ?>
          <?php if (is_user_logged_in()): ?>
            <span class="text-[10px]" id="membershipds"><?php echo esc_html(get_user_meta($user_id, 'membership', true) ?: 'MEMBRO'); ?></span>
          <?php endif; ?>
        </p>
      </div>
    </div>
    
    <!-- Desktop Search & Actions -->
    <div class="hidden md:flex items-center gap-3 text-[12px]">
      <div class="relative group">
        <i class="ri-search-line text-slate-400 absolute left-3 top-1.5 text-xs group-focus-within:text-slate-600"></i>
        <input type="text" placeholder="Buscar na cena..." class="pl-8 pr-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-[12px] w-64 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:bg-white transition-all" />
      </div>
      <div class="h-4 w-px bg-slate-200 mx-1"></div>
      <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Eventos</a>
      <a href="#" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Comunidades</a>
      
      <?php if (is_user_logged_in()): ?>
      <button class="ml-2 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 transition-all">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="h-full w-full rounded-full object-cover" />
      </button>
      <?php else: ?>
      <a href="<?php echo esc_url(wp_login_url()); ?>" class="ml-2 px-3 py-1.5 bg-slate-900 text-white rounded-full text-[11px] font-medium">Entrar</a>
      <?php endif; ?>
    </div>

    <!-- Mobile Header Actions -->
    <div class="flex md:hidden items-center gap-3">
      <button class="text-slate-500 hover:text-slate-900">
        <i class="ri-search-line text-xl"></i>
      </button>
      <button class="text-slate-500 hover:text-slate-900">
        <i class="ri-notification-3-line text-xl"></i>
      </button>
    </div>
  </header>

  <!-- MAIN CONTENT LAYOUT -->
  <main class="flex justify-center px-0 md:px-6 py-0 md:py-6 pb-24 md:pb-6">
    <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6">
      
      <!-- LEFT COLUMN: FEED STREAM -->
      <div class="space-y-4">
        
        <!-- Feed Controls / Create Post -->
        <section class="bg-white md:rounded-xl border-b md:border border-slate-200 p-4 sticky md:static top-14 z-30">
          <?php if (is_user_logged_in()): ?>
          <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-full bg-slate-100 overflow-hidden shrink-0">
              <img src="<?php echo esc_url($avatar_url); ?>" class="h-full w-full" />
            </div>
            <button class="flex-1 text-left bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-500 text-[13px] px-4 py-2.5 rounded-full transition-colors">
              No que você está pensando, <?php echo esc_html(explode(' ', $display_name)[0]); ?>?
            </button>
          </div>
          <?php endif; ?>

          <!-- Tabs Filter -->
          <div class="flex items-center justify-between overflow-x-auto no-scrollbar pb-1">
            <div class="flex gap-2" id="feed-tabs">
              <button class="menutag" data-target="all" data-active="true">
                <i class="ri-apps-line"></i> Tudo
              </button>
              <button class="menutag" data-target="events">
                <i class="ri-calendar-event-line"></i> Eventos
              </button>
              <button class="menutag" data-target="community">
                <i class="ri-discuss-line"></i> Comunidades
              </button>
            </div>
          </div>
        </section>

        <!-- FEED STREAM -->
        <div id="feed-container" class="space-y-4 px-2 md:px-0">
          <?php if (!empty($recent_events)): ?>
            <?php foreach ($recent_events as $event): 
              $event_date = get_post_meta($event->ID, '_event_start_date', true);
              $local_id = 0;
              if (function_exists('apollo_get_primary_local_id')) {
                  $local_id = apollo_get_primary_local_id($event->ID);
              } else {
                  $local_ids_meta = get_post_meta($event->ID, '_event_local_ids', true);
                  if (!empty($local_ids_meta)) {
                      $local_id = is_array($local_ids_meta) ? (int) reset($local_ids_meta) : (int) $local_ids_meta;
                  }
              }
              $local_name = '';
              if ($local_id) {
                  $local_name = get_post_meta($local_id, '_local_name', true);
                  if (empty($local_name)) {
                      $local_name = get_the_title($local_id);
                  }
              }
              $author = get_userdata($event->post_author);
            ?>
            <article class="aprioEXP-card-shell p-4 flex gap-3 feed-item" data-type="events">
              <div class="shrink-0">
                <div class="h-10 w-10 rounded-full overflow-hidden border border-slate-200">
                  <?php echo get_avatar($event->post_author, 40, '', '', array('class' => 'h-full w-full object-cover')); ?>
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-[13px] text-slate-900"><?php echo esc_html($author ? $author->display_name : 'Apollo'); ?></span>
                    </div>
                    <div class="text-[11px] text-slate-500">@<?php echo esc_html($author ? $author->user_login : 'apollo'); ?> · <?php echo human_time_diff(get_the_time('U', $event->ID), current_time('timestamp')); ?></div>
                  </div>
                  <button class="text-slate-400 hover:text-slate-800"><i class="ri-more-fill"></i></button>
                </div>
                
                <p class="mt-2 text-[13px] leading-relaxed text-slate-800">
                  <?php echo esc_html(wp_trim_words($event->post_content ?: $event->post_title, 30)); ?>
                </p>

                <!-- Event Attachment -->
                <div class="mt-3 border border-slate-200 rounded-lg overflow-hidden bg-slate-50 flex hover:bg-slate-100 cursor-pointer transition-colors">
                  <div class="w-16 bg-slate-900 text-white flex flex-col items-center justify-center p-2 shrink-0">
                    <?php if ($event_date): ?>
                      <span class="text-[10px] uppercase font-bold tracking-wider"><?php echo date_i18n('D', strtotime($event_date)); ?></span>
                      <span class="text-xl font-bold leading-none"><?php echo date_i18n('j', strtotime($event_date)); ?></span>
                    <?php else: ?>
                      <span class="text-[10px] uppercase font-bold tracking-wider">EVENTO</span>
                    <?php endif; ?>
                  </div>
                  <div class="p-3 flex-1 min-w-0">
                    <h3 class="font-bold text-[13px] truncate"><?php echo esc_html($event->post_title); ?></h3>
                    <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-1">
                      <i class="ri-map-pin-line"></i>
                      <?php echo esc_html($local_name ?: 'Local a definir'); ?>
                      <?php if ($event_date): ?>
                        · <?php echo date_i18n('H:i', strtotime($event_date)); ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <div class="flex items-center pr-3">
                    <i class="ri-arrow-right-s-line text-slate-400"></i>
                  </div>
                </div>

                <!-- Actions -->
                <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                  <div class="flex gap-6">
                    <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-pink-600 transition-colors">
                      <i class="ri-heart-3-line text-base group-hover:scale-110 transition-transform"></i>
                      <span>0</span>
                    </button>
                    <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-orange-600 transition-colors">
                      <i class="ri-chat-3-line text-base group-hover:scale-110 transition-transform"></i>
                      <span>0</span>
                    </button>
                  </div>
                  <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="text-slate-400 hover:text-slate-900">
                    <i class="ri-bookmark-line text-base"></i>
                  </a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="aprioEXP-card-shell p-8 text-center text-slate-500">
              <i class="ri-calendar-event-line text-4xl mb-2"></i>
              <p>Nenhum evento no feed ainda.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT COLUMN: SIDEBAR -->
      <aside class="hidden lg:block space-y-4">
        <!-- Calendar Widget -->
        <div class="aprioEXP-card-shell p-4">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500">Próximos 7 dias</h2>
            <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="text-[11px] text-neutral-600 hover:underline">Ver agenda</a>
          </div>
          <div class="space-y-2">
            <?php
            $upcoming_events = get_posts(array(
                'post_type' => 'event_listing',
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_event_start_date',
                        'value' => current_time('mysql'),
                        'compare' => '>='
                    )
                ),
                'orderby' => 'meta_value',
                'meta_key' => '_event_start_date',
                'order' => 'ASC'
            ));
            foreach ($upcoming_events as $event):
                $event_date = get_post_meta($event->ID, '_event_start_date', true);
            ?>
            <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="flex gap-3 items-center p-2 hover:bg-slate-50 rounded-lg transition-colors group">
              <div class="h-10 w-10 rounded-lg border border-slate-200 flex flex-col items-center justify-center bg-white text-slate-900 group-hover:border-slate-400 transition-colors">
                <?php if ($event_date): ?>
                  <span class="text-[9px] uppercase font-bold"><?php echo date_i18n('D', strtotime($event_date)); ?></span>
                  <span class="text-[13px] font-bold"><?php echo date_i18n('j', strtotime($event_date)); ?></span>
                <?php endif; ?>
              </div>
              <div class="min-w-0">
                <div class="text-[12px] font-bold truncate"><?php echo esc_html($event->post_title); ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <!-- MOBILE BOTTOM NAV -->
  <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 pb-safe-area z-50">
    <div class="grid grid-cols-5 h-14">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="flex flex-col items-center justify-center text-slate-900 gap-1">
        <i class="ri-home-5-fill text-xl"></i>
      </a>
      <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-search-2-line text-xl"></i>
      </button>
      <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="flex flex-col items-center justify-center text-slate-900 gap-1 -mt-6">
        <div class="h-12 w-12 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-lg shadow-slate-900/20">
          <i class="ri-add-line text-2xl"></i>
        </div>
      </a>
      <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-calendar-event-line text-xl"></i>
      </a>
      <a href="<?php echo esc_url(is_user_logged_in() ? home_url('/my-apollo/') : wp_login_url()); ?>" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-user-3-line text-xl"></i>
      </a>
    </div>
  </nav>

  <script type="module">
    import { animate, stagger } from "https://cdn.jsdelivr.net/npm/motion@10.17.0/dist/motion.js";

    // Entry Animation for Feed Items
    animate(
      ".feed-item",
      { opacity: [0, 1], y: [20, 0] },
      { delay: stagger(0.1), duration: 0.4, easing: "ease-out" }
    );

    // Tabs Logic
    const tabs = document.querySelectorAll('#feed-tabs button');
    const feedItems = document.querySelectorAll('.feed-item');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => {
          t.setAttribute('data-active', 'false');
          t.style.backgroundColor = 'white';
          t.style.color = '#475569';
        });
        
        tab.setAttribute('data-active', 'true');
        tab.style.backgroundColor = '#0f172a';
        tab.style.color = 'white';

        const target = tab.getAttribute('data-target');
        feedItems.forEach(item => {
          const itemType = item.getAttribute('data-type');
          if (target === 'all' || itemType === target) {
            item.style.display = 'flex';
            animate(item, { opacity: [0, 1], scale: [0.98, 1] }, { duration: 0.2 });
          } else {
            item.style.display = 'none';
          }
        });
      });
    });
  </script>

</body>
</html>

