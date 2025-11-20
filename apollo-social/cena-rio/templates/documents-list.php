<?php
/**
 * Documents List - Cena::Rio
 * Lista de documentos do usuário
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_documents = function_exists('Apollo\CenaRio\CenaRioModule::getUserDocuments') 
    ? Apollo\CenaRio\CenaRioModule::getUserDocuments($user_id)
    : array();
$max_documents = function_exists('Apollo\CenaRio\CenaRioModule::MAX_DOCUMENTS_PER_USER')
    ? Apollo\CenaRio\CenaRioModule::MAX_DOCUMENTS_PER_USER
    : 5;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-foreground">Meus Documentos</h2>
            <p class="text-sm text-muted-foreground mt-1">
                <?php echo count($user_documents); ?> de <?php echo $max_documents; ?> documentos criados
            </p>
        </div>
        <?php if (count($user_documents) < $max_documents): ?>
            <a href="<?php echo esc_url(home_url('/doc/new')); ?>" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Novo Documento
            </a>
        <?php else: ?>
            <div class="badge badge-secondary">
                Limite atingido
            </div>
        <?php endif; ?>
    </div>

    <!-- Documents Grid -->
    <?php if (empty($user_documents)): ?>
        <div class="card p-12 text-center">
            <i class="ri-file-text-line text-6xl text-muted-foreground mb-4"></i>
            <h3 class="text-xl font-semibold text-foreground mb-2">Nenhum documento criado</h3>
            <p class="text-muted-foreground mb-6">Crie seu primeiro documento para começar</p>
            <a href="<?php echo esc_url(home_url('/doc/new')); ?>" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Criar Documento
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($user_documents as $doc): ?>
                <div class="card hover:shadow-lg transition-shadow cursor-pointer">
                    <a href="<?php echo esc_url(get_permalink($doc->ID)); ?>" class="block">
                        <div class="card-header">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="card-title text-lg"><?php echo esc_html($doc->post_title); ?></h3>
                                    <p class="card-description mt-1">
                                        <?php echo esc_html(wp_trim_words($doc->post_content, 15)); ?>
                                    </p>
                                </div>
                                <i class="ri-file-text-line text-2xl text-muted-foreground"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <i class="ri-calendar-line"></i>
                                <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($doc->post_date))); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

