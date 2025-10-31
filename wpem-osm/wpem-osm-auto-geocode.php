<?php
/**
 * Auto-geocodificação de Local (lat/lng) via Nominatim ao salvar Local
 * Não exibe campos lat/lng no formulário
 */
add_action('save_post_local', function($post_id){
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  $address = trim(get_post_meta($post_id, '_local_address', true));
  $city = trim(get_post_meta($post_id, '_local_city', true));
  if(!$address && !$city) return;
  $full = $address . ($city ? ', ' . $city : '');
  if(!$full) return;
  $geo = wem_geocode($full);
  if($geo && !empty($geo['lat']) && !empty($geo['lng'])){
    update_post_meta($post_id, '_local_lat', $geo['lat']);
    update_post_meta($post_id, '_local_lng', $geo['lng']);
  }
});

// Map sem controles (Leaflet)
add_action('wp_enqueue_scripts', function(){
  wp_add_inline_script('leaflet', "L.Map.addInitHook(function(){this.zoomControl.remove();this.dragging.disable();this.touchZoom.disable();this.doubleClickZoom.disable();this.scrollWheelZoom.disable();this.boxZoom.disable();this.keyboard.disable();});");
});

// Função de geocodificação (Nominatim)
if(!function_exists('wem_geocode')){
function wem_geocode($address){
  $url = add_query_arg(['q'=>$address,'format'=>'json','limit'=>1],'https://nominatim.openstreetmap.org/search');
  $r = wp_remote_get($url, ['timeout'=>12,'headers'=>['User-Agent'=>'apollo::rio (admin@apollo.rio.br)']]);
  if(is_wp_error($r)) return null;
  $d = json_decode(wp_remote_retrieve_body($r), true);
  return !empty($d[0]) ? ['lat'=>$d[0]['lat'],'lng'=>$d[0]['lon']] : null;
}
}
