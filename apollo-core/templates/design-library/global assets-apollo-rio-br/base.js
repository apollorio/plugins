/**
 * ============================================================================
 * APOLLO BASE.JS - Global JavaScript Utilities
 * ============================================================================
 * 
 * Utilitários JavaScript globais para todo o ecossistema Apollo.
 * Usado em conjunto com uni.css.
 * 
 * CDN: https://assets.apollo.rio.br/base.js
 * Versão: 4.2.0
 * Atualizado: 2025-12-01
 * 
 * MÓDULOS:
 *   1. Script Loader - Carregamento sequencial de scripts externos
 *   2. CSS Injection - Injeção segura do uni.css (FOUC prevention)
 *   3. Typewriter Effect - Efeito de digitação em inputs
 *   4. Layout Toggle - Alternar visualização grid/lista
 *   5. Dark Mode - Tema claro/escuro com persistência
 *   6. User Menu - Menu de usuário com toggle
 *   7. Real-time Clock - Relógio em tempo real
 *   8. Event Filtering - Filtro por categoria, busca e data
 *   9. Date Navigation - Navegação por mês
 *   10. Apollo Forms - Utilitários para formulários (combobox, chips, etc)
 *   11. Tooltips - Sistema de tooltips com data-ap-tooltip
 *   12. Data Attribute Handlers - Handlers genéricos para data-ap-*
 *   13. Dashboard Utilities - Tabs, Kanban, Stats animation
 *   14. Gantt Chart - Project timeline utilities
 *   15. Chat Utilities - Auto-resize textarea, Enter to send
 *   16. Statistics Utilities - Chart.js theming, filter pills, CSV export
 *   17. Classifieds Utilities - Currency converter, advert filtering
 * ============================================================================
 */

// === 1. SCRIPT LOADER ===
function loadScript(url) {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = url;
    script.type = 'text/javascript';
    script.async = false;
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
}

const scripts = [
 // 'https://assets.apollo.rio.br/clock.js',
  'https://assets.apollo.rio.br/script.js'
];

(async () => {
  try {
    for (const url of scripts) await loadScript(url);
  } catch (err) {
    console.error('Erro ao carregar scripts externos:', err);
  }
})();

