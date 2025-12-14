<?php
/**
 * Template: Plano Image Editor
 *
 * WordPress template for the Plano creative studio editor.
 * NO TAILWIND - Uses Apollo design system (ap-*, ario-*)
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check permissions.
if ( ! is_user_logged_in() ) {
	wp_die( esc_html__( 'Você precisa estar logado para acessar o editor.', 'apollo-social' ) );
}

// Get current user.
$current_user = wp_get_current_user();

// Enqueue assets (handled by Apollo_Plano_Editor_Assets).
get_header();
?>

<div class="ap-plano-editor">
	<!-- Logo -->
	<img src="<?php echo esc_url( 'https://vertente.apollo.rio.br/i.png' ); ?>" 
		 class="ap-plano-logo" 
		 id="ap-plano-bal-dropper" 
		 alt="<?php echo esc_attr__( 'Apollo', 'apollo-social' ); ?>" 
		 draggable="true" />

	<!-- Top Menu -->
	<header class="ap-plano-top-menu">
		<div class="ap-plano-top-menu-blur"></div>
		<div class="ap-plano-menu-container">
			<div class="ap-plano-undo-redo-group">
				<button class="ap-plano-undo-redo-btn" 
						id="ap-plano-undo-btn" 
						title="<?php echo esc_attr__( 'Desfazer (Ctrl+Z)', 'apollo-social' ); ?>" 
						disabled>
					<i class="ri-arrow-go-back-line"></i>
				</button>
				<button class="ap-plano-undo-redo-btn" 
						id="ap-plano-redo-btn" 
						title="<?php echo esc_attr__( 'Refazer (Ctrl+Y)', 'apollo-social' ); ?>" 
						disabled>
					<i class="ri-arrow-go-forward-line"></i>
				</button>
			</div>

			<button class="ap-plano-side-btn" 
					id="ap-plano-download-btn" 
					title="<?php echo esc_attr__( 'Download', 'apollo-social' ); ?>">
				<i class="ri-save-3-fill"></i>
			</button>

			<div class="ap-plano-beach-toggle" 
				 id="ap-plano-transparent-bg-toggle" 
				 title="<?php echo esc_attr__( 'Fundo Transparente (clique para alternar)', 'apollo-social' ); ?>">
				<div class="ap-plano-beach-toggle__water">
					<div class="ap-plano-beach-toggle__foam"></div>
				</div>
			</div>

			<div class="ap-plano-color-picker-container" 
				 title="<?php echo esc_attr__( 'Cor de Fundo', 'apollo-social' ); ?>">
				<input type="color" 
					   class="ap-plano-color-picker-input" 
					   id="ap-plano-bg-color" 
					   value="#f9a748" />
			</div>
		</div>
	</header>

	<!-- Canvas Wrapper -->
	<div id="ap-plano-canvas-wrapper">
		<canvas id="ap-plano-canvas"></canvas>
	</div>

	<!-- Side Menu -->
	<div class="ap-plano-side-menu">
		<button class="ap-plano-side-btn" 
				id="ap-plano-build-btn" 
				title="<?php echo esc_attr__( 'Adicionar', 'apollo-social' ); ?>">
			<i class="ri-XXX"></i>
		</button>
		<button class="ap-plano-side-btn" 
				id="ap-plano-templates-btn" 
				title="<?php echo esc_attr__( 'Modelos', 'apollo-social' ); ?>">
			<i class="ri-stack-fill"></i>
		</button>
		<button class="ap-plano-side-btn" 
				id="ap-plano-ratio-btn" 
				title="<?php echo esc_attr__( 'Proporção', 'apollo-social' ); ?>">
			<i class="ri-aspect-ratio-line"></i>
		</button>
		<button class="ap-plano-side-btn" 
				id="ap-plano-gradient-btn" 
				title="<?php echo esc_attr__( 'Gradientes', 'apollo-social' ); ?>">
			<i class="ri-pantone-fill"></i>
		</button>
		<button class="ap-plano-side-btn" 
				id="ap-plano-delete-btn" 
				title="<?php echo esc_attr__( 'Deletar', 'apollo-social' ); ?>">
			<i class="ri-delete-bin-line"></i>
		</button>
	</div>

	<!-- Build Panel -->
	<div class="ap-plano-slide-panel" id="ap-plano-build-panel">
		<div class="ap-plano-panel-content">
			<button class="ap-plano-panel-btn" id="ap-plano-add-text-btn">
				<i class="ri-text"></i>
				<span><?php echo esc_html__( 'Texto', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-panel-btn" id="ap-plano-add-image-btn">
				<i class="ri-image-line"></i>
				<span><?php echo esc_html__( 'Imagem', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-panel-btn" id="ap-plano-add-box-btn">
				<i class="ri-shape-line"></i>
				<span><?php echo esc_html__( 'Caixa', 'apollo-social' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Element Controls -->
	<div class="ap-plano-element-controls" id="ap-plano-element-controls">
		<div class="ap-plano-color-picker-circle" 
			 id="ap-plano-elem-color-circle" 
			 title="<?php echo esc_attr__( 'Cor do Elemento', 'apollo-social' ); ?>">
			<input type="color" 
				   id="ap-plano-elem-color-input" 
				   value="#ffffff">
		</div>

		<div class="ap-plano-control-group ap-plano-font-control">
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-font-btn" 
					title="<?php echo esc_attr__( 'Fonte', 'apollo-social' ); ?>">
				<i class="ri-font-size"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-texttype-btn" 
					title="<?php echo esc_attr__( 'Tipo de Texto (H1-P)', 'apollo-social' ); ?>">
				<i class="ri-font-size-2"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-fontsize-btn" 
					title="<?php echo esc_attr__( 'Tamanho', 'apollo-social' ); ?>">
				<i class="ri-line-height"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-fontweight-btn" 
					title="<?php echo esc_attr__( 'Peso da Fonte', 'apollo-social' ); ?>">
				<i class="ri-font-weight"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-align-btn" 
					title="<?php echo esc_attr__( 'Alinhamento', 'apollo-social' ); ?>">
				<i class="ri-align-center"></i>
			</button>
		</div>

		<div class="ap-plano-control-group ap-plano-box-control">
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-border-btn" 
					title="<?php echo esc_attr__( 'Borda', 'apollo-social' ); ?>">
				<i class="ri-rounded-corner"></i>
			</button>
		</div>

		<div class="ap-plano-control-group">
			<button class="ap-plano-side-btn" 
					id="ap-plano-layer-up-btn" 
					title="<?php echo esc_attr__( 'Acima', 'apollo-social' ); ?>">
				<i class="ri-arrow-up-s-line"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-layer-down-btn" 
					title="<?php echo esc_attr__( 'Atrás', 'apollo-social' ); ?>">
				<i class="ri-arrow-down-s-line"></i>
			</button>
		</div>

		<div class="ap-plano-control-group">
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-opacity-btn" 
					title="<?php echo esc_attr__( 'Transparência', 'apollo-social' ); ?>">
				<i class="ri-ink-bottle-line"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-blend-btn" 
					title="<?php echo esc_attr__( 'Mix Cores', 'apollo-social' ); ?>">
				<i class="ri-dashboard-2-fill"></i>
			</button>
			<button class="ap-plano-side-btn" 
					id="ap-plano-elem-blur-btn" 
					title="<?php echo esc_attr__( 'Desfoque', 'apollo-social' ); ?>">
				<i class="ri-focus-2-line"></i>
			</button>
		</div>
	</div>

	<!-- Control Popups -->
	<div class="ap-plano-control-popup" id="ap-plano-border-popup">
		<label>
			<?php echo esc_html__( 'Borda', 'apollo-social' ); ?> 
			<span id="ap-plano-border-value">0</span>x
		</label>
		<br/>
		<input type="range" 
			   id="ap-plano-border-slider" 
			   min="0" 
			   max="200" 
			   value="0">
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-opacity-popup">
		<label>
			<?php echo esc_html__( 'Transparência', 'apollo-social' ); ?> 
			<span id="ap-plano-opacity-value">100</span>%
		</label>
		<br/>
		<input type="range" 
			   id="ap-plano-opacity-slider" 
			   min="0" 
			   max="100" 
			   value="100">
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-blur-popup">
		<label>
			<?php echo esc_html__( 'Desfoque', 'apollo-social' ); ?> 
			<span id="ap-plano-blur-value">0</span>x
		</label>
		<br/>
		<input type="range" 
			   id="ap-plano-blur-slider" 
			   min="0" 
			   max="30" 
			   value="0">
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-fontsize-popup">
		<label>
			<?php echo esc_html__( 'Tamanho', 'apollo-social' ); ?> 
			<span id="ap-plano-fontsize-value">24</span>x
		</label>
		<br/>
		<input type="range" 
			   id="ap-plano-fontsize-slider" 
			   min="10" 
			   max="150" 
			   value="24">
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-blend-popup">
		<select id="ap-plano-blend-select">
			<option value="normal"><?php echo esc_html__( 'Simples', 'apollo-social' ); ?></option>
			<option value="multiply"><?php echo esc_html__( 'Múltiplicar', 'apollo-social' ); ?></option>
			<option value="screen"><?php echo esc_html__( 'Tela', 'apollo-social' ); ?></option>
			<option value="overlay"><?php echo esc_html__( 'Sobrepor', 'apollo-social' ); ?></option>
			<option value="darken"><?php echo esc_html__( 'Escurecer', 'apollo-social' ); ?></option>
			<option value="lighten"><?php echo esc_html__( 'Iluminar', 'apollo-social' ); ?></option>
			<option value="color-dodge"><?php echo esc_html__( 'Lodge', 'apollo-social' ); ?></option>
			<option value="color-burn"><?php echo esc_html__( 'Queimar', 'apollo-social' ); ?></option>
			<option value="hard-light"><?php echo esc_html__( 'Luz Forte', 'apollo-social' ); ?></option>
			<option value="soft-light"><?php echo esc_html__( 'Luz Fraca', 'apollo-social' ); ?></option>
			<option value="difference"><?php echo esc_html__( 'Oposto*', 'apollo-social' ); ?></option>
			<option value="exclusion"><?php echo esc_html__( 'Inverter*', 'apollo-social' ); ?></option>
		</select>
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-font-popup">
		<select id="ap-plano-font-select"></select>
	</div>

	<div class="ap-plano-control-popup" id="ap-plano-texttype-popup">
		<div class="ap-plano-text-type-selector" style="flex-direction:column;min-width:140px;">
			<button class="ap-plano-text-type-btn" 
					data-type="h1" 
					data-size="48" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:18px;"><?php echo esc_html__( 'Título', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="h2" 
					data-size="36" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:16px;"><?php echo esc_html__( 'Sub-título', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="h3" 
					data-size="28" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:14px;"><?php echo esc_html__( 'Tópico', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="h4" 
					data-size="24" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:13px;"><?php echo esc_html__( 'Sub-tópico', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="h5" 
					data-size="20" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:12px;"><?php echo esc_html__( 'Inferior', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="h6" 
					data-size="16" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:11px;"><?php echo esc_html__( 'Sub-inferior', 'apollo-social' ); ?></span>
			</button>
			<button class="ap-plano-text-type-btn" 
					data-type="p" 
					data-size="14" 
					style="display:flex;align-items:center;gap:8px;width:100%;">
				<span style="font-size:11px;"><?php echo esc_html__( 'Parágrafo', 'apollo-social' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Modals -->
	<div class="ap-plano-modal hidden" id="ap-plano-gradient-modal">
		<div class="ap-plano-modal-content">
			<button class="ap-plano-modal-close" id="ap-plano-close-gradient">×</button>
			<h3>
				<i class="ri-pantone-fill"></i> 
				<?php echo esc_html__( 'Escolha um Degradê', 'apollo-social' ); ?>
			</h3>
			<div class="ap-plano-item-grid" id="ap-plano-gradient-grid"></div>
		</div>
	</div>

	<div class="ap-plano-modal hidden" id="ap-plano-templates-modal">
		<div class="ap-plano-modal-content">
			<button class="ap-plano-modal-close" id="ap-plano-close-templates">×</button>
			<h3>
				<i class="ri-stack-fill"></i> 
				<?php echo esc_html__( 'Escolha um Modelo', 'apollo-social' ); ?>
			</h3>
			<div class="ap-plano-item-grid" id="ap-plano-templates-grid"></div>
		</div>
	</div>

	<!-- Hidden file input -->
	<input type="file" 
		   id="ap-plano-image-upload" 
		   accept="image/*" 
		   style="display: none;" />
</div>

<script>
	// Add spin animation for loader icon.
	if (!document.getElementById('ap-plano-spin-style')) {
		const style = document.createElement('style');
		style.id = 'ap-plano-spin-style';
		style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
		document.head.appendChild(style);
	}
</script>

<?php
get_footer();
?>

