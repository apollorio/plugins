<?php
/**
 * Plans List - Cena::Rio
 * Lista de planos de evento do usuário
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
// TODO: Implementar busca de planos quando sistema estiver pronto
$user_plans = array();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-foreground">Planos de Evento</h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie seus planos de evento
            </p>
        </div>
        <a href="<?php echo esc_url(home_url('/pla/new')); ?>" class="btn btn-primary">
            <i class="ri-add-line mr-2"></i>
            Novo Plano
        </a>
    </div>

    <!-- Plans Grid -->
    <?php if (empty($user_plans)): ?>
        <div class="card p-12 text-center">
            <i class="ri-calendar-line text-6xl text-muted-foreground mb-4"></i>
            <h3 class="text-xl font-semibold text-foreground mb-2">Nenhum plano criado</h3>
            <p class="text-muted-foreground mb-6">Crie seu primeiro plano de evento</p>
            <a href="<?php echo esc_url(home_url('/pla/new')); ?>" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Criar Plano
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($user_plans as $plan): ?>
                <div class="card hover:shadow-lg transition-shadow">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo esc_html($plan->post_title); ?></h3>
                    </div>
                    <div class="card-content">
                        <p class="text-sm text-muted-foreground">Conteúdo do plano...</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

