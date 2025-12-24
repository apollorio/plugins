// File: /apollo-social/apollo-social/assets/js/cena-rio-suppliers.js

document.addEventListener('DOMContentLoaded', function() {
	const supplierForm = document.getElementById('supplierForm');
	const supplierModal = document.getElementById('supplierModal');
	const supplierList = document.getElementById('supplierList');

	// Function to handle form submission for adding a new supplier
	if (supplierForm) {
		supplierForm.addEventListener('submit', function(event) {
			event.preventDefault();
			const formData = new FormData(supplierForm);
			const requestOptions = {
				method: 'POST',
				body: formData,
			};

			fetch('/fornece/add/', requestOptions)
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Append new supplier to the list
						const newSupplierCard = createSupplierCard(data.supplier);
						supplierList.appendChild(newSupplierCard);
						supplierModal.classList.remove('is-active');
						supplierForm.reset();
					} else {
						alert('Error adding supplier: ' + data.message);
					}
				})
				.catch(error => {
					console.error('Error:', error);
				});
		});
	}

	// Function to create a supplier card element
	function createSupplierCard(supplier) {
		const card = document.createElement('div');
		card.className = 'supplier-card';
		card.innerHTML = `
			<h3>${supplier.name}</h3>
			<p>${supplier.contact}</p>
			<a href="/fornece/${supplier.id}">View Details</a>
		`;
		return card;
	}

	// Function to open the supplier modal
	document.getElementById('openSupplierModal').addEventListener('click', function() {
		supplierModal.classList.add('is-active');
	});

	// Function to close the supplier modal
	document.getElementById('closeSupplierModal').addEventListener('click', function() {
		supplierModal.classList.remove('is-active');
	});
});