<?php
declare(strict_types=1);
namespace Apollo\Modules\Map;

final class MapRepository {
	private const TABLE = 'apollo_user_locations';

	public static function saveLocation( int $userId, float $lat, float $lng, string $address = '', string $city = '', string $state = '', string $country = '' ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->replace(
			$t,
			array(
				'user_id'    => $userId,
				'latitude'   => $lat,
				'longitude'  => $lng,
				'address'    => sanitize_text_field( $address ),
				'city'       => sanitize_text_field( $city ),
				'state'      => sanitize_text_field( $state ),
				'country'    => sanitize_text_field( $country ),
				'geohash'    => self::encodeGeohash( $lat, $lng ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s' )
		) !== false;
	}

	public static function getLocation( int $userId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE user_id=%d", $userId ), ARRAY_A );
		return $row ?: null;
	}

	public static function deleteLocation( int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->delete( $t, array( 'user_id' => $userId ), array( '%d' ) ) !== false;
	}

	public static function getNearbyUsers( float $lat, float $lng, float $radiusKm = 50, int $limit = 50, int $excludeUserId = 0 ): array {
		global $wpdb;
		$t       = $wpdb->prefix . self::TABLE;
		$exclude = $excludeUserId > 0 ? $wpdb->prepare( ' AND l.user_id!=%d', $excludeUserId ) : '';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,u.display_name,
			(6371*acos(cos(radians(%f))*cos(radians(l.latitude))*cos(radians(l.longitude)-radians(%f))+sin(radians(%f))*sin(radians(l.latitude)))) as distance
			FROM {$t} l
			JOIN {$wpdb->users} u ON l.user_id=u.ID
			WHERE l.latitude BETWEEN %f AND %f
			AND l.longitude BETWEEN %f AND %f
			{$exclude}
			HAVING distance<=%f
			ORDER BY distance ASC
			LIMIT %d",
				$lat,
				$lng,
				$lat,
				$lat - ( $radiusKm / 111 ),
				$lat + ( $radiusKm / 111 ),
				$lng - ( $radiusKm / ( 111 * cos( deg2rad( $lat ) ) ) ),
				$lng + ( $radiusKm / ( 111 * cos( deg2rad( $lat ) ) ) ),
				$radiusKm,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getNearbyFriends( int $userId, float $radiusKm = 50 ): array {
		global $wpdb;
		$loc = self::getLocation( $userId );
		if ( ! $loc ) {
			return array();}
		$t = $wpdb->prefix . self::TABLE;
		$c = $wpdb->prefix . 'apollo_connections';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,u.display_name,
			(6371*acos(cos(radians(%f))*cos(radians(l.latitude))*cos(radians(l.longitude)-radians(%f))+sin(radians(%f))*sin(radians(l.latitude)))) as distance
			FROM {$t} l
			JOIN {$wpdb->users} u ON l.user_id=u.ID
			JOIN {$c} conn ON l.user_id=conn.friend_id
			WHERE conn.user_id=%d AND conn.status='accepted'
			HAVING distance<=%f
			ORDER BY distance ASC",
				(float) $loc['latitude'],
				(float) $loc['longitude'],
				(float) $loc['latitude'],
				$userId,
				$radiusKm
			),
			ARRAY_A
		) ?? array();
	}

	public static function getNearbyGroups( float $lat, float $lng, float $radiusKm = 50, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_group_locations';
		$g = $wpdb->prefix . 'apollo_groups';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT gl.*,g.name,g.slug,
			(6371*acos(cos(radians(%f))*cos(radians(gl.latitude))*cos(radians(gl.longitude)-radians(%f))+sin(radians(%f))*sin(radians(gl.latitude)))) as distance
			FROM {$t} gl
			JOIN {$g} g ON gl.group_id=g.id
			WHERE g.status='active' AND g.privacy!='hidden'
			HAVING distance<=%f
			ORDER BY distance ASC
			LIMIT %d",
				$lat,
				$lng,
				$lat,
				$radiusKm,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getNearbyEvents( float $lat, float $lng, float $radiusKm = 100, int $limit = 20 ): array {
		global $wpdb;
		$e   = $wpdb->prefix . 'apollo_events';
		$now = current_time( 'mysql' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*,
			(6371*acos(cos(radians(%f))*cos(radians(e.latitude))*cos(radians(e.longitude)-radians(%f))+sin(radians(%f))*sin(radians(e.latitude)))) as distance
			FROM {$e} e
			WHERE e.latitude IS NOT NULL AND e.status='published' AND e.start_date>=%s
			HAVING distance<=%f
			ORDER BY distance ASC, e.start_date ASC
			LIMIT %d",
				$lat,
				$lng,
				$lat,
				$now,
				$radiusKm,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUsersByCity( string $city, int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,u.display_name FROM {$t} l JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.city=%s ORDER BY l.updated_at DESC LIMIT %d",
				$city,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getUsersByCountry( string $country, int $limit = 100 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*,u.display_name FROM {$t} l JOIN {$wpdb->users} u ON l.user_id=u.ID WHERE l.country=%s ORDER BY l.updated_at DESC LIMIT %d",
				$country,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getLocationStats(): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return array(
			'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ),
			'by_country' => $wpdb->get_results( "SELECT country,COUNT(*) as cnt FROM {$t} WHERE country!='' GROUP BY country ORDER BY cnt DESC LIMIT 20", ARRAY_A ),
			'by_city'    => $wpdb->get_results( "SELECT city,country,COUNT(*) as cnt FROM {$t} WHERE city!='' GROUP BY city,country ORDER BY cnt DESC LIMIT 20", ARRAY_A ),
		);
	}

	public static function geocode( string $address ): ?array {
		$apiKey = get_option( 'apollo_google_maps_api_key' );
		if ( ! $apiKey ) {
			return null;}
		$url      = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(
			array(
				'address' => $address,
				'key'     => $apiKey,
			)
		);
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return null;}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['results'][0] ) ) {
			return null;}
		$result     = $data['results'][0];
		$loc        = $result['geometry']['location'];
		$components = array();
		foreach ( $result['address_components'] as $c ) {
			if ( in_array( 'locality', $c['types'] ) ) {
				$components['city'] = $c['long_name'];}
			if ( in_array( 'administrative_area_level_1', $c['types'] ) ) {
				$components['state'] = $c['short_name'];}
			if ( in_array( 'country', $c['types'] ) ) {
				$components['country'] = $c['long_name'];}
		}
		return array(
			'lat'     => $loc['lat'],
			'lng'     => $loc['lng'],
			'address' => $result['formatted_address'],
		) + $components;
	}

	public static function reverseGeocode( float $lat, float $lng ): ?array {
		$apiKey = get_option( 'apollo_google_maps_api_key' );
		if ( ! $apiKey ) {
			return null;}
		$url      = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(
			array(
				'latlng' => "{$lat},{$lng}",
				'key'    => $apiKey,
			)
		);
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return null;}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['results'][0] ) ) {
			return null;}
		$result     = $data['results'][0];
		$components = array( 'address' => $result['formatted_address'] );
		foreach ( $result['address_components'] as $c ) {
			if ( in_array( 'locality', $c['types'] ) ) {
				$components['city'] = $c['long_name'];}
			if ( in_array( 'administrative_area_level_1', $c['types'] ) ) {
				$components['state'] = $c['short_name'];}
			if ( in_array( 'country', $c['types'] ) ) {
				$components['country'] = $c['long_name'];}
		}
		return $components;
	}

	public static function calculateDistance( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
		$earthRadius = 6371;
		$dLat        = deg2rad( $lat2 - $lat1 );
		$dLng        = deg2rad( $lng2 - $lng1 );
		$a           = sin( $dLat / 2 ) * sin( $dLat / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dLng / 2 ) * sin( $dLng / 2 );
		$c           = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
		return round( $earthRadius * $c, 2 );
	}

	private static function encodeGeohash( float $lat, float $lng, int $precision = 8 ): string {
		$chars  = '0123456789bcdefghjkmnpqrstuvwxyz';
		$minLat = -90;
		$maxLat = 90;
		$minLng = -180;
		$maxLng = 180;
		$hash   = '';
		$bit    = 0;
		$ch     = 0;
		$isLon  = true;
		while ( strlen( $hash ) < $precision ) {
			if ( $isLon ) {
				$mid = ( $minLng + $maxLng ) / 2;
				if ( $lng > $mid ) {
					$ch    |= ( 1 << ( 4 - $bit ) );
					$minLng = $mid;
				} else {
					$maxLng = $mid;}
			} else {
				$mid = ( $minLat + $maxLat ) / 2;
				if ( $lat > $mid ) {
					$ch    |= ( 1 << ( 4 - $bit ) );
					$minLat = $mid;
				} else {
					$maxLat = $mid;}
			}
			$isLon = ! $isLon;
			if ( ++$bit === 5 ) {
				$hash .= $chars[ $ch ];
				$bit   = 0;
				$ch    = 0;}
		}
		return $hash;
	}
}
