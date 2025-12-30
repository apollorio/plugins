<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;
defined('ABSPATH') || exit;
final class Achievements {
    private static array $achievements = [
        'first_steps' => ['label' => 'Primeiros Passos', 'desc' => 'Complete seu perfil', 'icon' => '👋', 'points' => 100, 'trigger' => 'profile_complete'],
        'social_butterfly' => ['label' => 'Borboleta Social', 'desc' => 'Adicione 5 amigos ao bubble', 'icon' => '🦋', 'points' => 150, 'count' => 5, 'trigger' => 'bubble_added'],
        'party_starter' => ['label' => 'Party Starter', 'desc' => 'Crie seu primeiro evento', 'icon' => '🎉', 'points' => 200, 'trigger' => 'event_created'],
        'event_hopper' => ['label' => 'Event Hopper', 'desc' => 'Participe de 10 eventos', 'icon' => '🎪', 'points' => 300, 'count' => 10, 'trigger' => 'event_attended'],
        'night_owl' => ['label' => 'Coruja Noturna', 'desc' => 'Participe de 25 eventos', 'icon' => '🦉', 'points' => 500, 'count' => 25, 'trigger' => 'event_attended'],
        'market_maker' => ['label' => 'Market Maker', 'desc' => 'Publique 5 anúncios', 'icon' => '🏪', 'points' => 250, 'count' => 5, 'trigger' => 'classified_posted'],
        'deal_closer' => ['label' => 'Fechador de Negócios', 'desc' => 'Venda 3 itens', 'icon' => '🤝', 'points' => 400, 'count' => 3, 'trigger' => 'classified_sold'],
        'community_builder' => ['label' => 'Construtor de Comunidade', 'desc' => 'Crie uma comunidade', 'icon' => '🏗️', 'points' => 300, 'trigger' => 'group_created'],
        'team_player' => ['label' => 'Team Player', 'desc' => 'Entre em 5 grupos', 'icon' => '⚽', 'points' => 200, 'count' => 5, 'trigger' => 'group_joined'],
        'networker' => ['label' => 'Networker', 'desc' => 'Envie 10 convites aceitos', 'icon' => '🔗', 'points' => 350, 'count' => 10, 'trigger' => 'invite_accepted'],
        'trusted_member' => ['label' => 'Membro Confiável', 'desc' => 'Seja verificado', 'icon' => '✅', 'points' => 500, 'trigger' => 'verified_member'],
        'content_creator' => ['label' => 'Criador de Conteúdo', 'desc' => 'Crie 20 posts', 'icon' => '✍️', 'points' => 300, 'count' => 20, 'trigger' => 'post_created'],
        'popular_voice' => ['label' => 'Voz Popular', 'desc' => 'Receba 100 curtidas', 'icon' => '📢', 'points' => 400, 'count' => 100, 'trigger' => 'post_liked'],
        'week_warrior' => ['label' => 'Guerreiro Semanal', 'desc' => 'Login por 7 dias seguidos', 'icon' => '🗓️', 'points' => 150, 'trigger' => 'streak_7_days'],
        'month_master' => ['label' => 'Mestre Mensal', 'desc' => 'Login por 30 dias seguidos', 'icon' => '📅', 'points' => 500, 'trigger' => 'streak_30_days'],
        'document_pro' => ['label' => 'Document Pro', 'desc' => 'Assine 5 documentos', 'icon' => '📝', 'points' => 300, 'count' => 5, 'trigger' => 'document_signed'],
        'nucleo_founder' => ['label' => 'Fundador de Núcleo', 'desc' => 'Crie um núcleo de trabalho', 'icon' => '🏢', 'points' => 400, 'trigger' => 'nucleo_created'],
        'guardian' => ['label' => 'Guardião', 'desc' => 'Reporte 10 spams confirmados', 'icon' => '🛡️', 'points' => 350, 'count' => 10, 'trigger' => 'report_spam'],
        'mentor' => ['label' => 'Mentor', 'desc' => 'Ajude 5 novatos', 'icon' => '🎓', 'points' => 400, 'count' => 5, 'trigger' => 'help_newbie'],
        'scene_veteran' => ['label' => 'Veterano da Cena', 'desc' => 'Membro por 1 ano', 'icon' => '🏆', 'points' => 1000, 'days' => 365],
    ];
    public static function init(): void {
        add_action('apollo_points_awarded', [self::class, 'checkAchievements'], 10, 4);
    }
    public static function checkAchievements(int $user_id, string $trigger, int $points, int $total): void {
        foreach (self::$achievements as $key => $achievement) {
            if (self::hasAchievement($user_id, $key)) continue;
            if (!isset($achievement['trigger']) || $achievement['trigger'] !== $trigger) continue;
            if (isset($achievement['count'])) {
                $count = self::getTriggerCount($user_id, $trigger);
                if ($count < $achievement['count']) continue;
            }
            self::award($user_id, $key);
        }
    }
    public static function award(int $user_id, string $achievement_key): bool {
        if (!isset(self::$achievements[$achievement_key])) return false;
        if (self::hasAchievement($user_id, $achievement_key)) return false;
        $achievements = get_user_meta($user_id, '_apollo_achievements', true) ?: [];
        $achievements[$achievement_key] = ['awarded_at' => current_time('mysql', true)];
        update_user_meta($user_id, '_apollo_achievements', $achievements);
        $achievement = self::$achievements[$achievement_key];
        if (!empty($achievement['points'])) {
            PointsSystem::instance()->award($user_id, 'achievement_' . $achievement_key, ['achievement' => $achievement_key]);
        }
        do_action('apollo_achievement_unlocked', $user_id, $achievement_key, $achievement);
        return true;
    }
    public static function hasAchievement(int $user_id, string $key): bool {
        $achievements = get_user_meta($user_id, '_apollo_achievements', true) ?: [];
        return isset($achievements[$key]);
    }
    public static function getUserAchievements(int $user_id): array {
        $earned = get_user_meta($user_id, '_apollo_achievements', true) ?: [];
        $result = [];
        foreach (self::$achievements as $key => $achievement) {
            $result[$key] = array_merge($achievement, [
                'key' => $key,
                'earned' => isset($earned[$key]),
                'earned_at' => $earned[$key]['awarded_at'] ?? null,
            ]);
        }
        return $result;
    }
    public static function getProgress(int $user_id): array {
        $earned = get_user_meta($user_id, '_apollo_achievements', true) ?: [];
        $total = count(self::$achievements);
        $completed = count($earned);
        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'next' => self::getNextAchievements($user_id, 3),
        ];
    }
    private static function getNextAchievements(int $user_id, int $limit = 3): array {
        $earned = get_user_meta($user_id, '_apollo_achievements', true) ?: [];
        $next = [];
        foreach (self::$achievements as $key => $achievement) {
            if (isset($earned[$key])) continue;
            $progress = 0;
            if (isset($achievement['trigger'], $achievement['count'])) {
                $count = self::getTriggerCount($user_id, $achievement['trigger']);
                $progress = min(100, round(($count / $achievement['count']) * 100));
            }
            $next[] = array_merge($achievement, ['key' => $key, 'progress' => $progress]);
            if (count($next) >= $limit) break;
        }
        return $next;
    }
    private static function getTriggerCount(int $user_id, string $trigger): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_points_log WHERE user_id = %d AND trigger_name = %s",
            $user_id, $trigger
        ));
    }
    public static function getAll(): array { return self::$achievements; }
}
add_action('plugins_loaded', [Achievements::class, 'init'], 16);
