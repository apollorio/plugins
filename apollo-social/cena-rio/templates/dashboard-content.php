<?php
/**
 * Dashboard Content - Cena::Rio
 * Conteúdo principal do dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_documents = function_exists('Apollo\CenaRio\CenaRioModule::getUserDocuments') 
    ? Apollo\CenaRio\CenaRioModule::getUserDocuments($user_id)
    : array();
?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Documentos</p>
                    <p class="text-2xl font-bold text-foreground mt-1"><?php echo count($user_documents); ?></p>
                </div>
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="ri-file-text-line text-primary text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Planos de Evento</p>
                    <p class="text-2xl font-bold text-foreground mt-1">0</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
                    <i class="ri-calendar-line text-secondary text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Mensagens</p>
                    <p class="text-2xl font-bold text-foreground mt-1">0</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
                    <i class="ri-message-3-line text-accent text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Documents -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Documentos Recentes</h3>
            <p class="card-description">Seus documentos mais recentes</p>
        </div>
        <div class="card-content">
            <?php if (empty($user_documents)): ?>
                <div class="text-center py-8">
                    <i class="ri-file-text-line text-4xl text-muted-foreground mb-4"></i>
                    <p class="text-muted-foreground mb-4">Nenhum documento criado ainda</p>
                    <a href="<?php echo esc_url(home_url('/doc/new')); ?>" class="btn btn-primary">
                        <i class="ri-add-line mr-2"></i>
                        Criar Documento
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($user_documents, 0, 5) as $doc): ?>
                        <a href="<?php echo esc_url(get_permalink($doc->ID)); ?>" 
                           class="flex items-center justify-between p-3 rounded-md border border-border hover:bg-accent transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ri-file-text-line text-xl text-muted-foreground"></i>
                                <div>
                                    <p class="text-sm font-medium text-foreground"><?php echo esc_html($doc->post_title); ?></p>
                                    <p class="text-xs text-muted-foreground">
                                        <?php echo esc_html(human_time_diff(strtotime($doc->post_date), current_time('timestamp'))); ?> atrás
                                    </p>
                                </div>
                            </div>
                            <i class="ri-arrow-right-s-line text-muted-foreground"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($user_documents)): ?>
            <div class="card-footer">
                <a href="<?php echo esc_url(home_url('/cena-rio?tab=documents')); ?>" 
                   class="btn btn-ghost w-full">
                    Ver Todos os Documentos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

