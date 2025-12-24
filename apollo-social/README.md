# Apollo Social - Suppliers Module

## Overview
The Apollo Social Suppliers Module is designed to manage supplier information within the Apollo ecosystem. This module provides a RESTful API for interacting with supplier data, allowing for the addition, retrieval, and management of suppliers through a user-friendly interface.

## Directory Structure
The project is organized as follows:

```
apollo-social
├── src
│   ├── Domain
│   │   └── Suppliers
│   │       ├── Supplier.php
│   │       ├── SupplierRepository.php
│   │       └── SupplierService.php
│   ├── Infrastructure
│   │   ├── Persistence
│   │   │   └── WPPostSupplierRepository.php
│   │   └── Routing
│   │       └── SuppliersRouteHandler.php
│   └── Presentation
│       └── Controllers
│           └── SuppliersController.php
├── templates
│   └── cena-rio
│       ├── suppliers-list.php
│       ├── supplier-single.php
│       ├── supplier-add.php
│       └── partials
│           ├── supplier-card.php
│           └── supplier-modal.php
├── assets
│   ├── css
│   │   └── cena-rio-suppliers.css
│   └── js
│       └── cena-rio-suppliers.js
└── README.md
```

## Features
- **Supplier Management**: Add, update, and delete suppliers through a RESTful API.
- **User Interface**: A clean and responsive design for listing suppliers, viewing details, and adding new suppliers.
- **Data Persistence**: Utilizes WordPress custom post types for storing supplier data.
- **AJAX Support**: Dynamic interactions for adding suppliers without page reloads.

## Setup Instructions
1. **Installation**: Clone the repository into your WordPress plugins directory.
2. **Activation**: Activate the Apollo Social plugin through the WordPress admin panel.
3. **Configuration**: Ensure that the necessary WordPress custom post types are registered for suppliers.

## Usage
- **List Suppliers**: Access the suppliers list at `/fornece/`.
- **Add Supplier**: Use the form at `/fornece/add/` to add a new supplier.
- **View Supplier**: Access individual supplier details at `/fornece/{id}`.

## Development Guidelines
- Follow the PSR-4 autoloading standard for PHP classes.
- Ensure all new code adheres to WordPress coding standards.
- Use Yoda conditions for comparisons and snake_case for variable and function names.
- Document all functions and classes with comprehensive PHPDoc comments.

## Contributing
Contributions are welcome! Please submit a pull request with a clear description of your changes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.