<?php
/**
 * User Dashboard Template - ShadCN + Motion.dev
 * Customizable dashboard with draggable widgets
 */

if (!defined('ABSPATH')) exit;

$user = $view['data']['user'] ?? [];
$widgets = $view['data']['widgets'] ?? [];
$depoimentos = $view['data']['depoimentos'] ?? [];
$can_edit = $view['data']['can_edit'] ?? false;
$page_id = $view['data']['page_id'] ?? 0;
?>
<div id="apollo-dashboard" class="apollo-dashboard" data-user-id="<?php echo esc_attr($user['id']); ?>" data-page-id="<?php echo esc_attr($page_id); ?>">
    
    <?php if ($can_edit): ?>
    <div class="apollo-dashboard-toolbar">
        <button class="apollo-edit-toggle apollo-button">
            <?php esc_html_e('Editar Layout', 'apollo-social'); ?>
        </button>
        <button class="apollo-save-layout apollo-button apollo-button-primary" style="display: none;">
            <?php esc_html_e('Salvar Layout', 'apollo-social'); ?>
        </button>
        <button class="apollo-add-widget apollo-button">
            <?php esc_html_e('+ Adicionar Widget', 'apollo-social'); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Widgets will be rendered here by JavaScript -->
    <div class="apollo-widgets-container" id="apollo-widgets-container">
        <!-- Widgets loaded dynamically -->
    </div>

</div>

<script>
// Pass data to JavaScript
window.apolloCanvasData = window.apolloCanvasData || {};
window.apolloCanvasData.widgets = <?php echo json_encode($widgets); ?>;
window.apolloCanvasData.depoimentos = <?php echo json_encode($depoimentos); ?>;
window.apolloCanvasData.user = <?php echo json_encode($user); ?>;
window.apolloCanvasData.can_edit = <?php echo $can_edit ? 'true' : 'false'; ?>;
window.apolloCanvasData.page_id = <?php echo absint($page_id); ?>;
</script>

