<?php
declare(strict_types=1);
namespace Apollo\Modules\Navigation;

final class DynamicMenuRepository {

	public static function getMemberMenuItems(int $userId): array {
		$items=[];
		$items[]=self::buildItem('profile','Meu Perfil',home_url('/members/me/'),'user');
		$items[]=self::buildItem('activity','Minha Atividade',home_url('/members/me/activity/'),'activity');
		$friendCount=self::getFriendCount($userId);
		$items[]=self::buildItem('friends','Meus Amigos',home_url('/members/me/friends/'),'users',$friendCount);
		$groupCount=self::getGroupCount($userId);
		$items[]=self::buildItem('groups','Meus Grupos',home_url('/members/me/groups/'),'users',$groupCount);
		$messageCount=self::getUnreadMessageCount($userId);
		$items[]=self::buildItem('messages','Mensagens',home_url('/members/me/messages/'),'mail',$messageCount);
		$notifCount=self::getNotificationCount($userId);
		$items[]=self::buildItem('notifications','Notificações',home_url('/members/me/notifications/'),'bell',$notifCount);
		$inviteCount=self::getPendingInvitesCount($userId);
		if($inviteCount>0){
			$items[]=self::buildItem('invites','Convites',home_url('/members/me/invites/'),'user-plus',$inviteCount);
		}
		$items[]=self::buildItem('settings','Configurações',home_url('/members/me/settings/'),'settings');
		return apply_filters('apollo_member_menu_items',$items,$userId);
	}

	public static function getProfileTabs(int $profileId, int $viewerId): array {
		$tabs=[];
		$tabs[]=self::buildTab('activity','Atividade',home_url("/members/{$profileId}/activity/"),true);
		if(self::canView($viewerId,$profileId,'profile')){
			$tabs[]=self::buildTab('profile','Perfil',home_url("/members/{$profileId}/profile/"));
		}
		if(self::canView($viewerId,$profileId,'friends')){
			$count=self::getFriendCount($profileId);
			$tabs[]=self::buildTab('friends','Amigos',home_url("/members/{$profileId}/friends/"),false,$count);
		}
		if(self::canView($viewerId,$profileId,'groups')){
			$count=self::getGroupCount($profileId);
			$tabs[]=self::buildTab('groups','Grupos',home_url("/members/{$profileId}/groups/"),false,$count);
		}
		$tabs[]=self::buildTab('badges','Badges',home_url("/members/{$profileId}/badges/"));
		$customTabs=self::getCustomTabs($profileId);
		foreach($customTabs as $ct){$tabs[]=$ct;}
		return apply_filters('apollo_profile_tabs',$tabs,$profileId,$viewerId);
	}

	public static function getGroupNavItems(int $groupId, int $userId): array {
		$items=[];
		$items[]=self::buildItem('home','Início',home_url("/groups/{$groupId}/"),'home');
		$items[]=self::buildItem('activity','Atividade',home_url("/groups/{$groupId}/activity/"),'activity');
		$memberCount=self::getGroupMemberCount($groupId);
		$items[]=self::buildItem('members','Membros',home_url("/groups/{$groupId}/members/"),'users',$memberCount);
		if(self::groupHasForum($groupId)){
			$items[]=self::buildItem('forum','Fórum',home_url("/groups/{$groupId}/forum/"),'message-square');
		}
		if(self::groupHasEvents($groupId)){
			$items[]=self::buildItem('events','Eventos',home_url("/groups/{$groupId}/events/"),'calendar');
		}
		if(self::isGroupAdmin($groupId,$userId)){
			$items[]=self::buildItem('admin','Administrar',home_url("/groups/{$groupId}/admin/"),'settings');
		}
		return apply_filters('apollo_group_nav_items',$items,$groupId,$userId);
	}

	public static function getDashboardWidgets(int $userId): array {
		$widgets=[];
		$widgets[]=['id'=>'activity_feed','title'=>'Atividade Recente','priority'=>10];
		$widgets[]=['id'=>'online_friends','title'=>'Amigos Online','priority'=>20,'count'=>self::getOnlineFriendsCount($userId)];
		$widgets[]=['id'=>'upcoming_events','title'=>'Próximos Eventos','priority'=>30,'count'=>self::getUpcomingEventsCount($userId)];
		$widgets[]=['id'=>'group_activity','title'=>'Atividade dos Grupos','priority'=>40];
		$widgets[]=['id'=>'suggestions','title'=>'Sugestões','priority'=>50];
		$widgets[]=['id'=>'leaderboard','title'=>'Ranking','priority'=>60];
		return apply_filters('apollo_dashboard_widgets',$widgets,$userId);
	}

