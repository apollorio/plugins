<?php
declare(strict_types=1);
namespace Apollo\Modules\Events;

final class EventsRepository {
	private const TABLE='apollo_events';
	private const ATTENDEES='apollo_event_attendees';
	private const INTERESTED='apollo_event_interested';

	public static function create(array $d): ?int {
		global $wpdb;
		$r=$wpdb->insert($wpdb->prefix.self::TABLE,[
			'title'=>$d['title'],
			'slug'=>sanitize_title($d['title']),
			'description'=>$d['description']??'',
			'venue'=>$d['venue']??'',
			'address'=>$d['address']??'',
			'city'=>$d['city']??'',
			'state'=>$d['state']??'',
			'country'=>$d['country']??'',
			'zip'=>$d['zip']??'',
			'latitude'=>$d['latitude']??null,
			'longitude'=>$d['longitude']??null,
			'start_date'=>$d['start_date'],
			'end_date'=>$d['end_date']??null,
			'timezone'=>$d['timezone']??'America/Sao_Paulo',
			'is_all_day'=>$d['is_all_day']??0,
			'is_recurring'=>$d['is_recurring']??0,
			'recurrence_rule'=>$d['recurrence_rule']??null,
			'max_attendees'=>$d['max_attendees']??null,
			'registration_deadline'=>$d['registration_deadline']??null,
			'price'=>$d['price']??0,
			'currency'=>$d['currency']??'BRL',
			'cover_image'=>$d['cover_image']??null,
			'status'=>$d['status']??'published',
			'visibility'=>$d['visibility']??'public',
			'organizer_id'=>$d['organizer_id'],
			'group_id'=>$d['group_id']??null,
			'category_id'=>$d['category_id']??null
		],['%s','%s','%s','%s','%s','%s','%s','%s','%s','%f','%f','%s','%s','%s','%d','%d','%s','%d','%s','%f','%s','%s','%s','%s','%d','%d','%d']);
		return $r?(int)$wpdb->insert_id:null;
	}

	public static function update(int $id, array $d): bool {
		global $wpdb;
		return (bool)$wpdb->update($wpdb->prefix.self::TABLE,$d,['id'=>$id]);
	}

