<?php
declare(strict_types=1);
namespace Apollo\Modules\Albums;

final class PhotoAlbumsRepository {
	private const TABLE  = 'apollo_photo_albums';
	private const PHOTOS = 'apollo_photos';

	public static function create( int $userId, string $title, string $desc = '', string $privacy = 'public' ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		$wpdb->insert(
			$t,
			array(
				'user_id'     => $userId,
				'title'       => sanitize_text_field( $title ),
				'description' => wp_kses_post( $desc ),
				'privacy'     => $privacy,
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function update( int $albumId, int $userId, array $data ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		if ( ! self::isOwner( $albumId, $userId ) ) {
			return false;}
		$upd = array();
		$fmt = array();
		if ( isset( $data['title'] ) ) {
			$upd['title'] = sanitize_text_field( $data['title'] );
			$fmt[]        = '%s';}
		if ( isset( $data['description'] ) ) {
			$upd['description'] = wp_kses_post( $data['description'] );
			$fmt[]              = '%s';}
		if ( isset( $data['privacy'] ) ) {
			$upd['privacy'] = $data['privacy'];
			$fmt[]          = '%s';}
		if ( isset( $data['cover_photo_id'] ) ) {
			$upd['cover_photo_id'] = (int) $data['cover_photo_id'];
			$fmt[]                 = '%d';}
		$upd['updated_at'] = current_time( 'mysql' );
		$fmt[]             = '%s';
		return $wpdb->update( $t, $upd, array( 'id' => $albumId ), $fmt, array( '%d' ) ) !== false;
	}

	public static function delete( int $albumId, int $userId ): bool {
		global $wpdb;
		if ( ! self::isOwner( $albumId, $userId ) ) {
			return false;}
		$t = $wpdb->prefix . self::TABLE;
		$p = $wpdb->prefix . self::PHOTOS;
		$wpdb->delete( $p, array( 'album_id' => $albumId ), array( '%d' ) );
		return $wpdb->delete( $t, array( 'id' => $albumId ), array( '%d' ) ) !== false;
	}

	public static function get( int $albumId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::TABLE;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $albumId ), ARRAY_A );
		return $row ?: null;
	}

	public static function getByUser( int $userId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*,(SELECT COUNT(*) FROM {$wpdb->prefix}apollo_photos p WHERE p.album_id=a.id) as photo_count FROM {$t} a WHERE a.user_id=%d ORDER BY a.updated_at DESC LIMIT %d OFFSET %d",
				$userId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function addPhoto( int $albumId, int $userId, int $attachmentId, string $caption = '' ): int {
		global $wpdb;
		if ( ! self::isOwner( $albumId, $userId ) && ! self::canContribute( $albumId, $userId ) ) {
			return 0;}
		$t     = $wpdb->prefix . self::PHOTOS;
		$order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sort_order) FROM {$t} WHERE album_id=%d", $albumId ) ) + 1;
		$wpdb->insert(
			$t,
			array(
				'album_id'      => $albumId,
				'user_id'       => $userId,
				'attachment_id' => $attachmentId,
				'caption'       => sanitize_text_field( $caption ),
				'sort_order'    => $order,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%d', '%s' )
		);
		$photoId = (int) $wpdb->insert_id;
		self::updateAlbumTimestamp( $albumId );
		if ( $photoId && ! self::hasCover( $albumId ) ) {
			self::setCover( $albumId, $photoId );}
		return $photoId;
	}

	public static function removePhoto( int $photoId, int $userId ): bool {
		global $wpdb;
		$t     = $wpdb->prefix . self::PHOTOS;
		$photo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $photoId ), ARRAY_A );
		if ( ! $photo ) {
			return false;}
		if ( (int) $photo['user_id'] !== $userId && ! self::isOwner( (int) $photo['album_id'], $userId ) ) {
			return false;}
		$wpdb->delete( $t, array( 'id' => $photoId ), array( '%d' ) );
		self::updateAlbumTimestamp( (int) $photo['album_id'] );
		return true;
	}

