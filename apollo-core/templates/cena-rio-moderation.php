<?php
declare(strict_types=1);
/**
 * Cena::Rio - Moderation Template (Canvas Mode)
 *
 * Moderation queue interface for CENA-MOD users
 * This template uses Canvas Mode (no theme CSS)
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user has permission to moderate
if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
}

$current_user = wp_get_current_user();

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Tailwind (CDN for dev).
		wp_enqueue_script(
			'tailwindcss',
			'https://cdn.tailwindcss.com',
			array(),
			'3.4.0',
			false
		);

		// Inline mod-specific styles.
		$mod_css = '
			body { margin: 0; padding: 0; font-family: Inter, system-ui, Arial; background: #f8fafc; }
			.container { max-width: 1200px; margin: 0 auto; padding: 24px; }
			.topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
		';
		wp_add_inline_style( 'apollo-uni-css', $mod_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
	<title>Moderação Cena::rio</title>
	<?php wp_head(); ?>
</head>
<body>
	<div class="topbar">
		<div>
			<h1 style="margin:0;font-size:20px;font-weight:800;color:#0f172a">Moderação Cena::Rio</h1>
			<p style="margin:4px 0 0 0;color:#64748b;font-size:14px">Painel de aprovação de eventos</p>
		</div>
		<div style="display:flex;gap:12px;align-items:center">
			<a href="<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg font-semibold hover:bg-slate-200 transition-colors" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none">
				<i class="ri-arrow-left-line"></i>
				Voltar ao Calendário
			</a>
		</div>
	</div>

	<div class="container">
		<?php
		// Sanitize and check for success messages
		$approved = isset( $_GET['cena_approved'] ) ? sanitize_text_field( wp_unslash( $_GET['cena_approved'] ) ) : '';
		$rejected = isset( $_GET['cena_rejected'] ) ? sanitize_text_field( wp_unslash( $_GET['cena_rejected'] ) ) : '';

		if ( '1' === $approved ) {
			echo '<div style="padding:16px;background:#f0fdf4;border-left:4px solid #10b981;border-radius:8px;color:#065f46;margin-bottom:24px">
				<strong>✓ Evento Aprovado!</strong><br>
				O evento foi publicado com sucesso no calendário.
			</div>';
		}

		if ( '1' === $rejected ) {
			echo '<div style="padding:16px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:8px;color:#991b1b;margin-bottom:24px">
				<strong>Evento Rejeitado</strong><br>
				O evento foi movido para rascunho.
			</div>';
		}
		?>

		<?php
		// Render mod queue shortcode
		echo do_shortcode( '[apollo_cena_mod_queue]' );
		?>
	</div>

	<?php wp_footer(); ?>
</body>
</html>

