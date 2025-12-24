<?php
/**
 * Supplier Single Template - Cena Rio
 * This template displays the details of a single supplier.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch the supplier ID from the URL.
$supplier_id = get_query_var( 'id' );

// Fetch the supplier details using the SupplierService.
$supplier_service = new SupplierService();
$supplier = $supplier_service->get_supplier( $supplier_id );

// Check if the supplier exists.
if ( ! $supplier ) {
	// Handle the case where the supplier is not found.
	wp_redirect( home_url( '/fornece/' ) );
	exit;
}

// Prepare supplier data for display.
$supplier_name = esc_html( $supplier->get_name() );
$supplier_contact = esc_html( $supplier->get_contact_info() );
$supplier_description = esc_html( $supplier->get_description() );
$supplier_logo = esc_url( $supplier->get_logo_url() );

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $supplier_name; ?> - Fornecedores</title>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo esc_url( plugins_url( 'assets/css/cena-rio-suppliers.css', __FILE__ ) ); ?>">
</head>
<body class="apollo-canvas-mode">

<div class="supplier-single-container">
	<header class="supplier-header">
		<h1 class="supplier-name"><?php echo $supplier_name; ?></h1>
		<?php if ( $supplier_logo ) : ?>
			<img src="<?php echo $supplier_logo; ?>" alt="<?php echo $supplier_name; ?>" class="supplier-logo">
		<?php endif; ?>
	</header>

	<section class="supplier-details">
		<h2>Detalhes do Fornecedor</h2>
		<p><strong>Contato:</strong> <?php echo $supplier_contact; ?></p>
		<p><strong>Descrição:</strong> <?php echo $supplier_description; ?></p>
	</section>

	<section class="supplier-actions">
		<button class="btn btn-primary" id="editSupplierBtn">Editar Fornecedor</button>
		<button class="btn btn-danger" id="deleteSupplierBtn">Excluir Fornecedor</button>
	</section>
</div>

<script src="<?php echo esc_url( plugins_url( 'assets/js/cena-rio-suppliers.js', __FILE__ ) ); ?>"></script>
<script>
	document.getElementById('editSupplierBtn').addEventListener('click', function() {
		window.location.href = '<?php echo esc_url( home_url( '/fornece/edit/' . $supplier_id ) ); ?>';
	});

	document.getElementById('deleteSupplierBtn').addEventListener('click', function() {
		if ( confirm('Tem certeza de que deseja excluir este fornecedor?') ) {
			// Implement AJAX request to delete the supplier.
		}
	});
</script>

<?php wp_footer(); ?>
</body>
</html>