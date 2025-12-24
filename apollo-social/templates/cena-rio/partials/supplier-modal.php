<?php
/**
 * Supplier Modal Partial Template
 * This modal is used to display supplier details or a form for adding/editing suppliers.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue necessary scripts and styles for the modal.
wp_enqueue_style( 'cena-rio-suppliers' );
wp_enqueue_script( 'cena-rio-suppliers' );

// Nonce for security.
$supplier_nonce_action = 'apollo_social_supplier_nonce';
$supplier_nonce = wp_create_nonce( $supplier_nonce_action );

// Placeholder for supplier data.
$supplier_data = isset( $supplier ) ? $supplier : array(
	'id' => '',
	'name' => '',
	'contact_info' => '',
);

?>

<div id="supplierModal" class="modal">
	<div class="modal-content">
		<span class="close-button" onclick="document.getElementById('supplierModal').style.display='none'">&times;</span>
		<h2><?php echo esc_html( $supplier_data['id'] ? 'Edit Supplier' : 'Add Supplier' ); ?></h2>
		<form id="supplierForm" method="post" action="<?php echo esc_url( rest_url( 'fornece/add/' ) ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $supplier_nonce ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( $supplier_data['id'] ); ?>">

			<label for="supplierName">Name</label>
			<input type="text" id="supplierName" name="name" value="<?php echo esc_attr( $supplier_data['name'] ); ?>" required>

			<label for="supplierContact">Contact Information</label>
			<input type="text" id="supplierContact" name="contact_info" value="<?php echo esc_attr( $supplier_data['contact_info'] ); ?>" required>

			<button type="submit" class="btn btn-primary">
				<?php echo esc_html( $supplier_data['id'] ? 'Update Supplier' : 'Add Supplier' ); ?>
			</button>
		</form>
	</div>
</div>

<script>
	document.getElementById('supplierForm').addEventListener('submit', function(e) {
		e.preventDefault();
		const formData = new FormData(this);
		fetch(this.action, {
			method: 'POST',
			body: formData,
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert('Supplier saved successfully!');
				location.reload(); // Reload the page to reflect changes.
			} else {
				alert('Error saving supplier: ' + data.message);
			}
		})
		.catch(error => console.error('Error:', error));
	});
</script>

<style>
	.modal {
		display: none; /* Hidden by default */
		position: fixed; /* Stay in place */
		z-index: 1; /* Sit on top */
		left: 0;
		top: 0;
		width: 100%; /* Full width */
		height: 100%; /* Full height */
		overflow: auto; /* Enable scroll if needed */
		background-color: rgb(0,0,0); /* Fallback color */
		background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
	}

	.modal-content {
		background-color: #fefefe;
		margin: 15% auto; /* 15% from the top and centered */
		padding: 20px;
		border: 1px solid #888;
		width: 80%; /* Could be more or less, depending on screen size */
	}

	.close-button {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
	}

	.close-button:hover,
	.close-button:focus {
		color: black;
		text-decoration: none;
		cursor: pointer;
	}
</style>

<?php
// Note: The modal should be triggered by JavaScript when needed, e.g., on button click.
?>