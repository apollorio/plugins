<?php
/**
 * Classifieds listing view with Analytics tracking
 *
 * Template for displaying classified ads with tracking
 */

// Get current filters
$category_filter = $_GET['category']   ?? '';
$group_filter    = $_GET['group_type'] ?? '';

?>

<div class="apollo-classifieds-listing" data-page="classifieds">
	
	<div class="classifieds-header">
		<h1>Anúncios Classificados</h1>
		
		<div class="classifieds-actions">
			<button class="btn btn-primary apollo-create-ad-btn" data-category="<?php echo esc_attr($category_filter); ?>" data-group-type="<?php echo esc_attr($group_filter); ?>">
				Criar Anúncio
			</button>
		</div>
		
		<!-- Classifieds filters -->
		<div class="classifieds-filters">
			<select id="category-filter" class="apollo-filter" data-filter-type="category">
				<option value="">Todas as Categorias</option>
				<option value="veiculos" <?php echo $category_filter === 'veiculos' ? 'selected' : ''; ?>>Veículos</option>
				<option value="imoveis" <?php echo $category_filter  === 'imoveis' ? 'selected' : ''; ?>>Imóveis</option>
				<option value="servicos" <?php echo $category_filter === 'servicos' ? 'selected' : ''; ?>>Serviços</option>
				<option value="produtos" <?php echo $category_filter === 'produtos' ? 'selected' : ''; ?>>Produtos</option>
			</select>
			
			<select id="group-filter" class="apollo-filter" data-filter-type="group_type">
				<option value="">Todos os Grupos</option>
				<option value="comunidade" <?php echo $group_filter === 'comunidade' ? 'selected' : ''; ?>>Comunidade</option>
				<option value="nucleo" <?php echo $group_filter     === 'nucleo' ? 'selected' : ''; ?>>Núcleo</option>
				<option value="season" <?php echo $group_filter     === 'season' ? 'selected' : ''; ?>>Season</option>
			</select>
			
			<button class="btn apollo-apply-filters-btn">Aplicar Filtros</button>
		</div>
	</div>
	
	<div class="classifieds-grid" id="classifieds-grid">
		<!-- Ad cards will be loaded here -->
		<div class="ad-card" data-ad-id="1" data-category="veiculos" data-group-type="comunidade">
			<h3 class="ad-title">Honda Civic 2020</h3>
			<p class="ad-price">R$ 85.000</p>
			<p class="ad-description">Civic em excelente estado, único dono.</p>
			<div class="ad-meta">
				<span class="ad-category">Veículos</span>
				<span class="ad-group">Comunidade</span>
			</div>
			<button class="btn apollo-view-ad-btn" data-ad-id="1" data-category="veiculos" data-group-type="comunidade">
				Ver Anúncio
			</button>
		</div>
		
		<div class="ad-card" data-ad-id="2" data-category="servicos" data-group-type="nucleo">
			<h3 class="ad-title">Consultoria em WordPress</h3>
			<p class="ad-price">R$ 150/hora</p>
			<p class="ad-description">Desenvolvimento e consultoria em WordPress.</p>
			<div class="ad-meta">
				<span class="ad-category">Serviços</span>
				<span class="ad-group">Núcleo</span>
			</div>
			<button class="btn apollo-view-ad-btn" data-ad-id="2" data-category="servicos" data-group-type="nucleo">
				Ver Anúncio
			</button>
		</div>
	</div>
	
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	
	// Track ad creation attempts
	document.querySelector('.apollo-create-ad-btn')?.addEventListener('click', function() {
		var category = this.getAttribute('data-category') || 'geral';
		var groupType = this.getAttribute('data-group-type') || 'comunidade';
		
		if (typeof apolloAnalytics !== 'undefined') {
			apolloAnalytics.trackAdCreate(category, groupType);
		}
		
		// TODO: Check season validation
		// Simulate invalid season check
		var userSeason = '2024-2'; // This would come from user data
		var currentSeason = '2025-1';
		
		if (userSeason !== currentSeason) {
			if (typeof apolloAnalytics !== 'undefined') {
				apolloAnalytics.trackAdCreateInvalidSeason(currentSeason, userSeason);
			}
			
			alert('Sua season não está atualizada para criar anúncios.');
			return;
		}
		
		// TODO: Implement actual ad creation logic
		console.log('Creating ad for category:', category, 'group:', groupType);
	});
	
	// Track ad views
	document.querySelectorAll('.apollo-view-ad-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var adId = this.getAttribute('data-ad-id');
			var category = this.getAttribute('data-category');
			var groupType = this.getAttribute('data-group-type');
			
			if (typeof apolloAnalytics !== 'undefined') {
				apolloAnalytics.trackAdView(adId, category, groupType);
			}
			
			// TODO: Implement actual ad view navigation
			console.log('Viewing ad:', adId, category, groupType);
		});
	});
	
	// Track filter applications
	document.querySelector('.apollo-apply-filters-btn')?.addEventListener('click', function() {
		var categoryFilter = document.getElementById('category-filter').value;
		var groupFilter = document.getElementById('group-filter').value;
		
		// TODO: Implement actual filtering logic
		console.log('Applying filters:', { category: categoryFilter, group: groupFilter });
	});
	
	// Auto-track filter changes
	document.querySelectorAll('.apollo-filter').forEach(function(filter) {
		filter.addEventListener('change', function() {
			var filterType = this.getAttribute('data-filter-type');
			var filterValue = this.value;
			
			// TODO: Update URL parameters and reload results
			console.log('Filter changed:', filterType, filterValue);
		});
	});
	
});

// Simulate admin actions for testing
window.apolloClassifiedsAdmin = {
	
	publishAd: function(adId, category, groupType) {
		if (typeof apolloAnalytics !== 'undefined') {
			apolloAnalytics.trackAdPublish(category, groupType);
		}
		console.log('Ad published:', adId);
	},
	
	rejectAd: function(adId, category, reason) {
		if (typeof apolloAnalytics !== 'undefined') {
			apolloAnalytics.trackAdReject(category, reason);
		}
		console.log('Ad rejected:', adId, 'reason:', reason);
	}
	
};
</script>

<style>
.apollo-classifieds-listing {
	max-width: 1200px;
	margin: 0 auto;
	padding: 20px;
}

.classifieds-header {
	margin-bottom: 30px;
}

.classifieds-actions {
	margin: 15px 0;
}

.classifieds-filters {
	display: flex;
	gap: 15px;
	margin-top: 20px;
	flex-wrap: wrap;
}

.classifieds-filters select,
.classifieds-filters button {
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.classifieds-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.ad-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	background: #fff;
	transition: transform 0.2s ease;
}

.ad-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ad-title {
	margin: 0 0 10px 0;
	color: #1d2327;
}

.ad-price {
	font-size: 18px;
	font-weight: bold;
	color: #0073aa;
	margin: 5px 0;
}

.ad-description {
	color: #444;
	margin: 10px 0 15px 0;
}

.ad-meta {
	display: flex;
	gap: 10px;
	margin: 10px 0;
}

.ad-category,
.ad-group {
	background: #f0f0f0;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	color: #666;
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

.btn-primary {
	background: #00a32a;
}

.btn-primary:hover {
	background: #008a20;
}
</style>
