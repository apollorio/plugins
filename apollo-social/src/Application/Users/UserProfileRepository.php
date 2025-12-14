<?php

namespace Apollo\Application\Users;

/**
 * UserProfileRepository
 * Data access layer for user profile management and membership options
 */
class UserProfileRepository
{
    /**
     * Get user onboarding profile data
     */
    public function getUserProfile(int $user_id): array
    {
        $user = get_user_by('ID', $user_id);
        if (! $user) {
            return [];
        }

        $profile_fields = [
            'apollo_name'          => 'name',
            'apollo_industry'      => 'industry',
            'apollo_roles'         => 'roles',
            'apollo_member_of'     => 'member_of',
            'apollo_whatsapp'      => 'whatsapp',
            'apollo_instagram'     => 'instagram',
            'apollo_verify_token'  => 'verify_token',
            'apollo_verify_status' => 'verify_status',
            'apollo_onboarded'     => 'onboarded',
            'apollo_onboarded_at'  => 'onboarded_at',
        ];

        $profile = [
            'user_id'      => $user_id,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'created_at'   => $user->user_registered,
        ];

        foreach ($profile_fields as $meta_key => $key) {
            $value           = get_user_meta($user_id, $meta_key, true);
            $profile[ $key ] = $value;
        }

        // Parse JSON fields
        if (is_string($profile['roles'])) {
            $profile['roles'] = json_decode($profile['roles'], true) ?: [];
        }

        if (is_string($profile['member_of'])) {
            $profile['member_of'] = json_decode($profile['member_of'], true) ?: [];
        }

        return $profile;
    }

    /**
     * Update user profile data
     */
    public function updateUserProfile(int $user_id, array $data): bool
    {
        $user = get_user_by('ID', $user_id);
        if (! $user) {
            return false;
        }

        // Update core user fields if provided
        $user_data = [];
        if (isset($data['display_name'])) {
            $user_data['ID']           = $user_id;
            $user_data['display_name'] = sanitize_text_field($data['display_name']);
        }

        if (! empty($user_data)) {
            wp_update_user($user_data);
        }

        // Update meta fields
        $meta_fields = [
            'name'          => 'apollo_name',
            'industry'      => 'apollo_industry',
            'roles'         => 'apollo_roles',
            'member_of'     => 'apollo_member_of',
            'whatsapp'      => 'apollo_whatsapp',
            'instagram'     => 'apollo_instagram',
            'verify_token'  => 'apollo_verify_token',
            'verify_status' => 'apollo_verify_status',
        ];

        foreach ($meta_fields as $key => $meta_key) {
            if (isset($data[ $key ])) {
                $value = $data[ $key ];

                // JSON encode arrays
                if (in_array($key, [ 'roles', 'member_of' ]) && is_array($value)) {
                    $value = json_encode($value);
                }

                // Sanitize text fields
                if (in_array($key, [ 'name', 'industry', 'whatsapp', 'instagram' ])) {
                    $value = sanitize_text_field($value);
                }

                update_user_meta($user_id, $meta_key, $value);
            }
        }

        return true;
    }

