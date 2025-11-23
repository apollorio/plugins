<?php
/**
 * P0-3: Meta Keys Auditor
 * 
 * Audits and validates meta keys used across Apollo plugins.
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\Infrastructure\Database;

if (!defined('ABSPATH')) exit;

class MetaKeysAuditor
{
    /**
     * P0-3: Expected meta keys for event_listing CPT
     */
    public static function getEventMetaKeys(): array
    {
        return [
            '_event_title' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Event title (alternative to post_title)',
            ],
            '_event_start_date' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Event start date/time (YYYY-MM-DD HH:MM:SS)',
                'format' => 'datetime',
            ],
            '_event_end_date' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Event end date/time',
                'format' => 'datetime',
            ],
            '_event_start_time' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Event start time (HH:MM:SS)',
            ],
            '_event_end_time' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Event end time (HH:MM:SS)',
            ],
            '_event_banner' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Event banner image URL or attachment ID',
            ],
            '_event_dj_ids' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Array of DJ post IDs',
                'serialized' => true,
            ],
            '_event_local_ids' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Local post ID (single)',
            ],
            '_event_timetable' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Event timetable with DJs and times',
                'serialized' => true,
            ],
            '_favorites_count' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Cached count of favorites',
                'default' => 0,
            ],
            '_event_video_url' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Video URL (YouTube/Vimeo)',
            ],
            '_tickets_ext' => [
                'type' => 'string',
                'required' => false,
                'description' => 'External tickets URL',
            ],
            '_cupom_ario' => [
                'type' => 'integer',
                'required' => false,
                'description' => 'Cupom Ario flag (0 or 1)',
            ],
            '_event_location' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Location text (fallback)',
            ],
            '_event_country' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Country name',
            ],
            '_3_imagens_promo' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Promotional images array',
                'serialized' => true,
            ],
            '_imagem_final' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Final images array',
                'serialized' => true,
            ],
            '_event_co_authors' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Co-author user IDs',
                'serialized' => true,
            ],
        ];
    }

    /**
     * P0-3: Audit event meta keys
     */
    public static function auditEventMetaKeys(): array
    {
        global $wpdb;

        $expected_keys = self::getEventMetaKeys();
        $results = [
            'total_events' => 0,
            'events_with_meta' => 0,
            'missing_keys' => [],
            'invalid_types' => [],
            'coverage' => [],
        ];

        // Get all published events
        $events = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'event_listing' 
            AND post_status = 'publish'"
        );

        $results['total_events'] = count($events);

        foreach ($events as $event) {
            $event_id = $event->ID;
            $has_meta = false;

            foreach ($expected_keys as $key => $config) {
                $meta_value = get_post_meta($event_id, $key, true);

                if ($meta_value !== '') {
                    $has_meta = true;

                    // Validate type
                    $expected_type = $config['type'];
                    $actual_type = gettype($meta_value);

                    // Handle serialized arrays
                    if ($config['serialized'] ?? false) {
                        if (is_string($meta_value)) {
                            $unserialized = maybe_unserialize($meta_value);
                            if (is_array($unserialized)) {
                                $actual_type = 'array';
                            }
                        }
                    }

                    if ($expected_type === 'integer' && !is_numeric($meta_value)) {
                        if (!isset($results['invalid_types'][$key])) {
                            $results['invalid_types'][$key] = [];
                        }
                        $results['invalid_types'][$key][] = $event_id;
                    }

                    // Track coverage
                    if (!isset($results['coverage'][$key])) {
                        $results['coverage'][$key] = 0;
                    }
                    $results['coverage'][$key]++;
                } else {
                    // Check if required
                    if ($config['required'] ?? false) {
                        if (!isset($results['missing_keys'][$key])) {
                            $results['missing_keys'][$key] = [];
                        }
                        $results['missing_keys'][$key][] = $event_id;
                    }
                }
            }

            if ($has_meta) {
                $results['events_with_meta']++;
            }
        }

        // Calculate coverage percentages
        foreach ($results['coverage'] as $key => $count) {
            $results['coverage'][$key] = [
                'count' => $count,
                'percentage' => $results['total_events'] > 0 
                    ? round(($count / $results['total_events']) * 100, 1) 
                    : 0,
            ];
        }

        return $results;
    }

    /**
     * P0-3: Validate meta key value
     */
    public static function validateMetaKey(string $meta_key, $value, string $post_type = 'event_listing'): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
        ];

        if ($post_type === 'event_listing') {
            $expected_keys = self::getEventMetaKeys();
            
            if (!isset($expected_keys[$meta_key])) {
                $result['valid'] = false;
                $result['errors'][] = "Unknown meta key: {$meta_key}";
                return $result;
            }

            $config = $expected_keys[$meta_key];
            $expected_type = $config['type'];

            // Type validation
            switch ($expected_type) {
                case 'integer':
                    if (!is_numeric($value)) {
                        $result['valid'] = false;
                        $result['errors'][] = "Expected integer, got " . gettype($value);
                    }
                    break;
                case 'array':
                    if ($config['serialized'] ?? false) {
                        if (is_string($value)) {
                            $unserialized = maybe_unserialize($value);
                            if (!is_array($unserialized)) {
                                $result['valid'] = false;
                                $result['errors'][] = "Expected serialized array";
                            }
                        } elseif (!is_array($value)) {
                            $result['valid'] = false;
                            $result['errors'][] = "Expected array, got " . gettype($value);
                        }
                    } else {
                        if (!is_array($value)) {
                            $result['valid'] = false;
                            $result['errors'][] = "Expected array, got " . gettype($value);
                        }
                    }
                    break;
                case 'string':
                    if (!is_string($value) && !is_numeric($value)) {
                        $result['valid'] = false;
                        $result['errors'][] = "Expected string, got " . gettype($value);
                    }
                    break;
            }

            // Format validation for datetime
            if (isset($config['format']) && $config['format'] === 'datetime') {
                if (!empty($value)) {
                    $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                    if (!$dt) {
                        $result['valid'] = false;
                        $result['errors'][] = "Invalid datetime format. Expected YYYY-MM-DD HH:MM:SS";
                    }
                }
            }
        }

        return $result;
    }

    /**
     * P0-3: Get meta keys report
     */
    public static function getReport(): array
    {
        $audit = self::auditEventMetaKeys();
        $expected_keys = self::getEventMetaKeys();

        return [
            'timestamp' => current_time('mysql'),
            'total_events' => $audit['total_events'],
            'events_with_meta' => $audit['events_with_meta'],
            'expected_keys' => array_keys($expected_keys),
            'coverage' => $audit['coverage'],
            'missing_required' => $audit['missing_keys'],
            'invalid_types' => $audit['invalid_types'],
            'health_score' => self::calculateHealthScore($audit),
        ];
    }

    /**
     * P0-3: Calculate database health score
     */
    private static function calculateHealthScore(array $audit): float
    {
        $score = 100.0;
        $penalties = 0;

        // Penalty for missing required keys
        foreach ($audit['missing_keys'] as $key => $event_ids) {
            $penalties += count($event_ids) * 2; // 2 points per missing required key
        }

        // Penalty for invalid types
        foreach ($audit['invalid_types'] as $key => $event_ids) {
            $penalties += count($event_ids) * 1; // 1 point per invalid type
        }

        $score -= min($penalties, 100); // Cap at 0

        return max(0, round($score, 1));
    }
}

