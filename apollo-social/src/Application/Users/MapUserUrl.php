<?php

namespace Apollo\Application\Users;

/**
 * Map User URL use case (stub)
 *
 * Handles URL mapping for user profiles (/id/{id|login}).
 * TODO: Implement URL parsing and user resolution.
 */
class MapUserUrl
{
    /**
     * Map URL to user entity
     * TODO: implement URL parsing and user resolution logic
     */
    public function execute($url_part)
    {
        // TODO: implement URL mapping logic
        // 1. Parse URL part (could be ID or login)
        // 2. Try to resolve as ID first, then as login
        // 3. Return UserEntity or null if not found
    }
}
