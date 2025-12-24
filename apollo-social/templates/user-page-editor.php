<?php
/**
 * Template: Editor drag-and-drop da página do usuário
 * Usa Tailwind + shadcn + Muuri
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_query_var( 'apollo_user_id' );
if ( ! $user_id ) {
	wp_die( 'Usuário não encontrado' );
}

$user = get_userdata( $user_id );
if ( ! $user ) {
	wp_die( 'Usuário não encontrado' );
}

$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );
if ( ! $post_id ) {
	wp_die( 'Página do usuário não encontrada' );
}

// Check permissions
if ( get_current_user_id() != $user_id && ! current_user_can( 'edit_post', $post_id ) ) {
	wp_die( 'Acesso negado.' );
}

$layout = get_post_meta( $post_id, 'apollo_userpage_layout_v1', true );
if ( ! is_array( $layout ) ) {
	$layout = array( 'grid' => array() );
}

// STRICT MODE: base.js handles all core assets
if ( function_exists( 'apollo_ensure_base_assets' ) ) {
	apollo_ensure_base_assets();
}
wp_enqueue_script( 'sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true );

get_header();
?>

<div class="container max-w-4xl mx-auto px-4 py-8" data-user-id="<?php echo esc_attr( $user_id ); ?>">

	<!-- Header do Editor -->
	<header class="editor-header flex items-center justify-between mb-8">
	<div>
		<h1 class="text-3xl font-bold mb-1">Editar Página</h1>
		<p class="text-muted-foreground">Arraste e reorganize os blocos</p>
	</div>
	<div class="flex gap-2">
		<a href="<?php echo esc_url( remove_query_arg( 'action' ) ); ?>"
		class="btn btn-outline inline-flex items-center gap-2">
		<i class="ri-eye-line"></i>
		Visualizar
		</a>
		<button id="userpage-save" class="btn btn-primary inline-flex items-center gap-2">
		<i class="ri-save-line"></i>
		Salvar
		</button>
	</div>
	</header>

	<!-- Barra de Widgets Disponíveis -->
	<div class="widgets-toolbar bg-card border rounded-lg p-4 mb-6">
	<h3 class="font-semibold mb-3 flex items-center gap-2">
		<i class="ri-add-circle-line"></i>
		Adicionar Bloco
	</h3>
	<div class="grid grid-cols-2 md:grid-cols-4 gap-2">
		<?php
		$widgets = Apollo_User_Page_Widgets::get_widgets();
		foreach ( $widgets as $widget_id => $widget ) :
			?>
		<button class="add-widget-btn btn btn-outline text-sm py-2"
				data-widget="<?php echo esc_attr( $widget_id ); ?>">
			<i class="ri-<?php echo esc_attr( $widget['icon'] ?? 'puzzle' ); ?>-line"></i>
			<?php echo esc_html( $widget['title'] ); ?>
		</button>
		<?php endforeach; ?>
	</div>
	</div>

	<!-- Editor Drag-and-Drop -->
	<div id="userpage-editor" class="editor-grid grid gap-4 md:grid-cols-2 min-h-[300px] border-2 border-dashed rounded-lg p-4 bg-muted/20">
	<?php if ( ! empty( $layout['grid'] ) ) : ?>
		<?php
		foreach ( $layout['grid'] as $index => $widget_data ) :
			$widget_id = $widget_data['widget'] ?? '';
			$widget    = $widgets[ $widget_id ] ?? null;
			if ( ! $widget ) {
				continue;
			}
			?>
		<div class="widget-item card p-4 bg-card border rounded-lg cursor-move"
			data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
			data-index="<?php echo esc_attr( $index ); ?>">
			<div class="widget-header flex items-center justify-between mb-2">
			<span class="font-semibold text-sm flex items-center gap-2">
				<i class="ri-<?php echo esc_attr( $widget['icon'] ?? 'puzzle' ); ?>-line"></i>
				<?php echo esc_html( $widget['title'] ); ?>
			</span>
			<button class="remove-widget-btn text-destructive hover:bg-destructive/10 rounded p-1">
				<i class="ri-delete-bin-line"></i>
			</button>
			</div>
			<div class="widget-preview text-xs text-muted-foreground">
			Preview do widget...
			</div>
		</div>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="col-span-full text-center text-muted-foreground py-8">
		Adicione blocos para começar
		</p>
	<?php endif; ?>
	</div>

</div>

<script>
// Editor Drag-and-Drop com SortableJS
document.addEventListener('DOMContentLoaded', function() {
	const editor = document.getElementById('userpage-editor');
	if (!editor) return;

	// Inicializa Sortable with Motion.dev integration
	const sortable = Sortable.create(editor, {
	animation: 200,
	handle: '.widget-item',
	ghostClass: 'opacity-50',
	chosenClass: 'ring-2 ring-primary shadow-lg',
	dragClass: 'shadow-xl scale-105',
	forceFallback: true, // Better performance
	fallbackOnBody: true,
	swapThreshold: 0.65,
	onEnd: function(evt) {
		// Animate widgets after reorder with Motion.dev
		if (typeof window.motion !== 'undefined') {
		const items = editor.querySelectorAll('.widget-item');
		items.forEach((item, index) => {
			window.motion.animate(item, {
			y: [10, 0],
			opacity: [0.8, 1]
			}, {
			duration: 0.3,
			delay: index * 0.05,
			easing: 'ease-out'
			});
		});
		}
	}
	});

	// Adicionar widget
	document.querySelectorAll('.add-widget-btn').forEach(btn => {
	btn.addEventListener('click', function() {
		const widgetId = this.dataset.widget;
		const widgetTitle = this.textContent.trim();
		const widgetIcon = this.querySelector('i').className;

		// ShadCN Card structure with Motion.dev
		const widgetHtml = `
		<div class="widget-item shadcn-card rounded-lg border bg-card p-4 cursor-move shadow-sm"
			data-widget-id="${widgetId}"
			data-motion-item="true"
			style="opacity: 0; transform: scale(0.95);">
			<div class="widget-header flex items-center justify-between mb-2">
			<span class="font-semibold text-sm flex items-center gap-2">
				<i class="${widgetIcon}"></i>
				${widgetTitle}
			</span>
			<button class="remove-widget-btn shadcn-button-ghost h-8 w-8 p-0 text-destructive hover:bg-destructive/10 rounded" aria-label="Remover widget">
				<i class="ri-delete-bin-line"></i>
			</button>
			</div>
			<div class="widget-preview text-xs text-muted-foreground">
			Preview do widget...
			</div>
		</div>
		`;

		editor.insertAdjacentHTML('beforeend', widgetHtml);

		// Animate new widget with Motion.dev
		const newWidget = editor.lastElementChild;
		if (typeof window.motion !== 'undefined' && newWidget) {
		window.motion.animate(newWidget, {
			opacity: [0, 1],
			scale: [0.95, 1]
		}, {
			duration: 0.3,
			easing: 'ease-out'
		}).then(() => {
			newWidget.style.opacity = '1';
			newWidget.style.transform = 'scale(1)';
		});
		}

		attachRemoveListeners();
	});
	});

	// Remover widget with Motion.dev animation
	function attachRemoveListeners() {
	document.querySelectorAll('.remove-widget-btn').forEach(btn => {
		btn.onclick = function() {
		const widget = this.closest('.widget-item');
		if (!widget) return;

		// Animate out with Motion.dev
		if (typeof window.motion !== 'undefined') {
			window.motion.animate(widget, {
			opacity: [1, 0],
			scale: [1, 0.9],
			y: [0, -10]
			}, {
			duration: 0.2,
			easing: 'ease-in'
			}).then(() => {
			widget.remove();
			});
		} else {
			widget.remove();
		}
		};
	});
	}
	attachRemoveListeners();

	// Salvar layout
	document.getElementById('userpage-save').addEventListener('click', function() {
	const items = Array.from(editor.querySelectorAll('.widget-item')).map((item, index) => ({
		widget: item.dataset.widgetId,
		order: index,
		props: {}
	}));

	const layout = {
		version: 1,
		grid: items,
		updatedAt: new Date().toISOString(),
		updatedBy: <?php echo get_current_user_id(); ?>
	};

	const userId = document.querySelector('[data-user-id]').dataset.userId;

	fetch(ajaxurl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams({
		action: 'apollo_userpage_save',
		nonce: '<?php echo wp_create_nonce( 'apollo_userpage_save' ); ?>',
		user_id: userId,
		layout: JSON.stringify(layout)
		})
	})
	.then(r => r.json())
	.then(resp => {
		if (resp.success) {
		alert('✅ Salvo com sucesso!');
		} else {
		alert('❌ Erro: ' + (resp.data || 'Erro desconhecido'));
		}
	})
	.catch(err => {
		console.error(err);
		alert('❌ Erro de conexão');
	});
	});
});
</script>

<?php get_footer(); ?>