// ==== ÚNICO DOMContentLoaded ====
document.addEventListener("DOMContentLoaded", function () {

  // === INJEÇÃO SEGURA DO uni.css (zero FOUC) ===
  fetch('https://assets.apollo.rio.br/uni.css', { cache: 'no-cache' })
    .then(r => {
      if (!r.ok) throw new Error('CSS falhou');
      return r.text();
    })
    .then(cssText => {
      const styleEl = document.createElement('style');
      styleEl.textContent = cssText;
      styleEl.id = 'inlined-uni-css';

      if (!document.getElementById('inlined-uni-css')) {
        const header = document.getElementsByTagName('header')[0];
        header ? header.parentNode.insertBefore(styleEl, header.nextSibling) : document.head.appendChild(styleEl);
      }

      // Só remove o link externo DEPOIS que o style já está aplicado
      const externalLink = document.querySelector('link[rel="stylesheet"][href="https://assets.apollo.rio.br/uni.css"]');
      if (externalLink) externalLink.remove();
    })
    .catch(err => console.error('Erro ao injetar uni.css:', err));

  // === TYPEWRITER (ainda mais natural) ===
  const input = document.getElementById("eventSearchInput");
  if (input) {
    const words = ["Qual a boa do FDS?!", "Best party near me in Rio", "O que tem hoje?!", "All day, all night..", "La gente es muy loca!!"];
    let wordIndex = 0, charIndex = 0, typing = true, textSoFar = "";
    const baseTypeSpeed = 195, typeVariation = 110, eraseSpeed = 90, pauseTime = 3200, cursorBlinkSpeed = 530;

    const cursor = "|";
    const thin  = "\u200A"; // espaço fino invisível
    const setPH = (text, showCursor) => input.placeholder = text + (showCursor ? cursor : thin);

    let blinkOn = true;
    const blinkInterval = setInterval(() => {
      if (!typing && charIndex === words[wordIndex].length) {
        blinkOn = !blinkOn;
        setPH(textSoFar, blinkOn);
      }
    }, cursorBlinkSpeed);

    function type() {
      const current = words[wordIndex];

      if (typing) {
        if (charIndex < current.length) {
          charIndex++;
          textSoFar = current.slice(0, charIndex);
          setPH(textSoFar, true);
          setTimeout(type, baseTypeSpeed + Math.random() * typeVariation);
        } else {
          setTimeout(() => { typing = false; type(); }, pauseTime);
        }
      } else {
        if (charIndex > 0) {
          charIndex--;
          textSoFar = current.slice(0, charIndex);
          setPH(textSoFar, true);
          setTimeout(type, eraseSpeed + Math.random() * 50);
        } else {
          wordIndex = (wordIndex + 1) % words.length;
          typing = true;
          setTimeout(type, 400);
        }
      }
    }

    setPH("", true);
    setTimeout(type, 800);

    // Limpa placeholder ao focar
    input.addEventListener('focus', () => input.placeholder = '');
    input.addEventListener('blur', () => { if (!input.value) setPH(textSoFar || words[0], true); });
  }

  // === TOGGLE LAYOUT (com animação sutil) ===
  window.toggleLayout = function(el) {
    if (!el) return;
    const listings = document.querySelector('.event_listings');
    if (!listings) return;

    const isList = el.classList.contains('as-list');
    el.classList.toggle('as-list', !isList);
    el.classList.toggle('as-box', isList);
    el.setAttribute('aria-pressed', !isList);
    el.title = isList ? 'Visualizar em cartões' : 'Visualizar em lista';

    el.innerHTML = isList
      ? '<i class="ri-building-3-fill"></i><span class="visually-hidden">Cards</span>'
      : '<i class="ri-list-check-2"></i><span class="visually-hidden">Lista</span>';

    listings.classList.toggle('list-view', !isList);
    el.classList.toggle('wpem-active-layout', true);

    // Animação suave no ícone
    el.style.transform = 'scale(0.9)';
    setTimeout(() => el.style.transform = '', 150);
  };

  // === ESTADO GLOBAL ===
  const darkModeToggle = document.getElementById('darkModeToggle');
  const userMenuTrigger = document.getElementById('userMenuTrigger');
  const agoraH = document.getElementById('agoraH');
  const dateDisplay = document.getElementById('dateDisplay');
  const datePrev = document.getElementById('datePrev');
  const dateNext = document.getElementById('dateNext');
  const categoryButtons = document.querySelectorAll('.event-category');
  const searchInput = document.getElementById('eventSearchInput');
  const searchForm = document.getElementById('eventSearchForm');
  const allEvents = document.querySelectorAll('.event_listing');

  let activeCategory = 'all';
  let searchQuery = '';
  let displayDate = new Date();
  displayDate.setHours(0,0,0,0);
  displayDate.setDate(1); // primeiro dia do mês

  const monthShortNames = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
  const monthMap = { jan:0, fev:1, mar:2, abr:3, mai:4, jun:5, jul:6, ago:7, set:8, out:9, nov:10, dez:11 };

  // === DARK MODE ===
  if (darkModeToggle) {
    if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
    darkModeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    });
  }

  // === USER MENU ===
  if (userMenuTrigger) {
    const userMenu = userMenuTrigger.parentElement;
    userMenuTrigger.addEventListener('click', e => { e.stopPropagation(); userMenu.classList.toggle('open'); });
    document.addEventListener('click', e => { if (!userMenu.contains(e.target)) userMenu.classList.remove('open'); });
  }

  // === RELÓGIO ===
  if (agoraH) {
    const updateTime = () => {
      agoraH.textContent = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    };
    setInterval(updateTime, 1000);
    updateTime();
  }

  // === FILTRO COM DEBOUNCE + FADE ===
  function filterEvents() {
    const displayMonth = dateDisplay ? displayDate.getMonth() : null;

    allEvents.forEach(event => {
      const category = event.dataset.category || 'all';
      const monthStr = event.dataset.monthStr;
      const eventMonth = monthMap[monthStr];

      const showByDate = !dateDisplay || eventMonth === displayMonth;
      const showByCategory = activeCategory === 'all' || category === activeCategory;
      const showBySearch = !searchQuery || event.textContent.toLowerCase().includes(searchQuery);

      const show = showByDate && showByCategory && showBySearch;

      event.classList.toggle('hidden', !show);
      if (show) event.style.display = ''; // reseta para o valor do CSS (grid/flex/block)
    });
  }

  // Debounce na busca
  let searchTimeout;
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        searchQuery = searchInput.value.toLowerCase().trim();
        filterEvents();
      }, 150);
    });
  }
  if (searchForm) searchForm.addEventListener('submit', e => e.preventDefault());

  // === NAVEGAÇÃO DE MÊS ===
  function updateDatepicker() {
    if (!dateDisplay) return;
    const m = displayDate.getMonth();
    const y = displayDate.getFullYear().toString().slice(-2);
    dateDisplay.textContent = `${monthShortNames[m]} '${y}`;
    filterEvents();
  }

  if (datePrev) datePrev.addEventListener('click', () => { displayDate.setMonth(displayDate.getMonth() - 1); updateDatepicker(); });
  if (dateNext) dateNext.addEventListener('click', () => { displayDate.setMonth(displayDate.getMonth() + 1); updateDatepicker(); });

  // === CATEGORIAS ===
  categoryButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      categoryButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      activeCategory = btn.dataset.slug || 'all';
      filterEvents();
    });
  });

  // Botão "Todos" inicia ativo
  document.querySelector('.event-category[data-slug="all"]')?.classList.add('active');

  // === FADE DO "All/Todo/Tutto..." ===
  const xxall = document.getElementById('xxall');
  if (xxall) {
    const words = ['All','Todo','Tutto','Alle','Todos','Tous'];
    let i = 0;
    xxall.textContent = words[0];

    function fade(from, to, duration = 300) {
      const start = performance.now();
      return new Promise(resolve => {
        function step(t) {
          const p = Math.min((t - start) / duration, 1);
          xxall.style.opacity = from + (to - from) * p;
          if (p < 1) requestAnimationFrame(step);
          else resolve();
        }
        requestAnimationFrame(step);
      });
    }

    (async function cycle() {
      await new Promise(r => setTimeout(r, 2800));
      while (true) {
        await fade(1, 0, 250);
        i = (i + 1) % words.length;
        xxall.textContent = words[i];
        await fade(0, 1, 250);
        await new Promise(r => setTimeout(r, 2800));
      }
    })();
  }

  // Inicialização
  updateDatepicker(); // começa no mês atual
  filterEvents();

  // === 10. APOLLO FORMS UTILITIES ===
  // Combobox: Open/Close/Filter
  window.apolloCombobox = {
    open(input) {
      const dropdown = input.nextElementSibling;
      if (dropdown) dropdown.classList.add('active');
    },
    close(input) {
      setTimeout(() => {
        const dropdown = input.nextElementSibling;
        if (dropdown) dropdown.classList.remove('active');
      }, 200);
    },
    filter(input) {
      const filter = input.value.toLowerCase();
      const dropdown = input.nextElementSibling;
      if (!dropdown) return;
      const options = dropdown.querySelectorAll('.ap-combobox-option, .combobox-option');
      options.forEach(opt => {
        const text = opt.textContent.toLowerCase();
        opt.classList.toggle('hidden', !text.includes(filter));
      });
    }
  };

  // Chips: Add/Remove
  window.apolloChips = {
    add(container, value, onRemove) {
      if (!container) return;
      // Check if already exists
      const existing = Array.from(container.children).find(c => c.dataset.value === value);
      if (existing) return;
      
      const chip = document.createElement('div');
      chip.className = 'ap-chip active';
      chip.dataset.value = value;
      chip.innerHTML = `${value} <span class="ap-chip-remove" onclick="apolloChips.remove(this)">×</span>`;
      container.appendChild(chip);
      
      if (typeof onRemove === 'function') {
        chip.querySelector('.ap-chip-remove').addEventListener('click', () => onRemove(value));
      }
    },
    remove(el) {
      if (el.closest('.ap-chip')) el.closest('.ap-chip').remove();
    }
  };

  // File Preview
  window.apolloUpload = {
    preview(input, previewImg, placeholder) {
      const file = input.files?.[0];
      if (!file || !previewImg) return;
      
      const reader = new FileReader();
      reader.onloadend = () => {
        previewImg.src = reader.result;
        previewImg.style.display = 'block';
        if (placeholder) placeholder.style.opacity = '0';
      };
      reader.readAsDataURL(file);
    },
    
    gallery(input, index, prefix = 'gal') {
      const file = input.files?.[0];
      const img = document.getElementById(prefix + index);
      if (!file || !img) return;
      
      const reader = new FileReader();
      reader.onloadend = () => {
        img.src = reader.result;
        img.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  };

  // Rich Editor Simple Formatting
  window.apolloEditor = {
    format(textarea, cmd) {
      if (!textarea) return;
      const selection = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
      const before = textarea.value.substring(0, textarea.selectionStart);
      const after = textarea.value.substring(textarea.selectionEnd);
      
      let formatted = selection;
      switch(cmd) {
        case 'bold': formatted = `**${selection}**`; break;
        case 'italic': formatted = `_${selection}_`; break;
        case 'bullet': formatted = `\n• ${selection}`; break;
      }
      
      textarea.value = before + formatted + after;
      textarea.focus();
    }
  };

  // Modal
  window.apolloModal = {
    open(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
      }
    },
    close(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
      }
    }
  };

  // Slider with bubble
  window.apolloSlider = {
    init(slider, fill, bubble, formatValue) {
      if (!slider) return;
      
      const update = () => {
        const min = parseFloat(slider.min) || 0;
        const max = parseFloat(slider.max) || 100;
        const val = parseFloat(slider.value);
        const pct = ((val - min) / (max - min)) * 100;
        
        if (fill) fill.style.width = pct + '%';
        if (bubble) {
          bubble.style.left = pct + '%';
          bubble.textContent = typeof formatValue === 'function' ? formatValue(val) : val;
        }
      };
      
      slider.addEventListener('input', update);
      update(); // Initialize
    }
  };

  // === 11. TOOLTIPS (data-ap-tooltip) ===
  // Creates floating tooltips on hover for elements with data-ap-tooltip attribute
  (function initTooltips() {
    let tooltip = null;
    
    // Create tooltip element
    const createTooltip = () => {
      if (tooltip) return tooltip;
      tooltip = document.createElement('div');
      tooltip.className = 'ap-tooltip';
      tooltip.style.cssText = `
        position: fixed;
        z-index: 9999;
        padding: 6px 12px;
        background: rgba(15, 23, 42, 0.95);
        color: #fff;
        font-size: 12px;
        font-weight: 500;
        border-radius: 6px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s ease, transform 0.15s ease;
        transform: translateY(4px);
        max-width: 280px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        backdrop-filter: blur(4px);
      `;
      document.body.appendChild(tooltip);
      return tooltip;
    };
    
    // Position tooltip near element
    const positionTooltip = (el, tip) => {
      const rect = el.getBoundingClientRect();
      const tipRect = tip.getBoundingClientRect();
      
      let top = rect.top - tipRect.height - 8;
      let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
      
      // Adjust if off-screen
      if (top < 8) {
        top = rect.bottom + 8; // Show below instead
      }
      if (left < 8) left = 8;
      if (left + tipRect.width > window.innerWidth - 8) {
        left = window.innerWidth - tipRect.width - 8;
      }
      
      tip.style.top = top + 'px';
      tip.style.left = left + 'px';
    };
    
    // Event delegation for tooltips
    document.addEventListener('mouseenter', (e) => {
      const el = e.target.closest('[data-ap-tooltip]');
      if (!el) return;
      
      const text = el.getAttribute('data-ap-tooltip');
      if (!text) return;
      
      const tip = createTooltip();
      tip.textContent = text;
      
      // Show tooltip after brief delay
      requestAnimationFrame(() => {
        positionTooltip(el, tip);
        tip.style.opacity = '1';
        tip.style.transform = 'translateY(0)';
      });
    }, true);
    
    document.addEventListener('mouseleave', (e) => {
      const el = e.target.closest('[data-ap-tooltip]');
      if (!el || !tooltip) return;
      
      tooltip.style.opacity = '0';
      tooltip.style.transform = 'translateY(4px)';
    }, true);
    
    // Hide on scroll
    document.addEventListener('scroll', () => {
      if (tooltip) {
        tooltip.style.opacity = '0';
      }
    }, true);
  })();

  // === 12. DATA ATTRIBUTE HANDLERS ===
  // Generic handlers for common data-ap-* attributes
  
  // Toggle elements
  document.querySelectorAll('[data-ap-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-ap-toggle');
      const target = document.getElementById(targetId);
      if (target) {
        target.classList.toggle('active');
        target.classList.toggle('open');
        btn.setAttribute('aria-expanded', target.classList.contains('active'));
      }
    });
  });
  
  // Modal triggers
  document.querySelectorAll('[data-ap-modal-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.getAttribute('data-ap-modal-target');
      apolloModal.open(modalId);
    });
  });
  
  // Modal close buttons
  document.querySelectorAll('[data-ap-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.ap-modal-overlay, .modal-overlay');
      if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
      }
    });
  });

  // === 13. DASHBOARD UTILITIES ===
  /**
   * Apollo Dashboard Module
   * Handles tabs, kanban, sidebar, and dashboard-specific interactions
   */
  window.apolloDashboard = {
    // Tab Navigation
    initTabs: function(container = document) {
      const tabBtns = container.querySelectorAll('.ap-tab-btn');
      const tabContents = container.querySelectorAll('.ap-tab-content');
      
      tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          const tabId = btn.dataset.tab;
          
          // Update active states
          tabBtns.forEach(b => b.classList.remove('active'));
          tabContents.forEach(c => c.classList.remove('active'));
          
          btn.classList.add('active');
          const targetTab = container.querySelector(`#${tabId}`);
          if (targetTab) targetTab.classList.add('active');
          
          // Dispatch custom event
          container.dispatchEvent(new CustomEvent('ap-tab-change', { 
            detail: { tabId, button: btn } 
          }));
        });
      });
    },

    // Mobile Sidebar Toggle
    initSidebar: function() {
      const toggle = document.getElementById('mobileSidebarToggle');
      const sidebar = document.querySelector('.ap-dash-sidebar');
      
      if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
          sidebar.classList.toggle('open');
          toggle.setAttribute('aria-expanded', sidebar.classList.contains('open'));
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
          if (sidebar.classList.contains('open') && 
              !sidebar.contains(e.target) && 
              !toggle.contains(e.target)) {
            sidebar.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
          }
        });
      }
    },

    // Kanban Drag & Drop
    initKanban: function(container = document) {
      const taskCards = container.querySelectorAll('.ap-task-card[draggable="true"]');
      const kanbanColumns = container.querySelectorAll('.ap-kanban-tasks');
      
      taskCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
          card.classList.add('dragging');
          e.dataTransfer.setData('text/plain', card.dataset.taskId);
          e.dataTransfer.effectAllowed = 'move';
        });
        
        card.addEventListener('dragend', () => {
          card.classList.remove('dragging');
          kanbanColumns.forEach(col => col.classList.remove('drag-over'));
        });
      });
      
      kanbanColumns.forEach(column => {
        column.addEventListener('dragover', (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          column.classList.add('drag-over');
          
          // Find position to insert
          const afterElement = getDragAfterElement(column, e.clientY);
          const dragging = container.querySelector('.dragging');
          if (afterElement) {
            column.insertBefore(dragging, afterElement);
          } else if (dragging) {
            column.appendChild(dragging);
          }
        });
        
        column.addEventListener('dragleave', (e) => {
          if (!column.contains(e.relatedTarget)) {
            column.classList.remove('drag-over');
          }
        });
        
        column.addEventListener('drop', (e) => {
          e.preventDefault();
          column.classList.remove('drag-over');
          
          const taskId = e.dataTransfer.getData('text/plain');
          const newStatus = column.dataset.column || column.closest('.ap-kanban-column')?.dataset.column;
          
          // Dispatch custom event for AJAX handling
          container.dispatchEvent(new CustomEvent('ap-task-moved', {
            detail: { taskId, newStatus, column }
          }));
        });
      });
      
      // Helper function to find element after cursor
      function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.ap-task-card:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
          const box = child.getBoundingClientRect();
          const offset = y - box.top - box.height / 2;
          
          if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
          } else {
            return closest;
          }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
      }
    },

    // Stats Animation (count up effect)
    animateStats: function(container = document) {
      const statValues = container.querySelectorAll('.ap-stat-value');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const finalValue = el.textContent;
            const isNumeric = /^[\d,.%R$]+$/.test(finalValue.trim());
            
            if (isNumeric) {
              const numericPart = parseFloat(finalValue.replace(/[^\d.]/g, ''));
              const prefix = finalValue.match(/^[^\d]*/)?.[0] || '';
              const suffix = finalValue.match(/[^\d]*$/)?.[0] || '';
              
              let current = 0;
              const duration = 1000;
              const start = performance.now();
              
              const animate = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                current = Math.floor(numericPart * easeOutQuart(progress));
                
                el.textContent = prefix + current.toLocaleString('pt-BR') + suffix;
                
                if (progress < 1) {
                  requestAnimationFrame(animate);
                } else {
                  el.textContent = finalValue; // Ensure final value is exact
                }
              };
              
              requestAnimationFrame(animate);
            }
            
            observer.unobserve(el);
          }
        });
      }, { threshold: 0.5 });
      
      statValues.forEach(el => observer.observe(el));
      
      function easeOutQuart(x) {
        return 1 - Math.pow(1 - x, 4);
      }
    },

    // Progress Bar Animation
    animateProgressBars: function(container = document) {
      const progressBars = container.querySelectorAll('.ap-progress-value');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const width = el.style.width;
            el.style.width = '0%';
            
            requestAnimationFrame(() => {
              el.style.transition = 'width 0.8s ease-out';
              el.style.width = width;
            });
            
            observer.unobserve(el);
          }
        });
      }, { threshold: 0.2 });
      
      progressBars.forEach(el => observer.observe(el));
    },

    // Initialize all dashboard features
    init: function(container = document) {
      this.initTabs(container);
      this.initSidebar();
      this.initKanban(container);
      this.animateStats(container);
      this.animateProgressBars(container);
      
      console.log('[Apollo Dashboard] Initialized');
    }
  };
  
  // Auto-init if dashboard elements exist
  if (document.querySelector('.ap-dashboard')) {
    apolloDashboard.init();
  }

  // === 14. GANTT CHART UTILITIES ===
  window.apolloGantt = {
    init: function(container) {
      const toggles = container.querySelectorAll('.ap-gantt-toggle');
      
      toggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
          const project = toggle.closest('.ap-gantt-project');
          const tasks = project?.querySelector('.ap-gantt-tasks');
          
          if (tasks) {
            tasks.classList.toggle('collapsed');
            const icon = toggle.querySelector('i');
            if (icon) {
              icon.className = tasks.classList.contains('collapsed') 
                ? 'ri-arrow-right-s-line' 
                : 'ri-arrow-down-s-line';
            }
          }
        });
      });
    }
  };

  // Auto-init gantt if exists
  const ganttContainer = document.querySelector('.ap-gantt-container');
  if (ganttContainer) {
    apolloGantt.init(ganttContainer);
  }

  // === 15. CHAT UTILITIES ===
  window.apolloChat = {
    autoResize: function(textarea) {
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    },
    
    init: function(container) {
      const textareas = container.querySelectorAll('.ap-chat-input-wrapper textarea');
      
      textareas.forEach(textarea => {
        textarea.addEventListener('input', () => this.autoResize(textarea));
        
        // Send on Enter (without Shift)
        textarea.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const form = textarea.closest('form');
            const submitBtn = container.querySelector('.ap-chat-input .ap-btn-primary');
            
            if (form) form.submit();
            else if (submitBtn) submitBtn.click();
          }
        });
      });
    }
  };

  // Auto-init chat if exists
  const chatPanel = document.querySelector('.ap-chat-panel');
  if (chatPanel) {
    apolloChat.init(chatPanel);
  }

  // === 16. STATISTICS UTILITIES ===
  /**
   * Apollo Statistics Module
   * Handles data grids, charts, and statistics-specific interactions
   */
  window.apolloStats = {
    // Apollo Chart.js color palette
    colors: {
      primary: '#f97316',
      secondary: '#3b82f6',
      success: '#22c55e',
      warning: '#eab308',
      danger: '#ef4444',
      purple: '#a855f7',
      pink: '#ec4899',
      teal: '#14b8a6',
      indigo: '#6366f1',
      slate: '#64748b',
      palette: [
        'rgba(249, 115, 22, 0.85)',
        'rgba(59, 130, 246, 0.85)',
        'rgba(34, 197, 94, 0.85)',
        'rgba(168, 85, 247, 0.85)',
        'rgba(236, 72, 153, 0.85)',
        'rgba(20, 184, 166, 0.85)',
        'rgba(99, 102, 241, 0.85)',
        'rgba(234, 179, 8, 0.85)',
        'rgba(239, 68, 68, 0.85)',
        'rgba(100, 116, 139, 0.85)'
      ]
    },

    // Apply Apollo theme to Chart.js
    configureChartDefaults: function() {
      if (typeof Chart === 'undefined') return;
      
      Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
      Chart.defaults.font.size = 11;
      Chart.defaults.color = '#64748b';
      Chart.defaults.plugins.legend.labels.boxWidth = 12;
      Chart.defaults.plugins.legend.labels.padding = 8;
      
      // Check dark mode
      if (document.body.classList.contains('dark-mode')) {
        Chart.defaults.color = '#94a3b8';
      }
    },

    // Initialize filter pills
    initFilterPills: function(container, onFilter) {
      const pills = container.querySelectorAll('.ap-tab-pill');
      
      pills.forEach(pill => {
        pill.addEventListener('click', () => {
          pills.forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          
          const filterValue = pill.dataset.filter;
          if (typeof onFilter === 'function') {
            onFilter(filterValue, pill);
          }
          
          container.dispatchEvent(new CustomEvent('ap-filter-change', {
            detail: { filter: filterValue, element: pill }
          }));
        });
      });
    },

    // Animate mini stat values
    animateMiniStats: function(container = document) {
      const statValues = container.querySelectorAll('.ap-stat-mini-value');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const finalValue = el.textContent;
            const numericMatch = finalValue.match(/[\d,.]+/);
            
            if (numericMatch) {
              const numericPart = parseFloat(numericMatch[0].replace(',', '.'));
              const prefix = finalValue.substring(0, finalValue.indexOf(numericMatch[0]));
              const suffix = finalValue.substring(finalValue.indexOf(numericMatch[0]) + numericMatch[0].length);
              
              let current = 0;
              const duration = 800;
              const start = performance.now();
              
              const animate = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                current = numericPart * eased;
                
                // Format number
                let formatted;
                if (numericPart % 1 !== 0) {
                  formatted = current.toFixed(1);
                } else {
                  formatted = Math.floor(current).toString();
                }
                
                el.textContent = prefix + formatted + suffix;
                
                if (progress < 1) {
                  requestAnimationFrame(animate);
                } else {
                  el.textContent = finalValue;
                }
              };
              
              requestAnimationFrame(animate);
            }
            
            observer.unobserve(el);
          }
        });
      }, { threshold: 0.3 });
      
      statValues.forEach(el => observer.observe(el));
    },

    // Export data to CSV
    exportToCSV: function(data, filename = 'apollo-export') {
      if (!data || !data.length) return;
      
      const headers = Object.keys(data[0]);
      const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(h => {
          const val = row[h];
          return typeof val === 'string' && val.includes(',') 
            ? `"${val}"` 
            : val;
        }).join(','))
      ].join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `${filename}-${new Date().toISOString().split('T')[0]}.csv`;
      link.click();
    },

    // Initialize all statistics features
    init: function(container = document) {
      this.configureChartDefaults();
      this.animateMiniStats(container);
      
      // Auto-init filter pills
      const filterContainer = container.querySelector('.ap-grid-filters');
      if (filterContainer) {
        this.initFilterPills(filterContainer);
      }
      
      console.log('[Apollo Statistics] Initialized');
    }
  };
  
  // Auto-init if statistics elements exist
  if (document.querySelector('.ap-stats-dashboard')) {
    apolloStats.init();
  }

  // === 17. CLASSIFIEDS UTILITIES ===
  /**
   * Apollo Classifieds Module
   * Handles currency conversion, advert filtering, and marketplace interactions
   */
  window.apolloClassifieds = {
    // Currency converter state
    currency: {
      rate: 5.80, // Default USD to BRL fallback rate
      lastUpdate: null,
      
      // Fetch current exchange rate from API
      async fetchRate() {
        const apis = [
          'https://api.exchangerate.host/latest?base=USD&symbols=BRL',
          'https://api.frankfurter.app/latest?from=USD&to=BRL'
        ];
        
        for (const url of apis) {
          try {
            const res = await fetch(url, { cache: 'no-store' });
            const data = await res.json();
            
            if (data.rates?.BRL) {
              this.rate = data.rates.BRL;
              this.lastUpdate = new Date();
              return this.rate;
            }
          } catch (e) { continue; }
        }
        
        console.warn('[Apollo Currency] Using fallback rate:', this.rate);
        return this.rate;
      },
      
      // Convert USD to BRL
      usdToBrl(usd) {
        return (usd * this.rate).toFixed(2);
      },
      
      // Convert BRL to USD
      brlToUsd(brl) {
        return (brl / this.rate).toFixed(2);
      },
      
      // Format currency for display
      format(value, currency = 'BRL') {
        return new Intl.NumberFormat('pt-BR', {
          style: 'currency',
          currency: currency
        }).format(value);
      }
    },

    // Initialize currency widget
    initCurrencyWidget: function(container = document) {
      const toggle = container.querySelector('#currencyToggle, .ap-currency-toggle');
      const panel = container.querySelector('#currencyPanel, .ap-currency-panel');
      const closeBtn = container.querySelector('#currencyClose, .ap-currency-close');
      const swapBtn = container.querySelector('#currencySwap, .ap-currency-swap');
      const inputUSD = container.querySelector('#inputUSD');
      const inputBRL = container.querySelector('#inputBRL');
      
      if (toggle && panel) {
        toggle.addEventListener('click', () => panel.classList.toggle('open'));
        closeBtn?.addEventListener('click', () => panel.classList.remove('open'));
      }
      
      if (inputUSD && inputBRL) {
        inputUSD.addEventListener('input', () => {
          const usd = parseFloat(inputUSD.value) || 0;
          inputBRL.value = this.currency.usdToBrl(usd);
        });
        
        inputBRL.addEventListener('input', () => {
          const brl = parseFloat(inputBRL.value) || 0;
          inputUSD.value = this.currency.brlToUsd(brl);
        });
        
        swapBtn?.addEventListener('click', () => {
          const temp = inputUSD.value;
          inputUSD.value = inputBRL.value;
          inputBRL.value = temp;
        });
      }
    },

    // Initialize advert filtering
    initAdvertFilters: function(container = document) {
      const filterPills = container.querySelectorAll('.ap-filter-tabs .ap-tab-pill');
      const advertCards = container.querySelectorAll('.ap-advert-card');
      const searchInput = container.querySelector('#advertSearch');
      const sortSelect = container.querySelector('#advertSort');
      
      let currentFilter = 'all';
      let currentSearch = '';
      
      const filterAdverts = () => {
        advertCards.forEach(card => {
          const category = card.dataset.category;
          const text = card.textContent.toLowerCase();
          
          const matchesFilter = currentFilter === 'all' || category === currentFilter;
          const matchesSearch = !currentSearch || text.includes(currentSearch.toLowerCase());
          
          card.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });
      };
      
      filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
          filterPills.forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          currentFilter = pill.dataset.filter;
          filterAdverts();
        });
      });
      
      searchInput?.addEventListener('input', (e) => {
        currentSearch = e.target.value;
        filterAdverts();
      });
      
      sortSelect?.addEventListener('change', () => {
        const grid = container.querySelector('.ap-adverts-grid');
        if (!grid) return;
        
        const cards = [...grid.querySelectorAll('.ap-advert-card')];
        
        cards.sort((a, b) => {
          const priceA = parseInt(a.dataset.price) || 0;
          const priceB = parseInt(b.dataset.price) || 0;
          
          switch (sortSelect.value) {
            case 'price-low': return priceA - priceB;
            case 'price-high': return priceB - priceA;
            default: return 0;
          }
        });
        
        cards.forEach(card => grid.appendChild(card));
      });
    },

    // Initialize view toggle (grid/list)
    initViewToggle: function(container = document) {
      const viewButtons = container.querySelectorAll('[data-view]');
      const advertsGrid = container.querySelector('.ap-adverts-grid');
      
      viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          viewButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          
          const view = btn.dataset.view;
          advertsGrid?.classList.toggle('ap-adverts-list', view === 'list');
        });
      });
    },

    // Initialize all classifieds features
    init: async function(container = document) {
      // Fetch exchange rate
      await this.currency.fetchRate();
      
      // Initialize widgets
      this.initCurrencyWidget(container);
      this.initAdvertFilters(container);
      this.initViewToggle(container);
      
      // Update currency display
      const usdEl = container.querySelector('#usdValue');
      const brlEl = container.querySelector('#brlValue');
      if (usdEl) usdEl.textContent = '1.00';
      if (brlEl) brlEl.textContent = this.currency.rate.toFixed(2);
      
      // Auto-update rate every 5 minutes
      setInterval(async () => {
        await this.currency.fetchRate();
        if (brlEl) brlEl.textContent = this.currency.rate.toFixed(2);
      }, 300000);
      
      console.log('[Apollo Classifieds] Initialized');
    }
  };
  
  // Auto-init if classifieds elements exist
  if (document.querySelector('.ap-classifieds')) {
    apolloClassifieds.init();
  }

});