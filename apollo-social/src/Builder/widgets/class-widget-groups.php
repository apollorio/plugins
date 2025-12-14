<?php
/**
 * Apollo Widget: Groups
 *
 * Shows logos/names of groups user belongs to.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined('ABSPATH') || exit;

class Apollo_Widget_Groups extends Apollo_Widget_Base
{
    public function get_name()
    {
        return 'groups';
    }

    public function get_title()
    {
        return __('Núcleo / Comunidades', 'apollo-social');
    }

    public function get_icon()
    {
        return 'dashicons-groups';
    }

    public function get_description()
    {
        return __('Display groups and communities you belong to.', 'apollo-social');
    }

    public function get_tooltip()
    {
        return __('Shows group logos and names. Data comes from apollo_social_user_groups filter.', 'apollo-social');
    }

    public function get_default_width()
    {
        return 200;
    }

    public function get_default_height()
    {
        return 180;
    }

    /**
     * Settings
     */
    public function get_settings()
    {
        return [
            'max_groups' => $this->field(
                'slider',
                __('Max Groups', 'apollo-social'),
                6,
                [
                    'min' => 1,
                    'max' => 20,
                ]
            ),
            'show_names' => $this->field('switch', __('Show Group Names', 'apollo-social'), true),
            'layout'     => $this->field(
                'select',
                __('Layout', 'apollo-social'),
                'grid',
                [
                    'options' => [
                        'grid' => __('Grid', 'apollo-social'),
                        'list' => __('List', 'apollo-social'),
                    ],
                ]
            ),
        ];
    }

    /**
     * Render widget
     *
     * Data source: apollo_social_user_groups filter
     * Tooltip: Expected format: [['id' => '1', 'name' => 'Name', 'logo_url' => 'url', 'url' => 'link']]
     */
    public function render($data)
    {
        $settings = $data['settings'] ?? [];
        $post_id  = $data['post_id']  ?? 0;

        $user = $this->get_post_author($post_id);
        if (! $user) {
            return '<div class="apollo-widget-groups apollo-widget-error">'
                . __('User not found', 'apollo-social')
                . '</div>';
        }

        /**
         * Filter: apollo_social_user_groups
         *
         * @param array $groups Empty array
         * @param int $user_id User ID
         */
        $groups = apply_filters('apollo_social_user_groups', [], $user->ID);

        $max_groups = absint($settings['max_groups'] ?? 6);
        $show_names = ! empty($settings['show_names']);
        $layout     = $settings['layout'] ?? 'grid';

        // Limit groups
        $groups = array_slice($groups, 0, $max_groups);

        ob_start();
        ?>
		<div class="apollo-widget-groups groups-layout-<?php echo $this->esc($layout, 'attr'); ?>">
			<h4 class="widget-title">
				<span class="dashicons dashicons-groups"></span>
				<?php _e('Meus Grupos', 'apollo-social'); ?>
			</h4>
			
			<?php if (empty($groups)) : ?>
				<p class="groups-empty"><?php _e('Nenhum grupo ainda', 'apollo-social'); ?></p>
			<?php else : ?>
				<div class="groups-list">
					<?php
                    foreach ($groups as $group) :
                        $name = $group['name']     ?? '';
                        $logo = $group['logo_url'] ?? '';
                        $url  = $group['url']      ?? '#';
                        ?>
						<a href="<?php echo $this->esc($url, 'url'); ?>" 
							class="group-item" 
							title="<?php echo $this->esc($name, 'attr'); ?>">
							<?php if ($logo) : ?>
								<img src="<?php echo $this->esc($logo, 'url'); ?>" 
									alt="<?php echo $this->esc($name, 'attr'); ?>"
									class="group-logo"
									loading="lazy">
							<?php else : ?>
								<span class="dashicons dashicons-admin-multisite group-logo-placeholder"></span>
							<?php endif; ?>
							<?php if ($show_names && $name) : ?>
								<span class="group-name"><?php echo $this->esc($name); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
        return ob_get_clean();
    }
}
