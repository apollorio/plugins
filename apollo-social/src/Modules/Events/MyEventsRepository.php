<?php
declare(strict_types=1);
namespace Apollo\Modules\Events;

final class MyEventsRepository {
	private const T_EVENTS='apollo_events';
	private const T_ATTENDEES='apollo_event_attendees';
	private const T_INTERESTED='apollo_event_interested';

	public static function getCreatedEvents(int $userId, string $timeframe='upcoming', int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$now=gmdate('Y-m-d H:i:s');
		$where=match($timeframe){
			'past'=>"start_date<'{$now}'",
			'upcoming'=>"start_date>='{$now}'",
			default=>'1=1'
		};
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.*,
			(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_ATTENDEES." WHERE event_id=e.id AND status='going') as going_count,
			(SELECT COUNT(*) FROM {$wpdb->prefix}".self::T_INTERESTED." WHERE event_id=e.id) as interested_count
			FROM {$t} e WHERE e.organizer_id=%d AND {$where} ORDER BY e.start_date DESC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getAttendingEvents(int $userId, string $timeframe='upcoming', int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$now=gmdate('Y-m-d H:i:s');
		$where=match($timeframe){
			'past'=>"e.start_date<'{$now}'",
			'upcoming'=>"e.start_date>='{$now}'",
			default=>'1=1'
		};
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.*,a.status as my_status,a.created_at as rsvp_date FROM {$t} e JOIN {$a} a ON e.id=a.event_id WHERE a.user_id=%d AND a.status='going' AND {$where} ORDER BY e.start_date ASC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getInterestedEvents(int $userId, string $timeframe='upcoming', int $limit=20, int $offset=0): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$i=$wpdb->prefix.self::T_INTERESTED;
		$now=gmdate('Y-m-d H:i:s');
		$where=match($timeframe){
			'past'=>"e.start_date<'{$now}'",
			'upcoming'=>"e.start_date>='{$now}'",
			default=>'1=1'
		};
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.*,i.created_at as interested_date FROM {$t} e JOIN {$i} i ON e.id=i.event_id WHERE i.user_id=%d AND {$where} ORDER BY e.start_date ASC LIMIT %d OFFSET %d",
			$userId,$limit,$offset
		),ARRAY_A)??[];
	}

