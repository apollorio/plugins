<?php
/**
 * Supplier Class
 *
 * This class represents a supplier entity with properties and methods
 * for managing supplier data.
 *
 * @package Apollo_Social
 */

namespace Apollo\Domain\Suppliers;

class Supplier {
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $contact_email;

	/**
	 * @var string
	 */
	private $contact_phone;

	/**
	 * Constructor to initialize the supplier object.
	 *
	 * @param int $id
	 * @param string $name
	 * @param string $contact_email
	 * @param string $contact_phone
	 */
	public function __construct( int $id, string $name, string $contact_email, string $contact_phone ) {
		$this->id             = $id;
		$this->name           = $name;
		$this->contact_email  = $contact_email;
		$this->contact_phone   = $contact_phone;
	}

	/**
	 * Get the supplier ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the supplier name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the supplier name.
	 *
	 * @param string $name
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Get the supplier contact email.
	 *
	 * @return string
	 */
	public function get_contact_email(): string {
		return $this->contact_email;
	}

	/**
	 * Set the supplier contact email.
	 *
	 * @param string $contact_email
	 */
	public function set_contact_email( string $contact_email ): void {
		$this->contact_email = $contact_email;
	}

	/**
	 * Get the supplier contact phone.
	 *
	 * @return string
	 */
	public function get_contact_phone(): string {
		return $this->contact_phone;
	}

	/**
	 * Set the supplier contact phone.
	 *
	 * @param string $contact_phone
	 */
	public function set_contact_phone( string $contact_phone ): void {
		$this->contact_phone = $contact_phone;
	}
}
