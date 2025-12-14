<?php

namespace Apollo\Infrastructure\Security;

/**
 * Nonces manager (stub)
 *
 * Handles WordPress nonce generation and verification for forms and AJAX requests.
 * TODO: Implement nonce helpers for all plugin actions.
 */
class Nonces
{
    /**
     * Generate nonce for action
     * TODO: implement wrapper around wp_create_nonce
     */
    public function create($action)
    {
        // TODO: implement nonce creation logic
    }

    /**
     * Verify nonce for action
     * TODO: implement wrapper around wp_verify_nonce
     */
    public function verify($nonce, $action)
    {
        // TODO: implement nonce verification logic
    }
}
