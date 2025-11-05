<?php

namespace Apollo\Modules\UI\Renderers;

/**
 * Command Center
 * 
 * Quick action dashboard for Apollo Social management
 */
class CommandCenter
{
    public function render(): string
    {
        $user = wp_get_current_user();
        $permissions = $this->getUserPermissions();
        
        return $this->renderCommandCenter($permissions);
    }

    private function renderCommandCenter(array $permissions): string
    {
        ob_start();
        ?>
        <div class="apollo-command-center">
            <style>
                .apollo-command-center {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    color: white;
                }

                .command-header {
                    text-align: center;
                    margin-bottom: 40px;
                }

                .command-header h1 {
                    font-size: 2.5rem;
                    margin: 0 0 10px 0;
                    font-weight: 700;
                    background: linear-gradient(45deg, #fff, #e0e0e0);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }

                .command-subtitle {
                    font-size: 1.1rem;
                    opacity: 0.9;
                    margin: 0;
                }

                .command-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }

                .command-card {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 15px;
                    padding: 25px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    transition: all 0.3s ease;
                }

                .command-card:hover {
                    transform: translateY(-5px);
                    background: rgba(255, 255, 255, 0.15);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                }

                .card-title {
                    font-size: 1.3rem;
                    font-weight: 600;
                    margin: 0 0 15px 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .card-icon {
                    font-size: 1.5rem;
                }

                .command-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .command-btn {
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    text-decoration: none;
                }

                .command-btn:hover {
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(1.02);
                }

                .command-btn.primary {
                    background: linear-gradient(45deg, #4facfe, #00f2fe);
                    font-weight: 600;
                }

                .command-btn.success {
                    background: linear-gradient(45deg, #43e97b, #38f9d7);
                }

                .command-btn.warning {
                    background: linear-gradient(45deg, #fa709a, #fee140);
                }

                .command-btn.danger {
                    background: linear-gradient(45deg, #ff6b6b, #ffa726);
                }

                .btn-icon {
                    font-size: 1.1rem;
                }

                .permission-badge {
                    background: rgba(255, 255, 255, 0.2);
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    margin-left: auto;
                }

                .quick-stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin-bottom: 30px;
                }

                .stat-item {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                }

                .stat-number {
                    font-size: 2rem;
                    font-weight: 700;
                    margin: 0;
                }

                .stat-label {
                    font-size: 0.9rem;
                    opacity: 0.8;
                    margin: 5px 0 0 0;
                }

                .disabled {
                    opacity: 0.5;
                    pointer-events: none;
                }

                .status-indicator {
                    display: inline-block;
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    margin-right: 8px;
                }

                .status-online { background: #4ade80; }
                .status-pending { background: #fbbf24; }
                .status-offline { background: #ef4444; }

                @media (max-width: 768px) {
                    .apollo-command-center {
                        padding: 15px;
                    }

                    .command-header h1 {
                        font-size: 2rem;
                    }

                    .command-grid {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }

                    .quick-stats {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }
            </style>

            <div class="command-header">
                <h1>🚀 Apollo Command Center</h1>
                <p class="command-subtitle">Centro de Controle e Ações Rápidas</p>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-number" id="groups-count">0</div>
                    <div class="stat-label">Grupos Ativos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="events-count">0</div>
                    <div class="stat-label">Eventos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="pending-count">0</div>
                    <div class="stat-label">Pendentes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="users-count">0</div>
                    <div class="stat-label">Usuários Online</div>
                </div>
            </div>

            <!-- Command Grid -->
            <div class="command-grid">
                
                <!-- Content Management -->
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">📝</span>
                        Gerenciar Conteúdo
                    </h3>
                    <div class="command-actions">
                        <?php if ($permissions['can_create_groups']): ?>
                        <a href="/apollo/groups/create" class="command-btn primary">
                            <span>Criar Grupo</span>
                            <span class="btn-icon">➕</span>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($permissions['can_create_events']): ?>
                        <a href="/apollo/events/create" class="command-btn success">
                            <span>Criar Evento</span>
                            <span class="btn-icon">📅</span>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($permissions['can_create_ads']): ?>
                        <a href="/apollo/ads/create" class="command-btn warning">
                            <span>Criar Anúncio</span>
                            <span class="btn-icon">📢</span>
                        </a>
                        <?php endif; ?>
                        
                        <a href="/apollo/content/list" class="command-btn">
                            <span>Ver Todo Conteúdo</span>
                            <span class="btn-icon">📋</span>
                        </a>
                    </div>
                </div>

                <!-- Moderation -->
                <?php if ($permissions['can_moderate']): ?>
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">⚖️</span>
                        Moderação
                        <span class="permission-badge">Moderador</span>
                    </h3>
                    <div class="command-actions">
                        <a href="/apollo/moderation/queue" class="command-btn danger">
                            <span>Fila de Moderação</span>
                            <span class="btn-icon">🔍</span>
                        </a>
                        <a href="/apollo/moderation/reports" class="command-btn">
                            <span>Denúncias</span>
                            <span class="btn-icon">⚠️</span>
                        </a>
                        <a href="/apollo/moderation/rules" class="command-btn">
                            <span>Configurar Regras</span>
                            <span class="btn-icon">⚙️</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Analytics -->
                <?php if ($permissions['can_view_analytics']): ?>
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">📊</span>
                        Analytics
                        <?php if ($permissions['can_manage_analytics']): ?>
                        <span class="permission-badge">Gerente</span>
                        <?php endif; ?>
                    </h3>
                    <div class="command-actions">
                        <a href="/apollo/analytics" class="command-btn primary">
                            <span>Dashboard Principal</span>
                            <span class="btn-icon">📈</span>
                        </a>
                        <a href="/apollo/analytics/realtime" class="command-btn success">
                            <span>Tempo Real</span>
                            <span class="status-indicator status-online"></span>
                        </a>
                        <?php if ($permissions['can_export_analytics']): ?>
                        <a href="#" onclick="exportAnalytics()" class="command-btn">
                            <span>Exportar Dados</span>
                            <span class="btn-icon">💾</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- User Management -->
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">👥</span>
                        Usuários
                    </h3>
                    <div class="command-actions">
                        <a href="/apollo/users/profile" class="command-btn primary">
                            <span>Meu Perfil</span>
                            <span class="btn-icon">👤</span>
                        </a>
                        <a href="/apollo/users/groups" class="command-btn">
                            <span>Meus Grupos</span>
                            <span class="btn-icon">🏠</span>
                        </a>
                        <a href="/apollo/users/events" class="command-btn">
                            <span>Meus Eventos</span>
                            <span class="btn-icon">🎫</span>
                        </a>
                        <?php if ($permissions['user_level'] >= 4): ?>
                        <a href="/apollo/users/manage" class="command-btn warning">
                            <span>Gerenciar Usuários</span>
                            <span class="btn-icon">⚙️</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Tools -->
                <?php if ($permissions['user_level'] >= 4): ?>
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">🔧</span>
                        Ferramentas do Sistema
                        <span class="permission-badge">Admin</span>
                    </h3>
                    <div class="command-actions">
                        <a href="/apollo/onboarding" class="command-btn primary">
                            <span>Onboarding</span>
                            <span class="btn-icon">🎯</span>
                        </a>
                        <a href="/apollo/signatures" class="command-btn success">
                            <span>Assinaturas</span>
                            <span class="btn-icon">✍️</span>
                        </a>
                        <a href="/apollo/settings" class="command-btn">
                            <span>Configurações</span>
                            <span class="btn-icon">⚙️</span>
                        </a>
                        <a href="#" onclick="systemDiagnostics()" class="command-btn danger">
                            <span>Diagnósticos</span>
                            <span class="btn-icon">🩺</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="command-card">
                    <h3 class="card-title">
                        <span class="card-icon">⚡</span>
                        Ações Rápidas
                    </h3>
                    <div class="command-actions">
                        <a href="#" onclick="searchContent()" class="command-btn primary">
                            <span>Busca Global</span>
                            <span class="btn-icon">🔍</span>
                        </a>
                        <a href="#" onclick="showNotifications()" class="command-btn">
                            <span>Notificações</span>
                            <span class="btn-icon">🔔</span>
                        </a>
                        <a href="#" onclick="helpCenter()" class="command-btn">
                            <span>Central de Ajuda</span>
                            <span class="btn-icon">❓</span>
                        </a>
                        <a href="/apollo/backup" class="command-btn warning">
                            <span>Backup/Restore</span>
                            <span class="btn-icon">💾</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Initialize Command Center
            document.addEventListener('DOMContentLoaded', function() {
                loadQuickStats();
                initializeCommandActions();
            });

            // Load quick statistics
            function loadQuickStats() {
                // Simulate loading stats (replace with actual AJAX calls)
                setTimeout(() => {
                    document.getElementById('groups-count').textContent = '127';
                    document.getElementById('events-count').textContent = '43';
                    document.getElementById('pending-count').textContent = '8';
                    document.getElementById('users-count').textContent = '24';
                }, 500);
                
                // Update stats every 30 seconds
                setInterval(loadQuickStats, 30000);
            }

            // Initialize command actions
            function initializeCommandActions() {
                // Add keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key) {
                            case 'g': // Ctrl+G for groups
                                e.preventDefault();
                                window.location.href = '/apollo/groups/create';
                                break;
                            case 'e': // Ctrl+E for events
                                e.preventDefault();
                                window.location.href = '/apollo/events/create';
                                break;
                            case 'f': // Ctrl+F for search
                                e.preventDefault();
                                searchContent();
                                break;
                        }
                    }
                });
            }

            // Quick action functions
            function searchContent() {
                const query = prompt('Digite sua busca:');
                if (query) {
                    window.location.href = `/apollo/search?q=${encodeURIComponent(query)}`;
                }
            }

            function showNotifications() {
                // Implementation would open notifications panel
                alert('Painel de notificações em desenvolvimento');
            }

            function helpCenter() {
                window.open('/apollo/help', '_blank');
            }

            function exportAnalytics() {
                if (confirm('Exportar dados de analytics?')) {
                    // Implementation would trigger export
                    alert('Export iniciado. Você receberá um email quando estiver pronto.');
                }
            }

            function systemDiagnostics() {
                if (confirm('Executar diagnósticos do sistema?')) {
                    // Implementation would run diagnostics
                    window.location.href = '/apollo/diagnostics';
                }
            }
        </script>
        <?php
        return ob_get_clean();
    }

    private function getUserPermissions(): array
    {
        $user = wp_get_current_user();
        
        return [
            'can_create_groups' => current_user_can('create_apollo_groups'),
            'can_create_events' => current_user_can('create_eva_events'),
            'can_create_ads' => current_user_can('create_apollo_ads'),
            'can_moderate' => current_user_can('apollo_moderate'),
            'can_view_analytics' => current_user_can('apollo_view_analytics'),
            'can_manage_analytics' => current_user_can('apollo_manage_analytics'),
            'can_export_analytics' => current_user_can('apollo_export_analytics'),
            'user_level' => $this->getUserLevel($user),
            'role_name' => $this->getRoleName($user)
        ];
    }

    private function getUserLevel($user): int
    {
        if (in_array('administrator', $user->roles)) return 5;
        if (in_array('editor', $user->roles)) return 4;
        if (in_array('author', $user->roles)) return 3;
        if (in_array('contributor', $user->roles)) return 2;
        if (in_array('subscriber', $user->roles)) return 1;
        return 0;
    }

    private function getRoleName($user): string
    {
        $roles = [
            'administrator' => 'Administrador',
            'editor' => 'Editor', 
            'author' => 'Autor',
            'contributor' => 'Colaborador',
            'subscriber' => 'Assinante'
        ];

        foreach ($user->roles as $role) {
            if (isset($roles[$role])) {
                return $roles[$role];
            }
        }

        return 'Usuário';
    }
}