    /**
     * Get industry options for onboarding
     */
    public function getIndustryOptions(): array
    {
        return [
            'technology' => [
                'label'       => 'Tecnologia',
                'description' => 'Software, Hardware, TI, Desenvolvimento',
                'popular'     => true,
            ],
            'marketing' => [
                'label'       => 'Marketing & Publicidade',
                'description' => 'Marketing Digital, Publicidade, Branding',
                'popular'     => true,
            ],
            'design' => [
                'label'       => 'Design & Criativo',
                'description' => 'Design Gráfico, UX/UI, Fotografia',
                'popular'     => true,
            ],
            'business' => [
                'label'       => 'Negócios & Empreendedorismo',
                'description' => 'Startups, Consultoria, Gestão',
                'popular'     => true,
            ],
            'education' => [
                'label'       => 'Educação',
                'description' => 'Ensino, Treinamento, Cursos',
                'popular'     => false,
            ],
            'health' => [
                'label'       => 'Saúde & Bem-estar',
                'description' => 'Medicina, Fitness, Nutrição',
                'popular'     => false,
            ],
            'finance' => [
                'label'       => 'Finanças',
                'description' => 'Bancos, Investimentos, Fintech',
                'popular'     => false,
            ],
            'retail' => [
                'label'       => 'Varejo & E-commerce',
                'description' => 'Comércio, Vendas, Loja Online',
                'popular'     => false,
            ],
            'food' => [
                'label'       => 'Alimentação',
                'description' => 'Restaurantes, Food Tech, Gastronomia',
                'popular'     => false,
            ],
            'entertainment' => [
                'label'       => 'Entretenimento',
                'description' => 'Mídia, Jogos, Produção de Conteúdo',
                'popular'     => false,
            ],
            'real_estate' => [
                'label'       => 'Imobiliário',
                'description' => 'Imóveis, Construção, Arquitetura',
                'popular'     => false,
            ],
            'automotive' => [
                'label'       => 'Automotivo',
                'description' => 'Carros, Motos, Transportes',
                'popular'     => false,
            ],
            'travel' => [
                'label'       => 'Turismo & Viagem',
                'description' => 'Agências, Hotéis, Experiências',
                'popular'     => false,
            ],
            'fashion' => [
                'label'       => 'Moda & Beleza',
                'description' => 'Roupas, Cosméticos, Estilo',
                'popular'     => false,
            ],
            'sports' => [
                'label'       => 'Esportes',
                'description' => 'Atletas, Equipamentos, Eventos',
                'popular'     => false,
            ],
            'non_profit' => [
                'label'       => 'ONGs & Causas Sociais',
                'description' => 'Organizações sem fins lucrativos',
                'popular'     => false,
            ],
            'government' => [
                'label'       => 'Governo & Público',
                'description' => 'Órgãos públicos, Política',
                'popular'     => false,
            ],
            'other' => [
                'label'       => 'Outro',
                'description' => 'Indústria não listada',
                'popular'     => false,
            ],
        ];
    }

    /**
     * Get role options for onboarding
     */
    public function getRoleOptions(): array
    {
        return [
            'founder' => [
                'label'       => 'Fundador/CEO',
                'description' => 'Líder executivo da empresa',
                'category'    => 'leadership',
            ],
            'cofounder' => [
                'label'       => 'Co-fundador',
                'description' => 'Sócio fundador',
                'category'    => 'leadership',
            ],
            'director' => [
                'label'       => 'Diretor',
                'description' => 'Diretor executivo ou de área',
                'category'    => 'leadership',
            ],
            'manager' => [
                'label'       => 'Gerente',
                'description' => 'Gestão de equipe ou projeto',
                'category'    => 'management',
            ],
            'coordinator' => [
                'label'       => 'Coordenador',
                'description' => 'Coordenação de atividades',
                'category'    => 'management',
            ],
            'developer' => [
                'label'       => 'Desenvolvedor',
                'description' => 'Programação e desenvolvimento',
                'category'    => 'technical',
            ],
            'designer' => [
                'label'       => 'Designer',
                'description' => 'Design visual e UX/UI',
                'category'    => 'creative',
            ],
            'marketer' => [
                'label'       => 'Profissional de Marketing',
                'description' => 'Marketing e publicidade',
                'category'    => 'marketing',
            ],
            'sales' => [
                'label'       => 'Vendas',
                'description' => 'Representante ou gerente de vendas',
                'category'    => 'sales',
            ],
            'consultant' => [
                'label'       => 'Consultor',
                'description' => 'Consultoria especializada',
                'category'    => 'services',
            ],
            'freelancer' => [
                'label'       => 'Freelancer',
                'description' => 'Profissional autônomo',
                'category'    => 'independent',
            ],
            'entrepreneur' => [
                'label'       => 'Empreendedor',
                'description' => 'Criador de negócios',
                'category'    => 'independent',
            ],
            'student' => [
                'label'       => 'Estudante',
                'description' => 'Estudante ou estagiário',
                'category'    => 'learning',
            ],
            'investor' => [
                'label'       => 'Investidor',
                'description' => 'Investidor ou venture capital',
                'category'    => 'finance',
            ],
            'analyst' => [
                'label'       => 'Analista',
                'description' => 'Análise de dados ou negócios',
                'category'    => 'technical',
            ],
            'specialist' => [
                'label'       => 'Especialista',
                'description' => 'Especialista em área técnica',
                'category'    => 'technical',
            ],
            'other' => [
                'label'       => 'Outro',
                'description' => 'Função não listada',
                'category'    => 'other',
            ],
        ];
    }

