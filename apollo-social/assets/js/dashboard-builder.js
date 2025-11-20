/**
 * Dashboard Builder - ShadCN + Motion.dev
 * 
 * Builds customizable dashboard with draggable widgets
 * Uses Motion.dev for smooth animations
 */

(function() {
    'use strict';

    // Check dependencies
    if (typeof window.Motion === 'undefined') {
        console.error('Apollo Dashboard: Motion.dev library required');
        return;
    }

    const { motionValue, styleEffect, animate, transformValue } = window.Motion;

    /**
     * Dashboard Builder Class
     */
    class DashboardBuilder {
        constructor(container, config = {}) {
            this.container = container;
            this.config = {
                gridColumns: 12,
                gridGap: 16,
                editMode: false,
                ...config
            };

            this.widgets = [];
            this.draggedWidget = null;
            this.widgetRegistry = {};
            this.motionValues = {};

            this.init();
        }

        init() {
            // Load widgets from data
            this.loadWidgets();

            // Initialize grid system
            this.initGrid();

            // Setup drag and drop
            this.setupDragAndDrop();

            // Initialize widgets
            this.renderWidgets();

            // Setup edit mode toggle
            this.setupEditMode();
        }

        loadWidgets() {
            const data = window.apolloCanvasData || {};
            this.widgets = data.widgets || [];
            this.widgetRegistry = data.available_widgets || {};
        }

        initGrid() {
            // Create grid container with Motion.dev
            const gridStyle = {
                display: 'grid',
                gridTemplateColumns: `repeat(${this.config.gridColumns}, 1fr)`,
                gap: `${this.config.gridGap}px`,
                padding: '20px',
            };

            Object.assign(this.container.style, gridStyle);
            this.container.classList.add('apollo-dashboard-grid');
        }

        setupDragAndDrop() {
            if (!this.config.editMode) return;

            // Use Motion.dev for drag interactions
            this.container.addEventListener('mousedown', (e) => {
                const widget = e.target.closest('.apollo-widget');
                if (!widget) return;

                this.startDrag(widget, e);
            });

            document.addEventListener('mousemove', (e) => this.onDrag(e));
            document.addEventListener('mouseup', () => this.endDrag());
        }

        startDrag(widget, e) {
            this.draggedWidget = widget;
            const widgetId = widget.dataset.widgetId;
            const widgetData = this.widgets.find(w => w.id === widgetId);

            if (!widgetData) return;

            // Create motion values for drag
            const x = motionValue(0);
            const y = motionValue(0);
            const scale = motionValue(1);

            this.motionValues[widgetId] = { x, y, scale };

            // Apply drag styles with Motion
            styleEffect(widget, {
                x: transformValue(() => `${x.get()}px`),
                y: transformValue(() => `${y.get()}px`),
                scale: scale,
                zIndex: motionValue(1000),
            });

            widget.classList.add('apollo-dragging');
        }

        onDrag(e) {
            if (!this.draggedWidget) return;

            const widgetId = this.draggedWidget.dataset.widgetId;
            const motion = this.motionValues[widgetId];

            if (!motion) return;

            // Update position with smooth animation
            const rect = this.container.getBoundingClientRect();
            const gridX = Math.round((e.clientX - rect.left) / (rect.width / this.config.gridColumns));
            const gridY = Math.round((e.clientY - rect.top) / (this.config.gridGap + 100));

            animate(motion.x, gridX * (rect.width / this.config.gridColumns), {
                duration: 0.1,
                easing: 'ease-out'
            });

            animate(motion.y, gridY * (this.config.gridGap + 100), {
                duration: 0.1,
                easing: 'ease-out'
            });

            // Scale effect during drag
            animate(motion.scale, 1.05, { duration: 0.2 });
        }

        endDrag() {
            if (!this.draggedWidget) return;

            const widgetId = this.draggedWidget.dataset.widgetId;
            const motion = this.motionValues[widgetId];

            if (motion) {
                // Snap back with animation
                animate(motion.x, 0, { duration: 0.3, easing: 'ease-out' });
                animate(motion.y, 0, { duration: 0.3, easing: 'ease-out' });
                animate(motion.scale, 1, { duration: 0.3, easing: 'ease-out' });
            }

            this.draggedWidget.classList.remove('apollo-dragging');
            this.draggedWidget = null;
        }

        renderWidgets() {
            this.widgets.forEach((widgetData, index) => {
                const widget = this.createWidget(widgetData, index);
                this.container.appendChild(widget);
            });
        }

        createWidget(widgetData, index) {
            const widget = document.createElement('div');
            widget.className = 'apollo-widget';
            widget.dataset.widgetId = widgetData.id || `widget-${index}`;
            widget.dataset.widgetType = widgetData.type;

            // Set grid position and size
            const position = widgetData.position || { x: 0, y: index * 4 };
            const size = widgetData.size || { w: 12, h: 4 };

            widget.style.gridColumn = `span ${size.w}`;
            widget.style.gridRow = `span ${size.h}`;

            // Render widget content
            widget.innerHTML = this.renderWidgetContent(widgetData);

            // Add resize handles if edit mode
            if (this.config.editMode) {
                this.addResizeHandles(widget, widgetData);
            }

            return widget;
        }

        renderWidgetContent(widgetData) {
            const type = widgetData.type;
            const config = widgetData.config || {};

            switch (type) {
                case 'depoimentos':
                    return this.renderDepoimentosWidget(widgetData);
                case 'profile-header':
                    return this.renderProfileHeaderWidget(widgetData);
                case 'chat':
                    return this.renderChatWidget(widgetData);
                case 'sign-document':
                    return this.renderSignDocumentWidget(widgetData);
                default:
                    // ShadCN Skeleton placeholder
                    return `
                        <div class="apollo-widget-placeholder shadcn-card rounded-lg border bg-card p-4" data-motion-placeholder="true">
                            <div class="shadcn-skeleton h-4 w-3/4 mb-2"></div>
                            <div class="shadcn-skeleton h-4 w-1/2"></div>
                            <div class="shadcn-skeleton h-20 w-full mt-4"></div>
                            <p class="text-xs text-muted-foreground mt-2">Widget: ${type}</p>
                        </div>
                    `;
            }
        }

        renderDepoimentosWidget(widgetData) {
            const depoimentos = window.apolloCanvasData?.depoimentos || [];
            const config = widgetData.config || {};
            const title = config.title || 'Depoimentos';

            // ShadCN Card structure
            let html = '<div class="apollo-depoimentos-widget shadcn-card rounded-lg border bg-card text-card-foreground shadow-sm" data-motion-widget="depoimentos">';
            
            // Card Header
            html += '<div class="shadcn-card-header flex flex-row items-center justify-between space-y-0 pb-2">';
            html += `<h3 class="shadcn-card-title text-lg font-semibold leading-none tracking-tight flex items-center gap-2">`;
            html += '<i class="ri-chat-3-line"></i>';
            html += `<span>${title}</span>`;
            html += '</h3>';
            html += `<span class="text-sm text-muted-foreground">${depoimentos.length}</span>`;
            if (this.config.editMode) {
                html += '<button class="apollo-widget-edit shadcn-button-ghost h-8 w-8 p-0" aria-label="Editar widget">⚙️</button>';
            }
            html += '</div>';

            // Card Content
            html += '<div class="shadcn-card-content">';
            html += '<div class="apollo-depoimentos-list space-y-4" data-motion-list="true">';

            if (depoimentos.length === 0) {
                // ShadCN Empty State
                html += '<div class="flex flex-col items-center justify-center py-8 text-center">';
                html += '<i class="ri-chat-3-line text-4xl text-muted-foreground mb-2"></i>';
                html += '<p class="text-sm text-muted-foreground">Nenhum depoimento ainda.</p>';
                html += '<p class="text-xs text-muted-foreground mt-1">Seja o primeiro a deixar um depoimento!</p>';
                html += '</div>';
            } else {
                depoimentos.forEach((depoimento, index) => {
                    // ShadCN Card for each depoimento with Motion.dev
                    html += `<div class="apollo-depoimento-item shadcn-card rounded-lg border bg-card p-4" `;
                    html += `data-motion-item="true" `;
                    html += `data-motion-delay="${index * 50}" `;
                    html += `style="opacity: 0; transform: translateY(10px);">`;
                    
                    html += '<div class="flex items-start gap-3">';
                    html += '<div class="shadcn-avatar">';
                    html += '<div class="relative h-10 w-10 overflow-hidden rounded-full">';
                    html += `<img src="${depoimento.author?.avatar || 'https://secure.gravatar.com/avatar/?s=48&d=mm'}" `;
                    html += `alt="${depoimento.author?.name || 'Anônimo'}" class="h-full w-full object-cover">`;
                    html += '</div>';
                    html += '</div>';
                    
                    html += '<div class="flex-1 space-y-1">';
                    html += '<div class="flex items-center justify-between">';
                    html += `<p class="text-sm font-medium leading-none">${depoimento.author?.name || 'Anônimo'}</p>`;
                    html += `<p class="text-xs text-muted-foreground">${depoimento.date_formatted || ''}</p>`;
                    html += '</div>';
                    html += `<p class="text-sm text-muted-foreground">${depoimento.content || ''}</p>`;
                    html += '</div>';
                    
                    html += '</div>';
                    html += '</div>';
                });
            }

            html += '</div>';

            // Form for new depoimento (ShadCN styled)
            if (config.allow_comments !== false) {
                html += '<div class="apollo-depoimento-form mt-4 pt-4 border-t">';
                html += '<div class="shadcn-form-group space-y-2">';
                html += '<textarea ';
                html += 'placeholder="Escreva um depoimento..." ';
                html += 'class="shadcn-textarea min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm" ';
                html += 'rows="3"></textarea>';
                html += '<button ';
                html += 'class="shadcn-button shadcn-button-primary w-full sm:w-auto" ';
                html += 'data-hold-to-confirm ';
                html += 'data-hold-duration="1500">';
                html += '<i class="ri-send-plane-line mr-2"></i>';
                html += 'Enviar Depoimento';
                html += '</button>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
            html += '</div>';

            // Motion.dev initialization script
            html += '<script>';
            html += '(function() {';
            html += 'if (typeof window.motion !== "undefined") {';
            html += 'const items = document.querySelectorAll(\'[data-motion-item="true"]\');';
            html += 'items.forEach(function(item, index) {';
            html += 'const delay = parseInt(item.dataset.motionDelay || 0);';
            html += 'setTimeout(function() {';
            html += 'window.motion.animate(item, {';
            html += 'opacity: [0, 1],';
            html += 'y: [10, 0]';
            html += '}, {';
            html += 'duration: 0.4,';
            html += 'easing: "ease-out"';
            html += '}).then(function() {';
            html += 'item.style.opacity = "1";';
            html += 'item.style.transform = "translateY(0)";';
            html += '});';
            html += '}, delay);';
            html += '});';
            html += '}';
            html += '})();';
            html += '</script>';

            return html;
        }

        renderProfileHeaderWidget(widgetData) {
            const user = window.apolloCanvasData?.user || {};
            
            return `
                <div class="apollo-profile-header">
                    <div class="apollo-profile-avatar">
                        <img src="${user.avatar}" alt="${user.name}">
                    </div>
                    <div class="apollo-profile-info">
                        <h2>${user.name}</h2>
                        <p class="apollo-profile-bio">${user.bio || 'Sem biografia'}</p>
                        <div class="apollo-profile-meta">
                            <span>Membro desde ${new Date(user.registered).getFullYear()}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        renderChatWidget(widgetData) {
            return `
                <div class="apollo-chat-widget">
                    <div class="apollo-chat-header">
                        <h3>Chat</h3>
                    </div>
                    <div class="apollo-chat-messages" id="apollo-chat-messages">
                        <p class="apollo-empty-state">Nenhuma mensagem ainda</p>
                    </div>
                    <div class="apollo-chat-input">
                        <input type="text" placeholder="Digite uma mensagem...">
                        <button class="apollo-button apollo-button-primary" data-hold-to-confirm>
                            Enviar
                        </button>
                    </div>
                </div>
            `;
        }

        renderSignDocumentWidget(widgetData) {
            return `
                <div class="apollo-sign-document-widget">
                    <div class="apollo-sign-header">
                        <h3>Assinar Documento</h3>
                    </div>
                    <div class="apollo-sign-content">
                        <p>Selecione um documento para assinar</p>
                        <button class="apollo-button apollo-button-primary" data-hold-to-confirm>
                            Selecionar Documento
                        </button>
                    </div>
                </div>
            `;
        }

        addResizeHandles(widget, widgetData) {
            const handles = ['n', 's', 'e', 'w', 'ne', 'nw', 'se', 'sw'];
            
            handles.forEach(direction => {
                const handle = document.createElement('div');
                handle.className = `apollo-resize-handle apollo-resize-${direction}`;
                handle.dataset.direction = direction;
                widget.appendChild(handle);
            });
        }

        setupEditMode() {
            // Toggle edit mode
            const editToggle = document.querySelector('.apollo-edit-toggle');
            if (editToggle) {
                editToggle.addEventListener('click', () => {
                    this.config.editMode = !this.config.editMode;
                    this.container.classList.toggle('apollo-edit-mode', this.config.editMode);
                });
            }
        }

        saveLayout() {
            // Save widget positions and sizes
            const widgets = Array.from(this.container.querySelectorAll('.apollo-widget')).map(widget => {
                const rect = widget.getBoundingClientRect();
                const containerRect = this.container.getBoundingClientRect();
                
                return {
                    id: widget.dataset.widgetId,
                    type: widget.dataset.widgetType,
                    position: {
                        x: Math.round((rect.left - containerRect.left) / (containerRect.width / this.config.gridColumns)),
                        y: Math.round((rect.top - containerRect.top) / (this.config.gridGap + 100))
                    },
                    size: {
                        w: parseInt(widget.style.gridColumnEnd) - parseInt(widget.style.gridColumnStart),
                        h: parseInt(widget.style.gridRowEnd) - parseInt(widget.style.gridRowStart)
                    }
                };
            });

            // Save via AJAX
            this.saveWidgets(widgets);
        }

        saveWidgets(widgets) {
            fetch(window.apolloCanvasData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'apollo_save_widgets',
                    nonce: window.apolloCanvasData.nonce,
                    page_id: window.apolloCanvasData.page_id,
                    widgets: JSON.stringify(widgets),
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Layout salvo com sucesso');
                }
            });
        }
    }

    // Initialize dashboard when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const dashboardContainer = document.getElementById('apollo-dashboard');
        if (dashboardContainer && window.apolloCanvasData) {
            window.apolloDashboard = new DashboardBuilder(dashboardContainer, {
                editMode: window.apolloCanvasData.can_edit || false
            });
        }
    });

    // Export for global use
    window.DashboardBuilder = DashboardBuilder;

})();