	public static function getPhotos( int $albumId, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::PHOTOS;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,u.display_name as uploader_name FROM {$t} p LEFT JOIN {$wpdb->users} u ON p.user_id=u.ID WHERE p.album_id=%d ORDER BY p.sort_order ASC LIMIT %d OFFSET %d",
				$albumId,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getPhoto( int $photoId ): ?array {
		global $wpdb;
		$t   = $wpdb->prefix . self::PHOTOS;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $photoId ), ARRAY_A );
		return $row ?: null;
	}

	public static function updatePhotoCaption( int $photoId, int $userId, string $caption ): bool {
		global $wpdb;
		$t     = $wpdb->prefix . self::PHOTOS;
		$photo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $photoId ), ARRAY_A );
		if ( ! $photo || (int) $photo['user_id'] !== $userId ) {
			return false;}
		return $wpdb->update( $t, array( 'caption' => sanitize_text_field( $caption ) ), array( 'id' => $photoId ), array( '%s' ), array( '%d' ) ) !== false;
	}

	public static function reorderPhotos( int $albumId, int $userId, array $photoIds ): bool {
		global $wpdb;
		if ( ! self::isOwner( $albumId, $userId ) ) {
			return false;}
		$t = $wpdb->prefix . self::PHOTOS;
		foreach ( $photoIds as $order => $photoId ) {
			$wpdb->update(
				$t,
				array( 'sort_order' => $order ),
				array(
					'id'       => (int) $photoId,
					'album_id' => $albumId,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}
		return true;
	}

	public static function setCover( int $albumId, int $photoId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return $wpdb->update( $t, array( 'cover_photo_id' => $photoId ), array( 'id' => $albumId ), array( '%d' ), array( '%d' ) ) !== false;
	}

	public static function likePhoto( int $photoId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_likes';
		if ( self::hasLikedPhoto( $photoId, $userId ) ) {
			return true;}
		return $wpdb->insert(
			$t,
			array(
				'photo_id'   => $photoId,
				'user_id'    => $userId,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		) !== false;
	}

	public static function unlikePhoto( int $photoId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_likes';
		return $wpdb->delete(
			$t,
			array(
				'photo_id' => $photoId,
				'user_id'  => $userId,
			),
			array( '%d', '%d' )
		) !== false;
	}

	public static function hasLikedPhoto( int $photoId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_likes';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE photo_id=%d AND user_id=%d", $photoId, $userId ) );
	}

	public static function getPhotoLikeCount( int $photoId ): int {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_likes';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE photo_id=%d", $photoId ) );
	}

	public static function commentOnPhoto( int $photoId, int $userId, string $comment ): int {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_comments';
		$wpdb->insert(
			$t,
			array(
				'photo_id'   => $photoId,
				'user_id'    => $userId,
				'content'    => wp_kses_post( $comment ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function getPhotoComments( int $photoId, int $limit = 50 ): array {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_photo_comments';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,u.display_name,um.meta_value as avatar FROM {$t} c JOIN {$wpdb->users} u ON c.user_id=u.ID LEFT JOIN {$wpdb->usermeta} um ON c.user_id=um.user_id AND um.meta_key='apollo_avatar' WHERE c.photo_id=%d ORDER BY c.created_at ASC LIMIT %d",
				$photoId,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getRecentPhotos( int $limit = 20, string $privacy = 'public' ): array {
		global $wpdb;
		$t = $wpdb->prefix . self::PHOTOS;
		$a = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,al.title as album_title,u.display_name FROM {$t} p JOIN {$a} al ON p.album_id=al.id JOIN {$wpdb->users} u ON p.user_id=u.ID WHERE al.privacy=%s ORDER BY p.created_at DESC LIMIT %d",
				$privacy,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getAlbumCount( int $userId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE user_id=%d", $userId ) );
	}

	public static function getPhotoCount( int $userId ): int {
		global $wpdb;
		$t = $wpdb->prefix . self::PHOTOS;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE user_id=%d", $userId ) );
	}

	public static function canView( int $albumId, int $viewerId ): bool {
		$album = self::get( $albumId );
		if ( ! $album ) {
			return false;}
		if ( (int) $album['user_id'] === $viewerId ) {
			return true;}
		return match ( $album['privacy'] ) {
			'public'=>true,
			'friends'=>self::areFriends( (int) $album['user_id'], $viewerId ),
			'private'=>false,
			default=>true
		};
	}

	private static function isOwner( int $albumId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$t} WHERE id=%d", $albumId ) ) === $userId;
	}

	private static function canContribute( int $albumId, int $userId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_album_contributors';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE album_id=%d AND user_id=%d", $albumId, $userId ) );
	}

	private static function hasCover( int $albumId ): bool {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT cover_photo_id FROM {$t} WHERE id=%d", $albumId ) );
	}

	private static function updateAlbumTimestamp( int $albumId ): void {
		global $wpdb;
		$t = $wpdb->prefix . self::TABLE;
		$wpdb->update( $t, array( 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $albumId ), array( '%s' ), array( '%d' ) );
	}

	private static function areFriends( int $u1, int $u2 ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_connections';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d AND status='accepted'", $u1, $u2 ) );
	}
}