    /**
     * Get membership options for onboarding
     */
    public function getMembershipOptions(): array
    {
        return [
            'apollo_groups' => [
                'type'        => 'apollo',
                'label'       => 'Grupos Apollo',
                'description' => 'Outros grupos da comunidade Apollo',
                'icon'        => 'apollo-icon',
                'category'    => 'apollo_ecosystem',
            ],
            'facebook_groups' => [
                'type'        => 'external',
                'label'       => 'Grupos Facebook',
                'description' => 'Grupos de negócios no Facebook',
                'icon'        => 'facebook-icon',
                'category'    => 'social_media',
            ],
            'linkedin_groups' => [
                'type'        => 'external',
                'label'       => 'Grupos LinkedIn',
                'description' => 'Grupos profissionais no LinkedIn',
                'icon'        => 'linkedin-icon',
                'category'    => 'professional',
            ],
            'telegram_groups' => [
                'type'        => 'external',
                'label'       => 'Grupos Telegram',
                'description' => 'Comunidades no Telegram',
                'icon'        => 'telegram-icon',
                'category'    => 'messaging',
            ],
            'discord_servers' => [
                'type'        => 'external',
                'label'       => 'Servidores Discord',
                'description' => 'Comunidades no Discord',
                'icon'        => 'discord-icon',
                'category'    => 'gaming_tech',
            ],
            'whatsapp_groups' => [
                'type'        => 'external',
                'label'       => 'Grupos WhatsApp',
                'description' => 'Grupos de negócios no WhatsApp',
                'icon'        => 'whatsapp-icon',
                'category'    => 'messaging',
            ],
            'slack_workspaces' => [
                'type'        => 'external',
                'label'       => 'Workspaces Slack',
                'description' => 'Comunidades profissionais no Slack',
                'icon'        => 'slack-icon',
                'category'    => 'professional',
            ],
            'reddit_communities' => [
                'type'        => 'external',
                'label'       => 'Comunidades Reddit',
                'description' => 'Subreddits de negócios e tecnologia',
                'icon'        => 'reddit-icon',
                'category'    => 'forums',
            ],
            'professional_associations' => [
                'type'        => 'external',
                'label'       => 'Associações Profissionais',
                'description' => 'Conselhos e associações da área',
                'icon'        => 'association-icon',
                'category'    => 'professional',
            ],
            'coworking_spaces' => [
                'type'        => 'external',
                'label'       => 'Coworkings',
                'description' => 'Espaços de coworking e networking',
                'icon'        => 'coworking-icon',
                'category'    => 'physical',
            ],
            'startup_accelerators' => [
                'type'        => 'external',
                'label'       => 'Aceleradoras',
                'description' => 'Programas de aceleração e incubação',
                'icon'        => 'accelerator-icon',
                'category'    => 'startup',
            ],
            'none' => [
                'type'        => 'none',
                'label'       => 'Nenhuma',
                'description' => 'Não participo de outras comunidades',
                'icon'        => 'none-icon',
                'category'    => 'none',
            ],
        ];
    }

