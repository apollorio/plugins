<?php

namespace Apollo\Modules\Shortcodes;

use WP_Query;

class ShortcodeServiceProvider
{

	public function register(): void
	{
		add_action('init', array($this, 'registerShortcodes'));
	}

	public function registerShortcodes(): void
	{
		add_shortcode('apollo_event_list', array($this, 'renderEventList'));
		add_shortcode('apollo_profile_card', array($this, 'renderProfileCard'));
	}

	public function renderEventList($atts = array()): string
	{
		$atts = shortcode_atts(
			array(
				'limit'        => 6,
				'order'        => 'ASC',
				'order_by'     => 'meta_value',
				'show_excerpt' => true,
			),
			$atts,
			'apollo_event_list'
		);

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => (int) $atts['limit'],
				'post_status'    => 'publish',
				'orderby'        => $atts['order_by'],
				'order'          => $atts['order'],
				'meta_key'       => '_event_start_date',
			)
		);

		if (! $query->have_posts()) {
			wp_reset_postdata();

			return '<div class="apollo-empty-state"><p>' . esc_html__('Nenhum evento encontrado.', 'apollo-social') . '</p></div>';
		}

		// Apollo CDN Loader - handles all CSS/JS from CDN automatically
		if (! wp_script_is('apollo-cdn-loader', 'registered')) {
			wp_register_script(
				'apollo-cdn-loader',
				'https://assets.apollo.rio.br/index.min.js',
				array(),
				'3.1.0',
				false
			);
		}
		wp_enqueue_script('apollo-cdn-loader');

		// Load event data helper from apollo-events-manager
		$helper_path = WP_PLUGIN_DIR . '/apollo-events-manager/includes/helpers/event-data-helper.php';
		if (file_exists($helper_path)) {
			require_once $helper_path;
		}

		ob_start();
?>
		<div class="apollo-grid apollo-grid--3">
			<?php
			while ($query->have_posts()) :
				$query->the_post();
			?>
				<?php
				$event_id   = get_the_ID();
				$start_date = get_post_meta($event_id, '_event_start_date', true);
				$start_time = get_post_meta($event_id, '_event_start_time', true);

				// Use helper if available, fallback to direct meta
				$cover = '';
				if (class_exists('\Apollo_Event_Data_Helper')) {
					$cover = \Apollo_Event_Data_Helper::get_banner_url($event_id);
				} else {
					$cover = get_post_meta($event_id, '_event_banner', true);
					if (! $cover) {
						$cover = get_the_post_thumbnail_url($event_id, 'large');
					}
				}
				?>
				<article class="apollo-card apollo-card--elevated apollo-card--glass">
					<?php if ($cover) : ?>
						<div class="apollo-card__media">
							<img src="<?php echo esc_url($cover); ?>" alt="<?php the_title_attribute(); ?>">
						</div>
					<?php endif; ?>
					<div class="apollo-card__body">
						<div class="apollo-card__meta">
							<?php if ($start_date) : ?>
								<span class="apollo-badge apollo-badge--outline">
									<i class="ri-calendar-line"></i>
									<?php echo esc_html(date_i18n('d M Y', strtotime($start_date))); ?>
									<?php if ($start_time) : ?>
										• <?php echo esc_html($start_time); ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
						<h3 class="apollo-card__title"><?php the_title(); ?></h3>
						<?php if ($atts['show_excerpt']) : ?>
							<p class="apollo-card__description"><?php echo wp_trim_words(get_the_excerpt(), 18); ?></p>
						<?php endif; ?>
					</div>
					<footer class="apollo-card__footer">
						<a class="apollo-btn apollo-btn--ghost apollo-btn--sm" href="<?php the_permalink(); ?>">
							<?php esc_html_e('Ver evento', 'apollo-social'); ?>
							<i class="ri-arrow-right-up-line"></i>
						</a>
					</footer>
				</article>
			<?php endwhile; ?>
		</div>
	<?php

		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	public function renderProfileCard($atts = array(), $content = null): string
	{
		$atts = shortcode_atts(
			array(
				'user_id'  => get_current_user_id(),
				'title'    => '',
				'subtitle' => '',
			),
			$atts,
			'apollo_profile_card'
		);

		$userId = absint($atts['user_id']);
		if (! $userId) {
			return '';
		}

		$displayName = get_the_author_meta('display_name', $userId);
		$avatar      = get_avatar_url($userId, array('size' => 128));

		ob_start();
	?>
		<div class="apollo-card apollo-card--profile">
			<div class="apollo-card__body">
				<div class="apollo-card__avatar">
					<img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($displayName); ?>">
				</div>
				<div class="apollo-card__content">
					<h3 class="apollo-card__title"><?php echo esc_html($atts['title'] ?: $displayName); ?></h3>
					<?php if ($atts['subtitle']) : ?>
						<p class="apollo-card__subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
					<?php endif; ?>
					<?php if ($content) : ?>
						<div class="apollo-card__description">
							<?php echo wpautop(do_shortcode($content)); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
<?php

		return (string) ob_get_clean();
	}
}