	private static function buildItem(string $id, string $label, string $url, string $icon, int $count=0): array {
		return ['id'=>$id,'label'=>$label,'url'=>$url,'icon'=>$icon,'count'=>$count,'active'=>false];
	}

	private static function buildTab(string $id, string $label, string $url, bool $default=false, int $count=0): array {
		return ['id'=>$id,'label'=>$label,'url'=>$url,'default'=>$default,'count'=>$count];
	}

	private static function getFriendCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d AND status='accepted'",$userId));
	}

	private static function getGroupCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_members';
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id=%d",$userId));
	}

	private static function getUnreadMessageCount(int $userId): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_message_participants';
		return (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(unread_count) FROM {$t} WHERE user_id=%d AND is_deleted=0",$userId));
	}

	private static function getNotificationCount(int $userId): int {
		return (int)get_user_meta($userId,'apollo_unread_notifications',true);
	}

	private static function getPendingInvitesCount(int $userId): int {
		global $wpdb;
		$gi=$wpdb->prefix.'apollo_group_invites';
		$fr=$wpdb->prefix.'apollo_connections';
		$groupInvites=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$gi} WHERE user_id=%d AND status='pending'",$userId));
		$friendRequests=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$fr} WHERE friend_id=%d AND status='pending'",$userId));
		return $groupInvites+$friendRequests;
	}

	private static function canView(int $viewerId, int $profileId, string $what): bool {
		if($viewerId===$profileId){return true;}
		$setting=get_user_meta($profileId,"apollo_{$what}_visibility",true)?:'public';
		return match($setting){
			'public'=>true,
			'friends'=>self::areFriends($viewerId,$profileId),
			'none'=>false,
			default=>true
		};
	}

	private static function areFriends(int $userId1, int $userId2): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE user_id=%d AND friend_id=%d AND status='accepted'",$userId1,$userId2));
	}

	private static function getCustomTabs(int $profileId): array {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_tabs';
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE (user_id=%d OR user_id IS NULL) AND is_active=1 ORDER BY sort_order ASC",
			$profileId
		),ARRAY_A)??[];
		return array_map(fn($r)=>self::buildTab($r['slug'],$r['label'],home_url("/members/{$profileId}/".$r['slug'].'/')),$rows);
	}

	private static function getGroupMemberCount(int $groupId): int {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_members';
		return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE group_id=%d",$groupId));
	}

	private static function groupHasForum(int $groupId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT enable_forum FROM {$wpdb->prefix}apollo_groups WHERE id=%d",$groupId));
	}

	private static function groupHasEvents(int $groupId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}apollo_events WHERE group_id=%d LIMIT 1",$groupId));
	}

	private static function isGroupAdmin(int $groupId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_members';
		$role=$wpdb->get_var($wpdb->prepare("SELECT role FROM {$t} WHERE group_id=%d AND user_id=%d",$groupId,$userId));
		return in_array($role,['admin','owner','mod'],true);
	}

	private static function getOnlineFriendsCount(int $userId): int {
		global $wpdb;
		$c=$wpdb->prefix.'apollo_connections';
		$o=$wpdb->prefix.'apollo_online_users';
		$threshold=gmdate('Y-m-d H:i:s',strtotime('-5 minutes'));
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$c} c JOIN {$o} o ON c.friend_id=o.user_id WHERE c.user_id=%d AND c.status='accepted' AND o.last_activity>=%s",
			$userId,$threshold
		));
	}

	private static function getUpcomingEventsCount(int $userId): int {
		global $wpdb;
		$e=$wpdb->prefix.'apollo_events';
		$a=$wpdb->prefix.'apollo_event_attendees';
		$now=current_time('mysql');
		return (int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$a} a JOIN {$e} e ON a.event_id=e.id WHERE a.user_id=%d AND a.status='going' AND e.start_date>=%s",
			$userId,$now
		));
	}
}
