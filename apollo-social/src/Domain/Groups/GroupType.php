<?php
namespace Apollo\Domain\Groups;

/**
 * Group type enum (stub)
 *
 * Defines the three types of groups supported by the system.
 * TODO: Implement type validation and type-specific behaviors.
 */
class GroupType
{
    const COMUNIDADE = 'comunidade';
    const NUCLEO = 'nucleo';
    const SEASON = 'season';

    /**
     * Get all valid group types
     * TODO: implement type listing and validation
     */
    public static function all()
    {
        return [self::COMUNIDADE, self::NUCLEO, self::SEASON];
    }

    /**
     * Check if type is valid
     * TODO: implement type validation logic
     */
    public static function isValid($type)
    {
        // TODO: implement type validation logic
    }

    /**
     * Get type configuration
     * TODO: implement config loading from config/groups.php
     */
    public static function getConfig($type)
    {
        // TODO: implement config loading logic
    }
}