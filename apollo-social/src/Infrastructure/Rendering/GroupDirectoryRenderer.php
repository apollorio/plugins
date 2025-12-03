<?php
namespace Apollo\Infrastructure\Rendering;

use Apollo\Domain\Groups\Repositories\GroupsRepository;

/**
 * Group Directory Renderer
 *
 * Renders group directory pages (/comunidade/, /nucleo/, /season/)
 */
class GroupDirectoryRenderer {

	private GroupsRepository $repository;

	public function __construct() {
		$this->repository = new GroupsRepository();
	}
	/**
	 * Render group directory page
	 */
	public function render( $template_data ) {
		$type = $template_data['type'];
		// comunidade, nucleo, season

		// Get group type configuration
		$group_config = $this->getGroupConfig( $type );
		$groups       = $this->getGroupsData( $type );

		return array(
			'title'        => $group_config['label_plural'],
			'content'      => $this->renderGroupDirectory( $groups, $group_config ),
			'breadcrumbs'  => array( 'Apollo Social', $group_config['label_plural'] ),
			'groups'       => $groups,
			'group_config' => $group_config,
		);
	}

	/**
	 * Get group type configuration
	 */
	private function getGroupConfig( $type ) {
		$config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/groups.php';
		if ( file_exists( $config_file ) ) {
			$config = require $config_file;
			if ( isset( $config['types'][ $type ] ) ) {
				return $config['types'][ $type ];
			}
		}

		// Fallback
		return array(
			'label'        => ucfirst( $type ),
			'label_plural' => ucfirst( $type ) . 's',
			'description'  => 'Grupos do tipo ' . $type,
		);
	}

	/**
	 * Get groups data from repository
	 */
	private function getGroupsData( $type ) {
		$filters = array(
			'type'   => $type,
			'status' => 'published',
		// Only show published groups
		);

		// Allow admins to see all statuses
		if ( current_user_can( 'manage_options' ) ) {
			unset( $filters['status'] );
		}

		$group_entities = $this->repository->findAll( $filters );

		$groups = array();
		foreach ( $group_entities as $group ) {
			$groups[] = array(
				'id'            => $group->id,
				'name'          => $group->title,
				'slug'          => $group->slug,
				'description'   => $group->description,
				'members_count' => $group->members_count ?? 0,
				'type'          => $group->type,
				'url'           => '/' . $type . '/' . $group->slug,
				'created_at'    => $group->created_at,
			);
		}

		return $groups;
	}

	/**
	 * Render group directory content with ShadCN components
	 */
	private function renderGroupDirectory( $groups, $group_config ) {
		ob_start();
		?>
		<div class="apollo-group-directory apollo-container" data-motion-page="group-directory">
			<!-- ShadCN Card Header -->
			<div class="shadcn-card rounded-lg border bg-card mb-6">
				<div class="shadcn-card-header">
					<h1 class="shadcn-card-title text-3xl font-bold"><?php echo esc_html( $group_config['label_plural'] ); ?></h1>
					<p class="shadcn-card-description text-muted-foreground mt-2"><?php echo esc_html( $group_config['description'] ); ?></p>
				</div>
			</div>
			
			<!-- Groups Grid - Mobile-first responsive -->
			<div class="apollo-groups-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-motion-list="true">
				<?php foreach ( $groups as $index => $group ) : ?>
					<!-- ShadCN Card for each group -->
					<div class="apollo-group-card shadcn-card rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow" 
						data-motion-item="true" 
						data-motion-delay="<?php echo $index * 50; ?>"
						style="opacity: 0; transform: translateY(10px);">
						<div class="shadcn-card-header pb-3">
							<h3 class="shadcn-card-title text-xl">
								<a href="<?php echo esc_attr( $group['url'] ); ?>" class="hover:text-primary transition-colors">
									<?php echo esc_html( $group['name'] ); ?>
								</a>
							</h3>
						</div>
						<div class="shadcn-card-content">
							<p class="text-sm text-muted-foreground mb-4 line-clamp-3">
								<?php echo esc_html( $group['description'] ); ?>
							</p>
							<div class="flex items-center justify-between text-xs text-muted-foreground mb-4">
								<span class="flex items-center gap-1">
									<i class="ri-group-line"></i>
									<?php echo intval( $group['members_count'] ); ?> membros
								</span>
								<?php if ( ! empty( $group['created_at'] ) ) : ?>
								<span class="flex items-center gap-1">
									<i class="ri-calendar-line"></i>
									<?php echo date_i18n( 'M Y', strtotime( $group['created_at'] ) ); ?>
								</span>
								<?php endif; ?>
							</div>
						</div>
						<div class="shadcn-card-footer pt-0">
							<a href="<?php echo esc_attr( $group['url'] ); ?>" 
								class="shadcn-button shadcn-button-primary w-full">
								Ver Grupo
								<i class="ri-arrow-right-line ml-2"></i>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<?php if ( empty( $groups ) ) : ?>
			<div class="shadcn-card rounded-lg border bg-card p-8 text-center">
				<i class="ri-group-line text-4xl text-muted-foreground mb-4"></i>
				<p class="text-muted-foreground">Nenhum grupo encontrado.</p>
			</div>
			<?php endif; ?>
		</div>
		
		<script>
		// Motion.dev initialization for group cards
		(function() {
			if (typeof window.motion !== 'undefined') {
				const items = document.querySelectorAll('[data-motion-item="true"]');
				items.forEach(function(item, index) {
					const delay = parseInt(item.dataset.motionDelay || 0);
					setTimeout(function() {
						window.motion.animate(item, {
							opacity: [0, 1],
							y: [10, 0]
						}, {
							duration: 0.4,
							easing: 'ease-out'
						}).then(function() {
							item.style.opacity = '1';
							item.style.transform = 'translateY(0)';
						});
					}, delay);
				});
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}
}
