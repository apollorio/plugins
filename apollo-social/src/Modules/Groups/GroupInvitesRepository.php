<?php
declare(strict_types=1);
namespace Apollo\Modules\Groups;

final class GroupInvitesRepository {
	private const TABLE='apollo_group_invites';

	public static function create(int $groupId,int $inviterId,int $inviteeId,?string $message=null): int {
		global $wpdb;
		$existing=$wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}".self::TABLE." WHERE group_id=%d AND invitee_id=%d AND status='pending'",$groupId,$inviteeId
		));
		if($existing)return(int)$existing;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'group_id'=>$groupId,'inviter_id'=>$inviterId,'invitee_id'=>$inviteeId,
			'message'=>$message?sanitize_textarea_field($message):null,'status'=>'pending'
		]);
		return(int)$wpdb->insert_id;
	}

	public static function accept(int $inviteId): bool {
		global $wpdb;
		$invite=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d AND status='pending'",$inviteId),ARRAY_A);
		if(!$invite)return false;
		$wpdb->update($wpdb->prefix.self::TABLE,['status'=>'accepted'],['id'=>$inviteId]);
		return GroupsRepository::addMember((int)$invite['group_id'],(int)$invite['invitee_id']);
	}

	public static function decline(int $inviteId): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['status'=>'declined'],['id'=>$inviteId,'status'=>'pending'])!==false;
	}

	public static function getPendingForUser(int $userId): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT i.*,g.name as group_name,u.display_name as inviter_name
			 FROM {$wpdb->prefix}".self::TABLE." i
			 LEFT JOIN {$wpdb->prefix}apollo_groups g ON g.id=i.group_id
			 LEFT JOIN {$wpdb->users} u ON u.ID=i.inviter_id
			 WHERE i.invitee_id=%d AND i.status='pending' ORDER BY i.created_at DESC",
			$userId
		),ARRAY_A)?:[];
	}

	public static function countPendingForUser(int $userId): int {
		global $wpdb;
		return(int)$wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." WHERE invitee_id=%d AND status='pending'",$userId
		));
	}

	public static function delete(int $inviteId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['id'=>$inviteId])!==false;
	}
}
