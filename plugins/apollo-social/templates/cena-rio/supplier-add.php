<?php
/**
 * Supplier Add Template
 * This template provides a form for adding a new supplier.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue necessary scripts and styles.
wp_enqueue_style( 'cena-rio-suppliers', plugins_url( 'assets/css/cena-rio-suppliers.css', __FILE__ ) );
wp_enqueue_script( 'cena-rio-suppliers', plugins_url( 'assets/js/cena-rio-suppliers.js', __FILE__ ), array( 'jquery' ), null, true );

$supplier_nonce_action = 'apollo_social_add_supplier';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Adicionar Fornecedor - Apollo::rio</title>
	<?php wp_head(); ?>
</head>
<body class="apollo-canvas-mode">

<div class="container">
	<h1 class="text-2xl font-bold">Adicionar Fornecedor</h1>
	<form id="supplierAddForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="add_supplier">
		<?php wp_nonce_field( $supplier_nonce_action, 'supplier_nonce' ); ?>

		<div class="mb-4">
			<label for="supplierName" class="block text-sm font-medium">Nome do Fornecedor</label>
			<input type="text" id="supplierName" name="supplier_name" required class="input w-full" placeholder="Digite o nome do fornecedor">
		</div>

		<div class="mb-4">
			<label for="supplierContact" class="block text-sm font-medium">Informações de Contato</label>
			<input type="text" id="supplierContact" name="supplier_contact" required class="input w-full" placeholder="Digite as informações de contato">
		</div>

		<div class="mb-4">
			<label for="supplierAddress" class="block text-sm font-medium">Endereço</label>
			<input type="text" id="supplierAddress" name="supplier_address" class="input w-full" placeholder="Digite o endereço do fornecedor">
		</div>

		<div class="mb-4">
			<label for="supplierWebsite" class="block text-sm font-medium">Website</label>
			<input type="url" id="supplierWebsite" name="supplier_website" class="input w-full" placeholder="Digite o website do fornecedor">
		</div>

		<button type="submit" class="btn btn-primary">Adicionar Fornecedor</button>
	</form>
</div>

<script>
// JavaScript for handling form submission and validation can be added here.
</script>

<?php wp_footer(); ?>
</body>
</html>