<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$tax       = isset( $_GET['tax'] ) ? sanitize_key( $_GET['tax'] ) : 'event_sounds';
$uid       = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
$valid_tax = array( 'event_sounds', 'event_listing_category', 'event_listing_type', 'event_listing_tag' );
if ( ! in_array( $tax, $valid_tax, true ) ) {
	$tax = 'event_sounds';
}
$ranking         = class_exists( 'Apollo_Core\\Interesse_Ranking' ) ? \Apollo_Core\Interesse_Ranking::get_global_ranking( $tax, 50 ) : array();
$tax_labels      = array(
	'event_sounds'           => __( 'Sons', 'apollo-core' ),
	'event_listing_category' => __( 'Categorias', 'apollo-core' ),
	'event_listing_type'     => __( 'Tipos', 'apollo-core' ),
	'event_listing_tag'      => __( 'Tags', 'apollo-core' ),
);
$total_events    = $wpdb->get_var( "SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts} WHERE post_type='event_listing' AND post_status='publish'" );
$total_interests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_event_interested_users','_apollo_favorited_users')" );
?>
<div class="apollo-ranking-filters">
	<div class="filter-group">
		<label><?php esc_html_e( 'Taxonomia:', 'apollo-core' ); ?></label>
		<select id="tax-filter" onchange="location.href='<?php echo esc_url( remove_query_arg( array( 'tax', 'uid' ) ) ); ?>&tab=ranking&tax='+this.value">
			<?php foreach ( $tax_labels as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $tax, $k ); ?>><?php echo esc_html( $v ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
<div class="apollo-stat-cards" style="margin-bottom:20px">
	<div class="apollo-stat-card"><div class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div><div class="stat-value"><?php echo esc_html( number_format_i18n( (int) $total_events ) ); ?></div><div class="stat-label"><?php esc_html_e( 'Eventos', 'apollo-core' ); ?></div></div>
	<div class="apollo-stat-card"><div class="stat-icon"><span class="dashicons dashicons-heart"></span></div><div class="stat-value"><?php echo esc_html( number_format_i18n( (int) $total_interests ) ); ?></div><div class="stat-label"><?php esc_html_e( 'Interesses', 'apollo-core' ); ?></div></div>
	<div class="apollo-stat-card"><div class="stat-icon"><span class="dashicons dashicons-tag"></span></div><div class="stat-value"><?php echo esc_html( count( $ranking ) ); ?></div><div class="stat-label"><?php echo esc_html( $tax_labels[ $tax ] ); ?></div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
	<div class="apollo-chart-container">
		<h3><?php printf( esc_html__( 'Top %s por Interesse', 'apollo-core' ), esc_html( $tax_labels[ $tax ] ) ); ?></h3>
		<canvas id="rankingChart" height="300"></canvas>
	</div>
	<div class="apollo-chart-container">
		<h3><?php esc_html_e( 'Ranking Detalhado', 'apollo-core' ); ?></h3>
		<table class="apollo-data-table"><thead><tr><th>#</th><th><?php esc_html_e( 'Nome', 'apollo-core' ); ?></th><th><?php esc_html_e( 'Interesses', 'apollo-core' ); ?></th><th>%</th></tr></thead><tbody>
		<?php
		$max = $ranking[0]['count'] ?? 1;
		foreach ( $ranking as $i => $r ) :
			$pct = $max > 0 ? round( ( $r['count'] / $max ) * 100, 1 ) : 0;
			?>
		<tr><td><strong><?php echo( $i + 1 ); ?></strong></td><td><?php echo esc_html( $r['name'] ); ?></td><td><?php echo absint( $r['count'] ); ?></td><td><div style="background:#eee;border-radius:4px;height:8px;width:100px"><div style="background:linear-gradient(90deg,#9b59b6,#3498db);height:100%;width:<?php echo $pct; ?>%;border-radius:4px"></div></div></td></tr>
				<?php endforeach; ?>
		</tbody></table>
	</div>
</div>
<?php
wp_enqueue_script( 'apollo-vendor-chartjs' );
$labels = wp_json_encode( array_column( array_slice( $ranking, 0, 15 ), 'name' ) );
$data   = wp_json_encode( array_map( 'intval', array_column( array_slice( $ranking, 0, 15 ), 'count' ) ) );
?>
<script>
document.addEventListener('DOMContentLoaded',function(){
	new Chart(document.getElementById('rankingChart'),{type:'bar',data:{labels:<?php echo $labels; ?>,datasets:[{label:'<?php echo esc_js( $tax_labels[ $tax ] ); ?>',data:<?php echo $data; ?>,backgroundColor:['#9b59b6','#3498db','#1abc9c','#e74c3c','#f39c12','#2ecc71','#e67e22','#34495e','#16a085','#d35400','#8e44ad','#27ae60','#c0392b','#2980b9','#f1c40f']}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}});
});
</script>
