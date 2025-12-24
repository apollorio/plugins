<?php
/**
 * Suppliers List Template - Cena Rio
 * This template displays a list of suppliers in a card format.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch suppliers from the API or database.
$suppliers = []; // This should be populated with actual supplier data.

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Fornecedores - Apollo::Rio</title>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo esc_url( plugins_url( 'assets/css/cena-rio-suppliers.css', __FILE__ ) ); ?>">
</head>
<body class="apollo-cena-rio-suppliers">

<div class="container">
	<h1 class="text-2xl font-bold">Lista de Fornecedores</h1>
	<div class="supplier-list grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
		<?php if ( empty( $suppliers ) ) : ?>
			<div class="no-suppliers text-center p-8">
				<p class="text-lg">Nenhum fornecedor encontrado.</p>
			</div>
		<?php else : ?>
			<?php foreach ( $suppliers as $supplier ) : ?>
				<?php include 'partials/supplier-card.php'; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<script src="<?php echo esc_url( plugins_url( 'assets/js/cena-rio-suppliers.js', __FILE__ ) ); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>