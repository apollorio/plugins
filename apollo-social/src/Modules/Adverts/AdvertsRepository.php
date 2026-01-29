<?php
declare(strict_types=1);
namespace Apollo\Modules\Adverts;

final class AdvertsRepository {
	private const TABLE     = 'apollo_adverts';
	private const IMAGES    = 'apollo_advert_images';
	private const FAVORITES = 'apollo_advert_favorites';
	private const VIEWS     = 'apollo_advert_views';

	public static function create( array $d ): ?int {
		global $wpdb;
		$r = $wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'title'            => $d['title'],
				'slug'             => sanitize_title( $d['title'] ) . '-' . wp_generate_password( 6, false ),
				'description'      => $d['description'] ?? '',
				'price'            => $d['price'] ?? null,
				'price_type'       => $d['price_type'] ?? 'fixed',
				'currency'         => $d['currency'] ?? 'BRL',
				'category_id'      => $d['category_id'] ?? null,
				'subcategory_id'   => $d['subcategory_id'] ?? null,
				'condition'        => $d['condition'] ?? 'new',
				'city'             => $d['city'] ?? '',
				'state'            => $d['state'] ?? '',
				'country'          => $d['country'] ?? 'BR',
				'zip'              => $d['zip'] ?? '',
				'latitude'         => $d['latitude'] ?? null,
				'longitude'        => $d['longitude'] ?? null,
				'contact_phone'    => $d['contact_phone'] ?? '',
				'contact_whatsapp' => $d['contact_whatsapp'] ?? '',
				'contact_email'    => $d['contact_email'] ?? '',
				'user_id'          => $d['user_id'],
				'status'           => $d['status'] ?? 'pending',
				'featured'         => $d['featured'] ?? 0,
				'expires_at'       => $d['expires_at'] ?? null,
				'custom_fields'    => isset( $d['custom_fields'] ) ? json_encode( $d['custom_fields'] ) : null,
			),
			array( '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);
		return $r ? (int) $wpdb->insert_id : null;
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		if ( isset( $d['custom_fields'] ) ) {
			$d['custom_fields'] = json_encode( $d['custom_fields'] );}
		return (bool) $wpdb->update( $wpdb->prefix . self::TABLE, $d, array( 'id' => $id ) );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::IMAGES, array( 'advert_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . self::FAVORITES, array( 'advert_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . self::VIEWS, array( 'advert_id' => $id ), array( '%d' ) );
		return (bool) $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ), array( '%d' ) );
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $id ), ARRAY_A );
		if ( $row && $row['custom_fields'] ) {
			$row['custom_fields'] = json_decode( $row['custom_fields'], true );}
		return $row;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE slug=%s", $slug ), ARRAY_A );
		if ( $row && $row['custom_fields'] ) {
			$row['custom_fields'] = json_decode( $row['custom_fields'], true );}
		return $row;
	}

	public static function search( array $filters = array(), int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$where  = array( 'status=%s' );
		$params = array( 'published' );
		if ( ! empty( $filters['q'] ) ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$s        = '%' . $wpdb->esc_like( $filters['q'] ) . '%';
			$params[] = $s;
			$params[] = $s;}
		if ( ! empty( $filters['category_id'] ) ) {
			$where[]  = 'category_id=%d';
			$params[] = $filters['category_id'];}
		if ( ! empty( $filters['subcategory_id'] ) ) {
			$where[]  = 'subcategory_id=%d';
			$params[] = $filters['subcategory_id'];}
		if ( ! empty( $filters['city'] ) ) {
			$where[]  = 'city=%s';
			$params[] = $filters['city'];}
		if ( ! empty( $filters['state'] ) ) {
			$where[]  = 'state=%s';
			$params[] = $filters['state'];}
		if ( ! empty( $filters['country'] ) ) {
			$where[]  = 'country=%s';
			$params[] = $filters['country'];}
		if ( ! empty( $filters['condition'] ) ) {
			$where[]  = '`condition`=%s';
			$params[] = $filters['condition'];}
		if ( isset( $filters['price_min'] ) ) {
			$where[]  = 'price>=%f';
			$params[] = $filters['price_min'];}
		if ( isset( $filters['price_max'] ) ) {
			$where[]  = 'price<=%f';
			$params[] = $filters['price_max'];}
		if ( isset( $filters['user_id'] ) ) {
			$where[]  = 'user_id=%d';
			$params[] = $filters['user_id'];}
		if ( ! empty( $filters['featured'] ) ) {
			$where[] = 'featured=1';}
		$order = 'created_at DESC';
		if ( ! empty( $filters['sort'] ) ) {
			$order = match ( $filters['sort'] ) {
				'price_asc'=>'price ASC', 'price_desc'=>'price DESC', 'views'=>'views DESC',
				'newest'=>'created_at DESC', 'oldest'=>'created_at ASC', default=>'created_at DESC'
			};
		}
		$w        = \implode( ' AND ', $where );
		$params[] = $limit;
		$params[] = $offset;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$w} ORDER BY featured DESC,{$order} LIMIT %d OFFSET %d", ...$params ), ARRAY_A ) ?? array();
	}

	public static function count( array $filters = array() ): int {
		global $wpdb;
		$t      = $wpdb->prefix . self::TABLE;
		$where  = array( 'status=%s' );
		$params = array( 'published' );
		if ( ! empty( $filters['category_id'] ) ) {
			$where[]  = 'category_id=%d';
			$params[] = $filters['category_id'];}
		if ( ! empty( $filters['city'] ) ) {
			$where[]  = 'city=%s';
			$params[] = $filters['city'];}
		if ( isset( $filters['user_id'] ) ) {
			$where[]  = 'user_id=%d';
			$params[] = $filters['user_id'];}
		$w = \implode( ' AND ', $where );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$w}", ...$params ) );
	}

	public static function getByUser( int $userId, ?string $status = null, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( $status ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE user_id=%d AND status=%s ORDER BY created_at DESC LIMIT %d", $userId, $status, $limit ), ARRAY_A ) ?? array();
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE user_id=%d ORDER BY created_at DESC LIMIT %d", $userId, $limit ), ARRAY_A ) ?? array();
	}

	public static function addImage( int $advertId, string $url, int $order = 0, bool $isPrimary = false ): ?int {
		global $wpdb;
		if ( $isPrimary ) {
			$wpdb->update( $wpdb->prefix . self::IMAGES, array( 'is_primary' => 0 ), array( 'advert_id' => $advertId ) );}
		$r = $wpdb->insert(
			$wpdb->prefix . self::IMAGES,
			array(
				'advert_id'  => $advertId,
				'image_url'  => $url,
				'sort_order' => $order,
				'is_primary' => $isPrimary ? 1 : 0,
			),
			array( '%d', '%s', '%d', '%d' )
		);
		return $r ? (int) $wpdb->insert_id : null;
	}

	public static function getImages( int $advertId ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::IMAGES;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE advert_id=%d ORDER BY is_primary DESC,sort_order ASC", $advertId ), ARRAY_A ) ?? array();
	}

	public static function deleteImage( int $imageId ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $wpdb->prefix . self::IMAGES, array( 'id' => $imageId ), array( '%d' ) );
	}

	public static function addFavorite( int $advertId, int $userId ): bool {
		global $wpdb;
		$t      = $wpdb->prefix . self::FAVORITES;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE advert_id=%d AND user_id=%d", $advertId, $userId ) );
		if ( $exists ) {
			return true;}
		return (bool) $wpdb->insert(
			$t,
			array(
				'advert_id' => $advertId,
				'user_id'   => $userId,
			),
			array( '%d', '%d' )
		);
	}

	public static function removeFavorite( int $advertId, int $userId ): bool {
		global $wpdb;
		return (bool) $wpdb->delete(
			$wpdb->prefix . self::FAVORITES,
			array(
				'advert_id' => $advertId,
				'user_id'   => $userId,
			),
			array( '%d', '%d' )
		);
	}

	public static function isFavorite( int $advertId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::FAVORITES;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE advert_id=%d AND user_id=%d", $advertId, $userId ) );
	}

	public static function getUserFavorites( int $userId, int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		$f = $wpdb->prefix . self::FAVORITES;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.* FROM {$f} f JOIN {$t} a ON f.advert_id=a.id WHERE f.user_id=%d AND a.status='published' ORDER BY f.created_at DESC LIMIT %d",
				$userId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function recordView( int $advertId, ?int $userId = null, string $ip = '' ): void {
		global $wpdb;
		$t     = $wpdb->prefix . self::VIEWS;
		$today = current_time( 'Y-m-d' );
		if ( $userId ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE advert_id=%d AND user_id=%d AND DATE(viewed_at)=%s", $advertId, $userId, $today ) );
			if ( $exists ) {
				return;}
		} else {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE advert_id=%d AND ip_address=%s AND DATE(viewed_at)=%s", $advertId, $ip, $today ) );
			if ( $exists ) {
				return;}
		}
		$wpdb->insert(
			$t,
			array(
				'advert_id'  => $advertId,
				'user_id'    => $userId,
				'ip_address' => $ip,
			),
			array( '%d', '%d', '%s' )
		);
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}" . self::TABLE . ' SET views=views+1 WHERE id=%d', $advertId ) );
	}

	public static function getCategories(): array {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_advert_categories';
		return $wpdb->get_results( "SELECT * FROM {$t} ORDER BY sort_order ASC,name ASC", ARRAY_A ) ?? array();
	}

	public static function getCategoryTree(): array {
		global $wpdb;
		$t    = $wpdb->prefix . 'apollo_advert_categories';
		$all  = $wpdb->get_results( "SELECT * FROM {$t} ORDER BY parent_id,sort_order,name", ARRAY_A ) ?? array();
		$tree = array();
		$map  = array();
		foreach ( $all as &$c ) {
			$c['children']   = array();
			$map[ $c['id'] ] =&$c;}
		foreach ( $all as &$c ) {
			if ( $c['parent_id'] && isset( $map[ $c['parent_id'] ] ) ) {
				$map[ $c['parent_id'] ]['children'][] =&$c;
			} else {
				$tree[] =&$c;}
		}
		return $tree;
	}

	public static function getNearby( float $lat, float $lng, float $radiusKm = 25, int $limit = 20 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *,(6371*acos(cos(radians(%f))*cos(radians(latitude))*cos(radians(longitude)-radians(%f))+sin(radians(%f))*sin(radians(latitude)))) AS distance FROM {$t} WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status='published' HAVING distance<=%f ORDER BY featured DESC,distance ASC LIMIT %d",
				$lat,
				$lng,
				$lat,
				$radiusKm,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function approve( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'status'      => 'published',
				'approved_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id )
		);
	}

	public static function reject( int $id, string $reason = '' ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'status'           => 'rejected',
				'rejection_reason' => $reason,
			),
			array( 'id' => $id )
		);
	}

	public static function setFeatured( int $id, bool $featured, ?string $until = null ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'featured'       => $featured ? 1 : 0,
				'featured_until' => $until,
			),
			array( 'id' => $id )
		);
	}

	public static function expireOld(): int {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$now = current_time( 'mysql' );
		return (int) $wpdb->query( $wpdb->prepare( "UPDATE {$t} SET status='expired' WHERE status='published' AND expires_at IS NOT NULL AND expires_at<%s", $now ) );
	}

	public static function renew( int $id, int $days = 30 ): bool {
		global $wpdb;
		$t         = $wpdb->prefix . self::TABLE;
		$newExpiry = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );
		return (bool) $wpdb->update(
			$t,
			array(
				'status'     => 'published',
				'expires_at' => $newExpiry,
			),
			array( 'id' => $id )
		);
	}

	public static function countByStatus(): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results( "SELECT status,COUNT(*) as count FROM {$t} GROUP BY status", ARRAY_A ) ?? array();
	}

	public static function getPending( int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE status='pending' ORDER BY created_at ASC LIMIT %d", $limit ), ARRAY_A ) ?? array();
	}
}
