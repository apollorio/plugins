<?php
/**
 * Events listing view with Analytics tracking
 *
 * Template for displaying events with filtering and tracking
 */

// Get current season filter
$season_filter = $_GET['season'] ?? '';
$category_filter = $_GET['category'] ?? '';

?>

<div class="apollo-events-listing" data-page="events">
    
    <div class="events-header">
        <h1>Eventos Apollo</h1>
        
        <!-- Events filters -->
        <div class="events-filters">
            <select id="season-filter" class="apollo-filter" data-filter-type="season">
                <option value="">Todas as Seasons</option>
                <option value="2025-1" <?php selected($season_filter, '2025-1'); ?>>Season 2025-1</option>
                <option value="2025-2" <?php selected($season_filter, '2025-2'); ?>>Season 2025-2</option>
            </select>
            
            <select id="category-filter" class="apollo-filter" data-filter-type="category">
                <option value="">Todas as Categorias</option>
                <option value="workshop" <?php selected($category_filter, 'workshop'); ?>>Workshops</option>
                <option value="meetup" <?php selected($category_filter, 'meetup'); ?>>Meetups</option>
                <option value="conference" <?php selected($category_filter, 'conference'); ?>>Conferências</option>
            </select>
            
            <button class="btn apollo-apply-filters-btn">Aplicar Filtros</button>
        </div>
    </div>
    
    <div class="events-grid" id="events-grid">
        <!-- Event cards will be loaded here -->
        <div class="event-card" data-event-id="1" data-season="2025-1" data-group-type="comunidade">
            <h3 class="event-title">Workshop de Desenvolvimento</h3>
            <p class="event-date">15 de Novembro, 2025</p>
            <p class="event-description">Workshop sobre desenvolvimento de plugins WordPress.</p>
            <button class="btn apollo-view-event-btn" data-event-id="1" data-season="2025-1" data-group-type="comunidade">
                Ver Evento
            </button>
        </div>
        
        <div class="event-card" data-event-id="2" data-season="2025-1" data-group-type="nucleo">
            <h3 class="event-title">Meetup do Núcleo</h3>
            <p class="event-date">20 de Novembro, 2025</p>
            <p class="event-description">Encontro mensal do núcleo de desenvolvimento.</p>
            <button class="btn apollo-view-event-btn" data-event-id="2" data-season="2025-1" data-group-type="nucleo">
                Ver Evento
            </button>
        </div>
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Track filter applications
    document.querySelector('.apollo-apply-filters-btn')?.addEventListener('click', function() {
        var seasonFilter = document.getElementById('season-filter').value;
        var categoryFilter = document.getElementById('category-filter').value;
        
        if (typeof apolloAnalytics !== 'undefined') {
            if (seasonFilter) {
                apolloAnalytics.trackEventFilterApplied('season', seasonFilter);
            }
            if (categoryFilter) {
                apolloAnalytics.trackEventFilterApplied('category', categoryFilter);
            }
        }
        
        // TODO: Implement actual filtering logic
        console.log('Applying filters:', { season: seasonFilter, category: categoryFilter });
    });
    
    // Track event views
    document.querySelectorAll('.apollo-view-event-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var eventId = this.getAttribute('data-event-id');
            var seasonSlug = this.getAttribute('data-season');
            var groupType = this.getAttribute('data-group-type');
            
            if (typeof apolloAnalytics !== 'undefined') {
                apolloAnalytics.trackEventView(eventId, seasonSlug, groupType);
            }
            
            // TODO: Implement actual event view navigation
            console.log('Viewing event:', eventId, seasonSlug, groupType);
        });
    });
    
    // Auto-track filter changes (real-time)
    document.querySelectorAll('.apollo-filter').forEach(function(filter) {
        filter.addEventListener('change', function() {
            var filterType = this.getAttribute('data-filter-type');
            var filterValue = this.value;
            
            if (filterValue && typeof apolloAnalytics !== 'undefined') {
                apolloAnalytics.trackEventFilterApplied(filterType, filterValue);
            }
        });
    });
    
});
</script>

<style>
.apollo-events-listing {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.events-header {
    margin-bottom: 30px;
}

.events-filters {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.events-filters select,
.events-filters button {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.event-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    transition: transform 0.2s ease;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.event-title {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.event-date {
    color: #666;
    font-size: 14px;
    margin: 5px 0;
}

.event-description {
    color: #444;
    margin: 10px 0 15px 0;
}

.btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn:hover {
    background: #005a87;
}
</style>