<?php

namespace Apollo\Modules\Builder\Admin;

use Apollo\Modules\Builder\LayoutRepository;

class BuilderAdminPage
{
    private LayoutRepository $repository;

    public function __construct(LayoutRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 35);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Apollo Builder', 'apollo-social'),
            __('Apollo Builder', 'apollo-social'),
            'edit_users',
            'apollo-builder',
            [$this, 'renderPage'],
            'dashicons-layout',
            31
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_apollo-builder') {
            return;
        }

        $pluginFile = APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php';

        wp_enqueue_style('apollo-uni-css');
        wp_enqueue_style('apollo-social-builder');

        wp_enqueue_script(
            'interactjs',
            'https://cdn.jsdelivr.net/npm/interactjs@1.10.17/dist/interact.min.js',
            [],
            '1.10.17',
            true
        );

        wp_enqueue_script('apollo-social-builder-runtime');

        wp_localize_script('apollo-social-builder-runtime', 'apolloBuilder', [
            'restUrl' => esc_url_raw(rest_url('apollo-social/v1/builder/layout')),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentUser' => get_current_user_id(),
            'layout' => $this->repository->getLayout(get_current_user_id()),
        ]);
    }

    public function renderPage(): void
    {
        $currentUser = wp_get_current_user();
        ?>
        <div class="wrap apollo-builder-wrap">
            <header class="apollo-builder-header">
                <h1 class="apollo-builder-title">
                    <span class="ri-layout-masonry-fill"></span>
                    <?php esc_html_e('Apollo Habbo Builder', 'apollo-social'); ?>
                </h1>
                <div class="apollo-builder-toolbar">
                    <button class="apollo-btn primary" id="apollo-builder-save">
                        <?php esc_html_e('Salvar layout', 'apollo-social'); ?>
                    </button>
                    <button class="apollo-btn ghost" id="apollo-builder-export">
                        <?php esc_html_e('Exportar JSON', 'apollo-social'); ?>
                    </button>
                    <label class="apollo-btn ghost">
                        <?php esc_html_e('Importar JSON', 'apollo-social'); ?>
                        <input type="file" id="apollo-builder-import" accept="application/json" hidden>
                    </label>
                </div>
            </header>

            <main class="apollo-builder-main">
                <aside class="apollo-builder-sidebar">
                    <h2><?php esc_html_e('Widgets', 'apollo-social'); ?></h2>
                    <div class="apollo-widget-library" id="apollo-widget-library">
                        <button class="apollo-widget-item" data-widget="apollo_sticky_note">
                            <span>📝</span>
                            <strong><?php esc_html_e('Sticky Note', 'apollo-social'); ?></strong>
                            <small><?php esc_html_e('Nota adesiva Tailwind/Shadcn', 'apollo-social'); ?></small>
                        </button>
                    </div>
                </aside>

                <section class="apollo-builder-canvas">
                    <div class="apollo-stage" id="apollo-builder-stage">
                        <p class="apollo-stage-empty">
                            <?php esc_html_e('Arraste widgets para cá e posicione-os livremente.', 'apollo-social'); ?>
                        </p>
                    </div>
                </section>

                <aside class="apollo-builder-inspector">
                    <h2><?php esc_html_e('Inspector', 'apollo-social'); ?></h2>
                    <div class="apollo-inspector-panel" id="apollo-inspector-panel">
                        <p><?php esc_html_e('Selecione um widget para editar propriedades.', 'apollo-social'); ?></p>
                    </div>
                </aside>
            </main>
        </div>
        <?php
    }
}

