<?php
namespace Apollo\Infrastructure\Rendering;

use Apollo\Domain\Groups\Repositories\GroupsRepository;

/**
 * Group Page Renderer
 */
class GroupPageRenderer
{
    private GroupsRepository $repository;

    public function __construct()
    {
        $this->repository = new GroupsRepository();
    }
    public function render($template_data)
    {
        $type = $template_data['type'];
        $slug = $template_data['param'];
        
        $group_data = $this->getGroupData($type, $slug);
        
        return [
            'title' => $group_data['name'],
            'content' => $this->renderGroupPage($group_data),
            'breadcrumbs' => ['Apollo Social', ucfirst($type) . 's', $group_data['name']],
            'group' => $group_data,
        ];
    }

    private function getGroupData($type, $slug)
    {
        $group = $this->repository->findBySlug($slug);
        
        if (!$group || $group->type !== $type) {
            return [
                'id' => 0,
                'name' => 'Grupo não encontrado',
                'slug' => $slug,
                'type' => $type,
                'description' => 'O grupo solicitado não foi encontrado.',
                'members_count' => 0,
                'created' => '',
            ];
        }

        // Check if user can view (published or admin)
        if ($group->status !== 'published' && !current_user_can('manage_options')) {
            return [
                'id' => 0,
                'name' => 'Grupo não disponível',
                'slug' => $slug,
                'type' => $type,
                'description' => 'Este grupo não está disponível no momento.',
                'members_count' => 0,
                'created' => '',
            ];
        }
        
        return [
            'id' => $group->id,
            'name' => $group->title,
            'slug' => $group->slug,
            'type' => $group->type,
            'description' => $group->description,
            'members_count' => $group->members_count ?? 0,
            'created' => $group->created_at,
            'status' => $group->status,
            'visibility' => $group->visibility ?? 'public',
            'creator_id' => $group->creator_id ?? $group->created_by,
        ];
    }

    private function renderGroupPage($group_data)
    {
        if ($group_data['id'] === 0) {
            ob_start();
            ?>
            <div class="apollo-group-single apollo-container">
                <div class="shadcn-card rounded-lg border bg-card p-8 text-center">
                    <i class="ri-error-warning-line text-4xl text-muted-foreground mb-4"></i>
                    <h1 class="text-2xl font-bold mb-2"><?= esc_html($group_data['name']) ?></h1>
                    <p class="text-muted-foreground"><?= esc_html($group_data['description']) ?></p>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        $is_member = false;
        if (is_user_logged_in()) {
            $is_member = $this->repository->isMember($group_data['id'], get_current_user_id());
        }

        ob_start();
        ?>
        <div class="apollo-group-single apollo-container" data-motion-page="group-single">
            <!-- ShadCN Card Header -->
            <div class="shadcn-card rounded-lg border bg-card mb-6">
                <div class="shadcn-card-header">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="shadcn-card-title text-3xl font-bold mb-2"><?= esc_html($group_data['name']) ?></h1>
                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <i class="ri-group-line"></i>
                                    <?= intval($group_data['members_count']) ?> membros
                                </span>
                                <?php if (!empty($group_data['created'])): ?>
                                <span class="flex items-center gap-1">
                                    <i class="ri-calendar-line"></i>
                                    Criado em <?= date_i18n('d/m/Y', strtotime($group_data['created'])) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (is_user_logged_in() && !$is_member): ?>
                        <button class="shadcn-button shadcn-button-primary" 
                                data-group-id="<?= esc_attr($group_data['id']) ?>"
                                data-action="join-group">
                            <i class="ri-user-add-line mr-2"></i>
                            Participar
                        </button>
                        <?php elseif ($is_member): ?>
                        <button class="shadcn-button shadcn-button-ghost" 
                                data-group-id="<?= esc_attr($group_data['id']) ?>"
                                data-action="leave-group">
                            <i class="ri-user-unfollow-line mr-2"></i>
                            Sair
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="shadcn-card-content">
                    <p class="text-muted-foreground"><?= wp_kses_post(nl2br($group_data['description'])) ?></p>
                </div>
            </div>
            
            <!-- Group Content Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="shadcn-card rounded-lg border bg-card p-6">
                        <h2 class="text-xl font-semibold mb-4">Sobre o Grupo</h2>
                        <div class="prose prose-sm max-w-none">
                            <?= wp_kses_post($group_data['description']) ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="shadcn-card rounded-lg border bg-card p-6">
                        <h3 class="font-semibold mb-4">Informações</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Tipo:</dt>
                                <dd class="font-medium"><?= esc_html(ucfirst($group_data['type'])) ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Membros:</dt>
                                <dd class="font-medium"><?= intval($group_data['members_count']) ?></dd>
                            </div>
                            <?php if (!empty($group_data['created'])): ?>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Criado:</dt>
                                <dd class="font-medium"><?= date_i18n('d/m/Y', strtotime($group_data['created'])) ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Motion.dev initialization
        (function() {
            if (typeof window.motion !== 'undefined') {
                const page = document.querySelector('[data-motion-page="group-single"]');
                if (page) {
                    window.motion.animate(page, {
                        opacity: [0, 1],
                        y: [20, 0]
                    }, {
                        duration: 0.5,
                        easing: 'ease-out'
                    });
                }
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}