<?php
/**
 * Plugin Name: WPEM OSM
 * Description: Leaflet + OpenStreetMap para WP Event Manager
 */
add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
  wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
  wp_add_inline_style('leaflet', '#wem_map{height:320px;width:100%}');
});
function wem_render_osm_map($lat,$lng,$title=''){
  if(!$lat || !$lng) return '';
  $id = 'wem_map_'.uniqid();
  ob_start(); ?>
  <div id="<?php echo esc_attr($id);?>" class="wem-map" style="height:320px"></div>
  <script>
  (function(){
    var m = L.map('<?php echo $id;?>').setView([<?php echo $lat;?>,<?php echo $lng;?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(m);
    L.marker([<?php echo $lat;?>,<?php echo $lng;?>]).addTo(m).bindPopup(<?php echo json_encode($title);?>);
  })();
  </script>
  <?php return ob_get_clean();
}
add_action('single_event_listing_end', function(){
  $vid = (int) get_post_meta(get_the_ID(),'_local_id',true); // se você usa _local_id, troque aqui
  if(!$vid) return;
  $lat = get_post_meta($vid,'_local_lat',true);  // ajuste para _local_lat se aplicável
  $lng = get_post_meta($vid,'_local_lng',true);  // ajuste para _local_lng se aplicável
  echo wem_render_osm_map($lat,$lng,get_the_title($vid));
}, 9);

// Geocoding opcional por Nominatim (sem chave) quando criar Local:
function wem_geocode($address){
  $url = add_query_arg(['q'=>$address,'format'=>'json','limit'=>1],'https://nominatim.openstreetmap.org/search');
  $r = wp_remote_get($url, ['timeout'=>12,'headers'=>['User-Agent'=>'apollo::rio (admin@apollo.rio.br)']]);
  if(is_wp_error($r)) return null;
  $d = json_decode(wp_remote_retrieve_body($r), true);
  return !empty($d[0]) ? ['lat'=>$d[0]['lat'],'lng'=>$d[0]['lon']] : null;
}
// Se seus metakeys de Local já são _local_lat/_local_lng, ajuste as linhas indicadas.
