<?php
/**
 * Template: Cena::Rio Dashboard
 * Baseado em ShadCN Sidebar-15
 * https://ui.shadcn.com/view/new-york-v4/sidebar-15
 * 
 * Features:
 * - Sidebar com logo clicável
 * - Centro de notificações ao clicar no logo
 * - Resumo de mensagens de chat
 * - Layout responsivo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se usuário tem acesso
if (!current_user_can('cena-rio') && !current_user_can('administrator')) {
    wp_die('Acesso negado. Você precisa da permissão "cena-rio" para acessar esta página.', 'Acesso Negado', array('response' => 403));
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Buscar documentos do usuário
$user_documents = function_exists('Apollo\CenaRio\CenaRioModule::getUserDocuments') 
    ? Apollo\CenaRio\CenaRioModule::getUserDocuments($user_id)
    : array();

// Buscar notificações/mensagens (placeholder - integrar com sistema de chat)
$notifications = array(); // TODO: Integrar com sistema de chat

get_header();
?>

<div class="flex h-screen w-full overflow-hidden bg-background">
    <!-- Sidebar -->
    <aside class="sidebar border-r border-border bg-card w-64 flex-shrink-0 flex flex-col">
        <!-- Sidebar Header -->
        <div class="sidebar-header flex items-center justify-between p-4 border-b border-border">
            <div class="flex items-center gap-2">
                <!-- Logo clicável -->
                <button 
                    id="logoButton"
                    class="flex items-center justify-center w-10 h-10 rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-ring"
                    aria-label="Abrir notificações"
                >
                    <i class="ri-notification-3-line text-xl"></i>
                </button>
                <div class="flex flex-col">
                    <h2 class="text-sm font-semibold text-foreground">Cena::Rio</h2>
                    <p class="text-xs text-muted-foreground">Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="sidebar-content flex-1 overflow-y-auto p-4 space-y-1">
            <a href="<?php echo esc_url(home_url('/cena-rio')); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <i class="ri-home-4-line"></i>
                <span>Início</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/cena-rio?tab=documents')); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <i class="ri-file-text-line"></i>
                <span>Documentos</span>
                <?php if (!empty($user_documents)): ?>
                    <span class="ml-auto badge badge-primary"><?php echo count($user_documents); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?php echo esc_url(home_url('/cena-rio?tab=plans')); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <i class="ri-calendar-line"></i>
                <span>Planos de Evento</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/chat')); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <i class="ri-message-3-line"></i>
                <span>Mensagens</span>
                <?php if (!empty($notifications)): ?>
                    <span class="ml-auto badge badge-primary"><?php echo count($notifications); ?></span>
                <?php endif; ?>
            </a>
            
            <div class="separator my-4"></div>
            
            <a href="<?php echo esc_url(get_author_posts_url($user_id)); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <i class="ri-user-line"></i>
                <span>Meu Perfil</span>
            </a>
            
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-destructive hover:bg-destructive/10 hover:text-destructive transition-colors">
                <i class="ri-logout-box-r-line"></i>
                <span>Sair</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer p-4 border-t border-border">
            <div class="flex items-center gap-3">
                <div class="avatar">
                    <?php echo get_avatar($user_id, 32, '', '', array('class' => 'rounded-full')); ?>
                </div>
                <div class="flex flex-col flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground truncate">
                        <?php echo esc_html($current_user->display_name); ?>
                    </p>
                    <p class="text-xs text-muted-foreground truncate">
                        <?php echo esc_html($current_user->user_email); ?>
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="border-b border-border bg-card px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Bem-vindo, <?php echo esc_html($current_user->display_name); ?>!</h1>
                    <p class="text-sm text-muted-foreground mt-1">Gerencie seus documentos e planos de evento</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary">
                        <i class="ri-search-line mr-2"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-6">
            <?php
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
            
            switch ($tab) {
                case 'documents':
                    include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/documents-list.php';
                    break;
                case 'plans':
                    include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/plans-list.php';
                    break;
                default:
                    include APOLLO_SOCIAL_PLUGIN_DIR . 'cena-rio/templates/dashboard-content.php';
                    break;
            }
            ?>
        </div>
    </main>

    <!-- Notifications Center Modal -->
    <div id="notificationsModal" 
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-card border border-border rounded-lg shadow-lg w-full max-w-md mx-4 max-h-[80vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground">Centro de Notificações</h2>
                <button id="closeNotifications" 
                        class="text-muted-foreground hover:text-foreground transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <!-- Notifications Content -->
            <div class="flex-1 overflow-y-auto p-4">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-8">
                        <i class="ri-notification-off-line text-4xl text-muted-foreground mb-4"></i>
                        <p class="text-muted-foreground">Nenhuma notificação no momento</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="p-3 rounded-md border border-border hover:bg-accent transition-colors cursor-pointer">
                                <p class="text-sm font-medium text-foreground"><?php echo esc_html($notification['title']); ?></p>
                                <p class="text-xs text-muted-foreground mt-1"><?php echo esc_html($notification['message']); ?></p>
                                <p class="text-xs text-muted-foreground mt-2"><?php echo esc_html($notification['time']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Notifications Modal
document.getElementById('logoButton')?.addEventListener('click', function() {
    document.getElementById('notificationsModal')?.classList.remove('hidden');
    document.getElementById('notificationsModal')?.classList.add('flex');
});

document.getElementById('closeNotifications')?.addEventListener('click', function() {
    document.getElementById('notificationsModal')?.classList.add('hidden');
    document.getElementById('notificationsModal')?.classList.remove('flex');
});

// Fechar ao clicar fora
document.getElementById('notificationsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
        this.classList.remove('flex');
    }
});
</script>

<?php get_footer(); ?>

