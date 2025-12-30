<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;
defined('ABSPATH') || exit;
final class ProfileFields {
    private static array $cache = [];
    private static array $field_groups = [
        'basic' => ['label' => 'Informações Básicas', 'order' => 1],
        'social' => ['label' => 'Redes Sociais', 'order' => 2],
        'professional' => ['label' => 'Profissional', 'order' => 3],
        'comuna' => ['label' => 'Comunidade', 'order' => 4, 'visibility' => 'members'],
        'nucleo' => ['label' => 'Núcleo de Trabalho', 'order' => 5, 'visibility' => 'group_members'],
    ];
    private static array $fields = [
        'bio' => ['group' => 'basic', 'type' => 'textarea', 'label' => 'Bio', 'public' => true, 'order' => 1],
        'location' => ['group' => 'basic', 'type' => 'text', 'label' => 'Localização', 'public' => true, 'order' => 2],
        'website' => ['group' => 'basic', 'type' => 'url', 'label' => 'Website', 'public' => true, 'order' => 3],
        'phone' => ['group' => 'basic', 'type' => 'tel', 'label' => 'Telefone', 'public' => false, 'order' => 4],
        'birthdate' => ['group' => 'basic', 'type' => 'date', 'label' => 'Data Nascimento', 'public' => false, 'order' => 5],
        'instagram' => ['group' => 'social', 'type' => 'text', 'label' => 'Instagram', 'public' => true, 'order' => 1, 'prefix' => '@'],
        'soundcloud' => ['group' => 'social', 'type' => 'url', 'label' => 'SoundCloud', 'public' => true, 'order' => 2],
        'spotify' => ['group' => 'social', 'type' => 'url', 'label' => 'Spotify', 'public' => true, 'order' => 3],
        'youtube' => ['group' => 'social', 'type' => 'url', 'label' => 'YouTube', 'public' => true, 'order' => 4],
        'occupation' => ['group' => 'professional', 'type' => 'text', 'label' => 'Ocupação', 'public' => true, 'order' => 1],
        'company' => ['group' => 'professional', 'type' => 'text', 'label' => 'Empresa/Coletivo', 'public' => true, 'order' => 2],
        'skills' => ['group' => 'professional', 'type' => 'tags', 'label' => 'Habilidades', 'public' => true, 'order' => 3],
        'sounds' => ['group' => 'professional', 'type' => 'tags', 'label' => 'Gêneros Musicais', 'public' => true, 'order' => 4],
    ];
    public static function getFieldGroups(): array {
        return apply_filters('apollo_profile_field_groups', self::$field_groups);
    }
    public static function getFields(string $group = ''): array {
        $fields = apply_filters('apollo_profile_fields', self::$fields);
        if ($group) {
            return array_filter($fields, fn($f) => $f['group'] === $group);
        }
        return $fields;
    }
    public static function getValue(int $user_id, string $field): mixed {
        $cache_key = "{$user_id}_{$field}";
        if (isset(self::$cache[$cache_key])) return self::$cache[$cache_key];
        $value = get_user_meta($user_id, "_apollo_profile_{$field}", true);
        self::$cache[$cache_key] = $value;
        return $value;
    }
    public static function setValue(int $user_id, string $field, mixed $value): bool {
        $fields = self::getFields();
        if (!isset($fields[$field])) return false;
        $sanitized = self::sanitizeValue($value, $fields[$field]['type']);
        update_user_meta($user_id, "_apollo_profile_{$field}", $sanitized);
        self::$cache["{$user_id}_{$field}"] = $sanitized;
        do_action('apollo_profile_field_updated', $user_id, $field, $sanitized);
        return true;
    }
    public static function getProfile(int $user_id, bool $public_only = true): array {
        $fields = self::getFields();
        $profile = [];
        foreach ($fields as $key => $config) {
            if ($public_only && !$config['public']) continue;
            $value = self::getValue($user_id, $key);
            if ($value !== '' && $value !== null) {
                $profile[$key] = ['value' => $value, 'label' => $config['label'], 'type' => $config['type']];
            }
        }
        return $profile;
    }
    public static function calculateCompleteness(int $user_id): int {
        $fields = self::getFields();
        $total = count($fields);
        $filled = 0;
        foreach ($fields as $key => $config) {
            $value = self::getValue($user_id, $key);
            if ($value !== '' && $value !== null && $value !== []) $filled++;
        }
        $user = get_userdata($user_id);
        if ($user->display_name && $user->display_name !== $user->user_login) $filled++;
        $total++;
        if (get_avatar_url($user_id) && !str_contains(get_avatar_url($user_id), 'gravatar')) $filled++;
        $total++;
        return $total > 0 ? (int) round(($filled / $total) * 100) : 0;
    }
    private static function sanitizeValue(mixed $value, string $type): mixed {
        return match($type) {
            'text' => sanitize_text_field($value),
            'textarea' => sanitize_textarea_field($value),
            'url' => esc_url_raw($value),
            'email' => sanitize_email($value),
            'tel' => preg_replace('/[^0-9+\-\s()]/', '', $value),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '',
            'number' => (int) $value,
            'tags' => is_array($value) ? array_map('sanitize_text_field', $value) : \explode(',', sanitize_text_field($value)),
            default => sanitize_text_field($value),
        };
    }
    public static function renderField(string $field, int $user_id = 0, array $attrs = []): string {
        $fields = self::getFields();
        if (!isset($fields[$field])) return '';
        if (!$user_id) $user_id = get_current_user_id();
        $config = $fields[$field];
        $value = self::getValue($user_id, $field);
        $id = "apollo-field-{$field}";
        $name = "apollo_profile[{$field}]";
        $class = $attrs['class'] ?? 'apollo-input';
        $html = match($config['type']) {
            'textarea' => sprintf('<textarea id="%s" name="%s" class="%s" rows="3">%s</textarea>', $id, $name, $class, esc_textarea($value)),
            'tags' => sprintf('<input type="text" id="%s" name="%s" class="%s" value="%s" data-type="tags">', $id, $name, $class, esc_attr(is_array($value) ? \implode(', ', $value) : $value)),
            default => sprintf('<input type="%s" id="%s" name="%s" class="%s" value="%s">', $config['type'], $id, $name, $class, esc_attr($value)),
        };
        return sprintf('<div class="apollo-field"><label for="%s">%s</label>%s</div>', $id, esc_html($config['label']), $html);
    }
}
