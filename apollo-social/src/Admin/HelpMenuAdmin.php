<?php
/**
 * Help Menu Admin
 *
 * Adiciona botão de ajuda no menu do WordPress admin
 * para acesso rápido a suporte e documentação de emergência
 *
 * @package ApolloSocial\Admin
 * @since 1.0.0
 */

namespace Apollo\Admin;

class HelpMenuAdmin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_help_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_help_styles' ) );
	}

	/**
	 * Register Help Menu
	 */
	public function register_help_menu() {
		add_menu_page(
			__( 'Apollo HELP', 'apollo-social' ),
			// Page title
			__( '🆘 HELP', 'apollo-social' ),
			// Menu title
			'read',
			// Capability (todos os logados)
			'apollo-help',
			// Menu slug
			array( $this, 'render_help_page' ),
			// Callback
			'dashicons-sos',
			// Icon
			999
			// Position (último)
		);
	}

	/**
	 * Render Help Page
	 */
	public function render_help_page() {
		$current_user = wp_get_current_user();
		$site_url     = get_site_url();
		$admin_email  = get_option( 'admin_email' );

		?>
		<div class="wrap apollo-help-page">
			<h1>
				<span class="dashicons dashicons-sos" style="color: #d63638;"></span>
				<?php esc_html_e( 'Central de Ajuda Apollo', 'apollo-social' ); ?>
			</h1>
			
			<div class="apollo-help-grid">
				
				<!-- Emergência -->
				<div class="apollo-help-card apollo-help-emergency">
					<h2>🚨 <?php esc_html_e( 'Emergência', 'apollo-social' ); ?></h2>
					<p><?php esc_html_e( 'Algo está quebrado? Precisa de ajuda imediata?', 'apollo-social' ); ?></p>
					<ul>
						<li><strong>Site travado:</strong> <code>Ctrl + Shift + R</code> (limpar cache)</li>
						<li><strong>Não consigo editar:</strong> Verificar se está logado</li>
						<li><strong>Erro 500:</strong> Desabilitar último plugin ativado</li>
					</ul>
					<a href="mailto:<?php echo esc_attr( $admin_email ); ?>?subject=EMERGÊNCIA Apollo" 
						class="button button-primary button-hero apollo-help-btn-emergency">
						📧 Email de Emergência
					</a>
				</div>
				
				<!-- Documentação -->
				<div class="apollo-help-card">
					<h2>📚 <?php esc_html_e( 'Documentação', 'apollo-social' ); ?></h2>
					<p><?php esc_html_e( 'Guias e manuais do sistema:', 'apollo-social' ); ?></p>
					<ul>
						<li><a href="<?php echo admin_url( 'admin.php?page=apollo-help&doc=tipos-conteudo' ); ?>">
							Tipos de Conteúdo Disponíveis
						</a></li>
						<li><a href="<?php echo admin_url( 'admin.php?page=apollo-help&doc=user-pages' ); ?>">
							Como Editar Páginas de Usuário
						</a></li>
						<li><a href="<?php echo admin_url( 'admin.php?page=apollo-help&doc=eventos' ); ?>">
							Criar e Gerenciar Eventos
						</a></li>
						<li><a href="<?php echo admin_url( 'admin.php?page=apollo-help&doc=api' ); ?>">
							API REST - Endpoints
						</a></li>
					</ul>
				</div>
				
				<!-- Informações do Sistema -->
				<div class="apollo-help-card">
					<h2>ℹ️ <?php esc_html_e( 'Informações do Sistema', 'apollo-social' ); ?></h2>
					<table class="apollo-help-info-table">
						<tr>
							<th>Usuário:</th>
							<td><?php echo esc_html( $current_user->display_name ); ?> (ID: <?php echo esc_html( $current_user->ID ); ?>)</td>
						</tr>
						<tr>
							<th>Role:</th>
							<td><?php echo esc_html( implode( ', ', $current_user->roles ) ); ?></td>
						</tr>
						<tr>
							<th>WordPress:</th>
							<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
						</tr>
						<tr>
							<th>PHP:</th>
							<td><?php echo esc_html( PHP_VERSION ); ?></td>
						</tr>
						<tr>
							<th>Tema:</th>
							<td><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></td>
						</tr>
						<tr>
							<th>URL do Site:</th>
							<td><code><?php echo esc_url( $site_url ); ?></code></td>
						</tr>
					</table>
				</div>
				
				<!-- Tipos de Conteúdo -->
				<div class="apollo-help-card">
					<h2>📋 <?php esc_html_e( 'Tipos de Conteúdo Ativos', 'apollo-social' ); ?></h2>
					<?php
					$post_types = get_post_types( array( '_builtin' => false ), 'objects' );
					if ( ! empty( $post_types ) ) {
						echo '<ul>';
						foreach ( $post_types as $post_type ) {
							$count = wp_count_posts( $post_type->name );
							$total = isset( $count->publish ) ? $count->publish : 0;
							printf(
								'<li><strong>%s</strong> <span class="apollo-help-badge">%d</span><br><small>%s</small></li>',
								esc_html( $post_type->labels->name ),
								esc_html( $total ),
								esc_html( $post_type->name )
							);
						}
						echo '</ul>';
					} else {
						echo '<p>Nenhum tipo de conteúdo customizado ativo.</p>';
					}
					?>
				</div>
				
				<!-- Ações Rápidas -->
				<div class="apollo-help-card">
					<h2>⚡ <?php esc_html_e( 'Ações Rápidas', 'apollo-social' ); ?></h2>
					<div class="apollo-help-actions">
						<a href="<?php echo admin_url( 'plugins.php' ); ?>" class="button">
							🔌 Gerenciar Plugins
						</a>
						<a href="<?php echo admin_url( 'options-permalink.php' ); ?>" class="button">
							🔗 Atualizar URLs
						</a>
						<a href="<?php echo admin_url( 'tools.php?page=health-check' ); ?>" class="button">
							💊 Verificar Saúde do Site
						</a>
						<a href="<?php echo admin_url( 'export.php' ); ?>" class="button">
							💾 Fazer Backup
						</a>
					</div>
				</div>
				
				<!-- Contatos -->
				<div class="apollo-help-card">
					<h2>📞 <?php esc_html_e( 'Contatos de Suporte', 'apollo-social' ); ?></h2>
					<p><strong>Email Admin:</strong> <a href="mailto:<?php echo esc_attr( $admin_email ); ?>"><?php echo esc_html( $admin_email ); ?></a></p>
					<p><strong>Desenvolvedor:</strong> Entre em contato pelo email acima</p>
					<p><strong>Horário:</strong> Segunda a Sexta, 9h-18h</p>
				</div>
				
			</div>
			
			<!-- Nota de Rodapé -->
			<div class="apollo-help-footer">
				<p>
					<span class="dashicons dashicons-info"></span>
					<strong>Dica:</strong> Antes de entrar em contato, tente limpar o cache do navegador e verificar se está usando a última versão dos plugins.
				</p>
				<p style="color: #666; font-size: 12px;">
					Última atualização: <?php echo date_i18n( get_option( 'date_format' ) ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue Help Styles
	 */
	public function enqueue_help_styles( $hook ) {
		if ( 'toplevel_page_apollo-help' !== $hook ) {
			return;
		}

		// Inline CSS
		$css = '
            .apollo-help-page {
                max-width: 1400px;
                margin: 20px auto;
            }
            
            .apollo-help-page h1 {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 30px;
            }
            
            .apollo-help-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .apollo-help-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .apollo-help-card h2 {
                margin-top: 0;
                font-size: 18px;
                border-bottom: 2px solid #f0f0f1;
                padding-bottom: 10px;
            }
            
            .apollo-help-emergency {
                border-color: #d63638;
                background: #fff8f8;
            }
            
            .apollo-help-emergency h2 {
                color: #d63638;
            }
            
            .apollo-help-btn-emergency {
                background: #d63638 !important;
                border-color: #b32d2e !important;
                margin-top: 15px;
            }
            
            .apollo-help-info-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .apollo-help-info-table th {
                text-align: left;
                padding: 8px;
                width: 120px;
                font-weight: 600;
            }
            
            .apollo-help-info-table td {
                padding: 8px;
            }
            
            .apollo-help-info-table tr {
                border-bottom: 1px solid #f0f0f1;
            }
            
            .apollo-help-badge {
                background: #2271b1;
                color: #fff;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
            }
            
            .apollo-help-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .apollo-help-actions .button {
                flex: 1 1 calc(50% - 5px);
                min-width: 150px;
            }
            
            .apollo-help-footer {
                background: #f0f6fc;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
            }
            
            .apollo-help-footer .dashicons {
                color: #2271b1;
            }
            
            .apollo-help-card ul {
                line-height: 1.8;
            }
            
            .apollo-help-card code {
                background: #f0f0f1;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
            }
        ';

		wp_add_inline_style( 'wp-admin', $css );
	}
}

// Initialize
new HelpMenuAdmin();