	public static function delete(int $id): bool {
		global $wpdb;
		$wpdb->delete($wpdb->prefix.self::ATTENDEES,['event_id'=>$id],['%d']);
		$wpdb->delete($wpdb->prefix.self::INTERESTED,['event_id'=>$id],['%d']);
		return (bool)$wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$id],['%d']);
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$id),ARRAY_A);
	}

	public static function getBySlug(string $slug): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE slug=%s",$slug),ARRAY_A);
	}

	public static function getUpcoming(int $limit=10, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$now=current_time('mysql');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE start_date>=%s AND status='published' AND visibility='public' ORDER BY start_date ASC LIMIT %d OFFSET %d",
			$now,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getPast(int $limit=10, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$now=current_time('mysql');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE end_date<%s OR (end_date IS NULL AND start_date<%s) ORDER BY start_date DESC LIMIT %d OFFSET %d",
			$now,$now,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getByOrganizer(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE organizer_id=%d ORDER BY start_date DESC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getByGroup(int $groupId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE group_id=%d AND status='published' ORDER BY start_date DESC LIMIT %d",
			$groupId,$limit
		),ARRAY_A)??[];
	}

	public static function search(array $filters=[], int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$where=['status=%s'];$params=['published'];
		if(!empty($filters['q'])){$where[]='(title LIKE %s OR description LIKE %s)';$s='%'.$wpdb->esc_like($filters['q']).'%';$params[]=$s;$params[]=$s;}
		if(!empty($filters['city'])){$where[]='city=%s';$params[]=$filters['city'];}
		if(!empty($filters['country'])){$where[]='country=%s';$params[]=$filters['country'];}
		if(!empty($filters['category_id'])){$where[]='category_id=%d';$params[]=$filters['category_id'];}
		if(!empty($filters['start_after'])){$where[]='start_date>=%s';$params[]=$filters['start_after'];}
		if(!empty($filters['start_before'])){$where[]='start_date<=%s';$params[]=$filters['start_before'];}
		if(isset($filters['is_free'])&&$filters['is_free']){$where[]='(price=0 OR price IS NULL)';}
		$w=implode(' AND ',$where);$params[]=$limit;$params[]=$offset;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE {$w} ORDER BY start_date ASC LIMIT %d OFFSET %d",...$params),ARRAY_A)??[];
	}

	public static function attend(int $eventId, int $userId, string $status='going'): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::ATTENDEES;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE event_id=%d AND user_id=%d",$eventId,$userId));
		if($exists){return (bool)$wpdb->update($t,['status'=>$status,'updated_at'=>current_time('mysql')],['event_id'=>$eventId,'user_id'=>$userId]);}
		return (bool)$wpdb->insert($t,['event_id'=>$eventId,'user_id'=>$userId,'status'=>$status,'tickets'=>1],['%d','%d','%s','%d']);
	}

	public static function cancelAttendance(int $eventId, int $userId): bool {
		global $wpdb;
		return (bool)$wpdb->delete($wpdb->prefix.self::ATTENDEES,['event_id'=>$eventId,'user_id'=>$userId],['%d','%d']);
	}

	public static function getAttendees(int $eventId, string $status='going', int $limit=100): array {
		global $wpdb;
		$t=$wpdb->prefix.self::ATTENDEES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT a.*,u.display_name,u.user_email FROM {$t} a JOIN {$wpdb->users} u ON a.user_id=u.ID WHERE a.event_id=%d AND a.status=%s ORDER BY a.created_at DESC LIMIT %d",
			$eventId,$status,$limit
		),ARRAY_A)??[];
	}

	public static function getAttendeeCount(int $eventId): int {
		global $wpdb;
		$t=$wpdb->prefix.self::ATTENDEES;
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE event_id=%d AND status='going'",$eventId));
	}

	public static function isAttending(int $eventId, int $userId): ?string {
		global $wpdb;
		$t=$wpdb->prefix.self::ATTENDEES;
		return $wpdb->get_var($wpdb->prepare("SELECT status FROM {$t} WHERE event_id=%d AND user_id=%d",$eventId,$userId));
	}

	public static function markInterested(int $eventId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::INTERESTED;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE event_id=%d AND user_id=%d",$eventId,$userId));
		if($exists){return true;}
		return (bool)$wpdb->insert($t,['event_id'=>$eventId,'user_id'=>$userId],['%d','%d']);
	}

	public static function removeInterest(int $eventId, int $userId): bool {
		global $wpdb;
		return (bool)$wpdb->delete($wpdb->prefix.self::INTERESTED,['event_id'=>$eventId,'user_id'=>$userId],['%d','%d']);
	}

	public static function isInterested(int $eventId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::INTERESTED;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE event_id=%d AND user_id=%d",$eventId,$userId));
	}

	public static function getInterestedUsers(int $eventId, int $limit=100): array {
		global $wpdb;
		$t=$wpdb->prefix.self::INTERESTED;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT i.*,u.display_name FROM {$t} i JOIN {$wpdb->users} u ON i.user_id=u.ID WHERE i.event_id=%d ORDER BY i.created_at DESC LIMIT %d",
			$eventId,$limit
		),ARRAY_A)??[];
	}

	public static function getMyEvents(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;$a=$wpdb->prefix.self::ATTENDEES;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.*,a.status as attendance_status FROM {$a} a JOIN {$t} e ON a.event_id=e.id WHERE a.user_id=%d ORDER BY e.start_date ASC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getMyInterestedEvents(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;$i=$wpdb->prefix.self::INTERESTED;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.* FROM {$i} i JOIN {$t} e ON i.event_id=e.id WHERE i.user_id=%d ORDER BY e.start_date ASC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function getNearby(float $lat, float $lng, float $radiusKm=50, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;$now=current_time('mysql');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT *,(6371*acos(cos(radians(%f))*cos(radians(latitude))*cos(radians(longitude)-radians(%f))+sin(radians(%f))*sin(radians(latitude)))) AS distance FROM {$t} WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND start_date>=%s AND status='published' HAVING distance<=%f ORDER BY distance ASC LIMIT %d",
			$lat,$lng,$lat,$now,$radiusKm,$limit
		),ARRAY_A)??[];
	}

	public static function getCategories(): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_event_categories';
		return $wpdb->get_results("SELECT * FROM {$t} ORDER BY name ASC",ARRAY_A)??[];
	}

	public static function incrementViews(int $eventId): void {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$wpdb->query($wpdb->prepare("UPDATE {$t} SET views=views+1 WHERE id=%d",$eventId));
	}

	public static function countByStatus(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_results("SELECT status,COUNT(*) as count FROM {$t} GROUP BY status",ARRAY_A)??[];
	}
}