    /**
     * Get users awaiting verification
     */
    public function getUsersAwaitingVerification(int $limit = 20, int $offset = 0): array
    {
        global $wpdb;

        $verification_table = $wpdb->prefix . 'apollo_verifications';

        $query = "
            SELECT v.*, u.user_login, u.user_email, u.display_name, u.user_registered
            FROM {$verification_table} v
            INNER JOIN {$wpdb->users} u ON v.user_id = u.ID
            WHERE v.verify_status IN ('awaiting_instagram_verify', 'dm_requested')
            ORDER BY v.submitted_at DESC
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $limit, $offset),
            ARRAY_A
        );

        // Parse metadata and assets for each result
        foreach ($results as &$result) {
            if (! empty($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true) ?: [];
            }

            if (! empty($result['verify_assets'])) {
                $result['verify_assets'] = json_decode($result['verify_assets'], true) ?: [];
            }
        }

        return $results;
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats(): array
    {
        global $wpdb;

        $verification_table = $wpdb->prefix . 'apollo_verifications';

        $stats = $wpdb->get_results(
            "
            SELECT 
                verify_status,
                COUNT(*) as count
            FROM {$verification_table}
            GROUP BY verify_status
        ",
            ARRAY_A
        );

        $formatted_stats = [
            'awaiting_instagram_verify' => 0,
            'dm_requested'              => 0,
            'verified'                  => 0,
            'rejected'                  => 0,
            'total'                     => 0,
        ];

        foreach ($stats as $stat) {
            $formatted_stats[ $stat['verify_status'] ] = (int) $stat['count'];
            $formatted_stats['total'] += (int) $stat['count'];
        }

        return $formatted_stats;
    }

    /**
     * Search users by criteria
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        global $wpdb;

        $where_conditions = [ '1=1' ];
        $params           = [];

        // Search by name
        if (! empty($criteria['name'])) {
            $where_conditions[] = '(u.display_name LIKE %s OR meta_name.meta_value LIKE %s)';
            $search_name        = '%' . $wpdb->esc_like($criteria['name']) . '%';
            $params[]           = $search_name;
            $params[]           = $search_name;
        }

        // Search by industry
        if (! empty($criteria['industry'])) {
            $where_conditions[] = 'meta_industry.meta_value = %s';
            $params[]           = $criteria['industry'];
        }

        // Search by verification status
        if (! empty($criteria['verify_status'])) {
            $where_conditions[] = 'meta_verify_status.meta_value = %s';
            $params[]           = $criteria['verify_status'];
        }

        // Search by onboarding status
        if (isset($criteria['onboarded'])) {
            $where_conditions[] = 'meta_onboarded.meta_value = %s';
            $params[]           = $criteria['onboarded'] ? '1' : '0';
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "
            SELECT DISTINCT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} meta_name ON u.ID = meta_name.user_id AND meta_name.meta_key = 'apollo_name'
            LEFT JOIN {$wpdb->usermeta} meta_industry ON u.ID = meta_industry.user_id AND meta_industry.meta_key = 'apollo_industry'
            LEFT JOIN {$wpdb->usermeta} meta_verify_status ON u.ID = meta_verify_status.user_id AND meta_verify_status.meta_key = 'apollo_verify_status'
            LEFT JOIN {$wpdb->usermeta} meta_onboarded ON u.ID = meta_onboarded.user_id AND meta_onboarded.meta_key = 'apollo_onboarded'
            WHERE {$where_clause}
            ORDER BY u.user_registered DESC
            LIMIT %d OFFSET %d
        ";

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results(
            $wpdb->prepare($query, ...$params),
            ARRAY_A
        );
    }

    /**
     * Get onboarding analytics data
     */
    public function getOnboardingAnalytics(string $period = '30d'): array
    {
        global $wpdb;

        // Convert period to MySQL date
        $date_ranges = [
            '7d'  => '7 DAY',
            '30d' => '30 DAY',
            '90d' => '90 DAY',
            '1y'  => '365 DAY',
        ];

        $date_range = $date_ranges[ $period ] ?? '30 DAY';

        // Get onboarding completions over time
        $completions = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT 
                DATE(meta_value) as date,
                COUNT(*) as completions
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'apollo_onboarded_at'
            AND meta_value >= DATE_SUB(NOW(), INTERVAL %s)
            GROUP BY DATE(meta_value)
            ORDER BY date ASC
        ",
                $date_range
            ),
            ARRAY_A
        );

        // Get industry distribution
        $industries = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT 
                meta_value as industry,
                COUNT(*) as count
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'apollo_industry'
            AND user_id IN (
                SELECT user_id FROM {$wpdb->usermeta}
                WHERE meta_key = 'apollo_onboarded_at'
                AND meta_value >= DATE_SUB(NOW(), INTERVAL %s)
            )
            GROUP BY meta_value
            ORDER BY count DESC
        ",
                $date_range
            ),
            ARRAY_A
        );

        return [
            'completions_over_time' => $completions,
            'industry_distribution' => $industries,
            'period'                => $period,
        ];
    }
}
