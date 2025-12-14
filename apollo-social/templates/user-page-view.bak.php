<?php
/**
 * Template: Página pública do usuário
 * Usa Tailwind + shadcn + uni.css
 */
if (! defined('ABSPATH')) {
    exit;
}

$user_id = get_query_var('apollo_user_id');
if (! $user_id) {
    wp_die('Usuário não encontrado');
}

$user = get_userdata($user_id);
if (! $user) {
    wp_die('Usuário não encontrado');
}

$post_id = get_user_meta($user_id, 'apollo_user_page_id', true);
$layout  = get_post_meta($post_id, 'apollo_userpage_layout_v1', true);
if (! is_array($layout)) {
    $layout = [];
}

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');

get_header();
?>

<div class="container max-w-4xl mx-auto px-4 py-8">
  
	<!-- Header do Perfil -->
	<header class="profile-header mb-8 text-center">
	<?php echo get_avatar($user_id, 128, '', '', [ 'class' => 'rounded-full mx-auto mb-4 shadow-lg' ]); ?>
	<h1 class="text-4xl font-bold mb-2"><?php echo esc_html($user->display_name); ?></h1>
	<p class="text-muted-foreground">@<?php echo esc_html($user->user_login); ?></p>
	
	<?php if (get_current_user_id() == $user_id) : ?>
		<a href="<?php echo esc_url(add_query_arg('action', 'edit')); ?>" 
		class="btn btn-outline mt-4 inline-flex items-center gap-2">
		<i class="ri-edit-line"></i>
		Editar Página
		</a>
	<?php endif; ?>
	</header>

	<!-- Widgets do Layout -->
	<div id="userpage-widgets" class="grid gap-4 md:grid-cols-2">
	<?php
    if (! empty($layout['grid'])) {
        $widgets = Apollo_User_Page_Widgets::get_widgets();
        foreach ($layout['grid'] as $widget_data) {
            $widget_id = $widget_data['widget'] ?? '';
            $props     = $widget_data['props']  ?? [];

            if (isset($widgets[ $widget_id ]) && isset($widgets[ $widget_id ]['render'])) {
                $ctx = [
                    'user_id'      => $user_id,
                    'post_id'      => $post_id,
                    'display_name' => $user->display_name,
                ];
                echo $widgets[ $widget_id ]['render']($props, $ctx);
            }
        }
    } else {
        echo '<p class="text-muted-foreground col-span-full text-center py-8">Nenhum conteúdo adicionado ainda.</p>';
    }
?>
	</div>

	<!-- Depoimentos (Comments) -->
	<section class="depoimentos mt-12">
	<h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
		<i class="ri-chat-3-line"></i>
		Depoimentos
	</h2>
	<?php
// Force post ID for comments
global $post;
$post = get_post($post_id);
setup_postdata($post);

if (comments_open($post_id)) {
    comments_template();
} else {
    echo '<p class="text-muted-foreground">Depoimentos desativados.</p>';
}

wp_reset_postdata();
?>
	</section>

</div>

<?php get_footer(); ?>
