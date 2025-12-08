<?php
namespace Apollo\Domain\Entities;

/**
 * User Entity
 */
class User {

	public $id;
	public $login;
	public $email;
	public $display_name;
	public $roles        = [];
	public $capabilities = [];

	public function __construct( $data = [] ) {
		$this->id           = $data['id'] ?? 0;
		$this->login        = $data['login'] ?? '';
		$this->email        = $data['email'] ?? '';
		$this->display_name = $data['display_name'] ?? '';
		$this->roles        = $data['roles'] ?? [];
		$this->capabilities = $data['capabilities'] ?? [];
	}

	public function hasRole( $role ) {
		return in_array( $role, $this->roles );
	}

	public function can( $capability ) {
		return in_array( $capability, $this->capabilities ) || $this->hasRole( 'administrator' );
	}

	public function isLoggedIn() {
		return $this->id > 0;
	}
}
