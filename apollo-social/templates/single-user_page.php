<?php
/**
 * Default fallback template for user_page posts.
 *
 * @package ApolloSocial
 * @since   1.0.0
 */

defined('ABSPATH') || exit;

get_header();
?>
<main class="apollo-user-page-wrapper">
    <div class="apollo-user-page-container">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('apollo-user-page-entry'); ?>>
                    <h1 class="apollo-user-page-title"><?php the_title(); ?></h1>
                    <div class="apollo-user-page-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p>
                <?php esc_html_e('Nenhum conteúdo disponível.', 'apollo-social'); ?>
            </p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
