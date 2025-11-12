<?php

namespace Apollo\Modules\Builder;

/**
 * Persist SiteOrigin layout data in user meta.
 */
class LayoutRepository
{
    public const META_KEY = 'apollo_builder_layout';

    /**
     * Retrieve layout for a given user.
     */
    public function getLayout(int $userId): array
    {
        if ($userId <= 0) {
            return $this->emptyLayout();
        }

        $stored = get_user_meta($userId, self::META_KEY, true);

        if (empty($stored) || !is_array($stored)) {
            return $this->emptyLayout();
        }

        return wp_unslash($stored);
    }

    /**
     * Persist layout for user.
     */
    public function saveLayout(int $userId, array $layout): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $layout['__last_updated'] = current_time('mysql');
        $layout['__version'] = $layout['__version'] ?? 1;

        return update_user_meta($userId, self::META_KEY, wp_slash($layout));
    }

    /**
     * Remove stored layout.
     */
    public function deleteLayout(int $userId): void
    {
        if ($userId > 0) {
            delete_user_meta($userId, self::META_KEY);
        }
    }

    /**
     * Provide a sane empty layout scaffold.
     */
    public function emptyLayout(): array
    {
        return [
            'widgets' => [],
            'grids' => [],
            'grid_cells' => [],
            'apollo' => [
                'absolute' => true,
                'positions' => [],
            ],
            '__version' => 1,
        ];
    }
}