	public static function getInvitedEvents(int $userId, int $limit=20): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$inv=$wpdb->prefix.'apollo_event_invites';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.*,ei.invited_by,ei.created_at as invited_at,u.display_name as inviter_name FROM {$t} e JOIN {$inv} ei ON e.id=ei.event_id JOIN {$wpdb->users} u ON ei.invited_by=u.ID WHERE ei.user_id=%d AND ei.status='pending' AND e.start_date>=NOW() ORDER BY e.start_date ASC LIMIT %d",
			$userId,$limit
		),ARRAY_A)??[];
	}

	public static function markInterested(int $userId, int $eventId): bool {
		global $wpdb;
		$i=$wpdb->prefix.self::T_INTERESTED;
		$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$i} WHERE user_id=%d AND event_id=%d",$userId,$eventId));
		if($exists)return true;
		return (bool)$wpdb->insert($i,[
			'user_id'=>$userId,
			'event_id'=>$eventId,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s']);
	}

	public static function removeInterest(int $userId, int $eventId): bool {
		global $wpdb;
		$i=$wpdb->prefix.self::T_INTERESTED;
		return (bool)$wpdb->delete($i,['user_id'=>$userId,'event_id'=>$eventId],['%d','%d']);
	}

	public static function rsvp(int $userId, int $eventId, string $status='going', int $guests=0): bool {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$existing=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$a} WHERE user_id=%d AND event_id=%d",$userId,$eventId));
		if($existing){
			return (bool)$wpdb->update($a,
				['status'=>$status,'guests'=>$guests,'updated_at'=>gmdate('Y-m-d H:i:s')],
				['id'=>$existing],
				['%s','%d','%s'],['%d']
			);
		}
		return (bool)$wpdb->insert($a,[
			'user_id'=>$userId,
			'event_id'=>$eventId,
			'status'=>$status,
			'guests'=>$guests,
			'created_at'=>gmdate('Y-m-d H:i:s')
		],['%d','%d','%s','%d','%s']);
	}

	public static function cancelRsvp(int $userId, int $eventId): bool {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		return (bool)$wpdb->delete($a,['user_id'=>$userId,'event_id'=>$eventId],['%d','%d']);
	}

	public static function getStats(int $userId): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$i=$wpdb->prefix.self::T_INTERESTED;
		$now=gmdate('Y-m-d H:i:s');
		return [
			'created_total'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE organizer_id=%d",$userId)),
			'created_upcoming'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE organizer_id=%d AND start_date>=%s",$userId,$now)),
			'attending_upcoming'=>(int)$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$a} a JOIN {$t} e ON a.event_id=e.id WHERE a.user_id=%d AND a.status='going' AND e.start_date>=%s",
				$userId,$now
			)),
			'interested'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE user_id=%d",$userId)),
			'pending_invites'=>(int)$wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_event_invites WHERE user_id=%d AND status='pending'",$userId
			))
		];
	}

	public static function isAttending(int $userId, int $eventId): bool {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$a} WHERE user_id=%d AND event_id=%d AND status='going'",$userId,$eventId));
	}

	public static function isInterested(int $userId, int $eventId): bool {
		global $wpdb;
		$i=$wpdb->prefix.self::T_INTERESTED;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$i} WHERE user_id=%d AND event_id=%d",$userId,$eventId));
	}

	public static function getUpcomingReminders(int $userId, int $hoursAhead=24): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$threshold=gmdate('Y-m-d H:i:s',strtotime("+{$hoursAhead} hours"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.* FROM {$t} e JOIN {$a} a ON e.id=a.event_id WHERE a.user_id=%d AND a.status='going' AND e.start_date BETWEEN NOW() AND %s ORDER BY e.start_date ASC",
			$userId,$threshold
		),ARRAY_A)??[];
	}

	public static function getFriendsAttending(int $userId, int $eventId, int $limit=10): array {
		global $wpdb;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$c=$wpdb->prefix.'apollo_connections';
		return $wpdb->get_results($wpdb->prepare(
			"SELECT u.ID,u.display_name FROM {$a} a JOIN {$wpdb->users} u ON a.user_id=u.ID JOIN {$c} c ON ((c.user_id=%d AND c.friend_id=a.user_id) OR (c.friend_id=%d AND c.user_id=a.user_id)) AND c.status='accepted' WHERE a.event_id=%d AND a.status='going' LIMIT %d",
			$userId,$userId,$eventId,$limit
		),ARRAY_A)??[];
	}

	public static function getCalendarEvents(int $userId, int $month, int $year): array {
		global $wpdb;
		$t=$wpdb->prefix.self::T_EVENTS;
		$a=$wpdb->prefix.self::T_ATTENDEES;
		$start=gmdate('Y-m-d 00:00:00',strtotime("{$year}-{$month}-01"));
		$end=gmdate('Y-m-t 23:59:59',strtotime("{$year}-{$month}-01"));
		return $wpdb->get_results($wpdb->prepare(
			"SELECT e.id,e.title,e.start_date,e.end_date,'attending' as type FROM {$t} e JOIN {$a} a ON e.id=a.event_id WHERE a.user_id=%d AND a.status='going' AND e.start_date BETWEEN %s AND %s
			UNION
			SELECT e.id,e.title,e.start_date,e.end_date,'created' as type FROM {$t} e WHERE e.organizer_id=%d AND e.start_date BETWEEN %s AND %s
			ORDER BY start_date ASC",
			$userId,$start,$end,$userId,$start,$end
		),ARRAY_A)??[];
	}

	public static function getDashboard(int $userId): array {
		return [
			'stats'=>self::getStats($userId),
			'upcoming'=>self::getAttendingEvents($userId,'upcoming',5),
			'interested'=>self::getInterestedEvents($userId,'upcoming',5),
			'invites'=>self::getInvitedEvents($userId,5),
			'reminders'=>self::getUpcomingReminders($userId,48),
			'my_events'=>self::getCreatedEvents($userId,'upcoming',5)
		];
	}
}
