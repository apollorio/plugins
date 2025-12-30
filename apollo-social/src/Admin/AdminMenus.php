<?php
declare(strict_types=1);
namespace Apollo\Admin;

final class AdminMenus {

	public static function register(): void {
		add_action('admin_menu',[self::class,'addMenus']);
	}

	public static function addMenus(): void {
		add_menu_page('Apollo Social','Apollo Social','manage_options','apollo-social',[self::class,'dashboardPage'],'dashicons-groups',30);
		add_submenu_page('apollo-social','Dashboard','Dashboard','manage_options','apollo-social',[self::class,'dashboardPage']);
		add_submenu_page('apollo-social','Members','Members','manage_options','apollo-members',[self::class,'membersPage']);
		add_submenu_page('apollo-social','Pending Users','Pending Users','manage_options','apollo-pending-users',[self::class,'pendingUsersPage']);
		add_submenu_page('apollo-social','Spammers','Spammers','manage_options','apollo-spammers',[self::class,'spammersPage']);
		add_submenu_page('apollo-social','Verified Users','Verified Users','manage_options','apollo-verified',[self::class,'verifiedUsersPage']);
		add_submenu_page('apollo-social','Member Types','Member Types','manage_options','apollo-member-types',[self::class,'memberTypesPage']);
		add_submenu_page('apollo-social','Groups','Groups','manage_options','apollo-groups',[self::class,'groupsPage']);
		add_submenu_page('apollo-social','Activity','Activity','manage_options','apollo-activity',[self::class,'activityPage']);
		add_submenu_page('apollo-social','Points & Ranks','Points & Ranks','manage_options','apollo-gamification',[self::class,'gamificationPage']);
		add_submenu_page('apollo-social','Achievements','Achievements','manage_options','apollo-achievements',[self::class,'achievementsPage']);
		add_submenu_page('apollo-social','Competitions','Competitions','manage_options','apollo-competitions',[self::class,'competitionsPage']);
		add_submenu_page('apollo-social','Notices','Notices','manage_options','apollo-notices',[self::class,'noticesPage']);
		add_submenu_page('apollo-social','Profile Fields','Profile Fields','manage_options','apollo-profile-fields',[self::class,'profileFieldsPage']);
		add_submenu_page('apollo-social','Email Queue','Email Queue','manage_options','apollo-email-queue',[self::class,'emailQueuePage']);
		add_submenu_page('apollo-social','Team Members','Team Members','manage_options','apollo-team',[self::class,'teamPage']);
		add_submenu_page('apollo-social','Map Pins','Map Pins','manage_options','apollo-map',[self::class,'mapPage']);
		add_submenu_page('apollo-social','Testimonials','Testimonials','manage_options','apollo-testimonials',[self::class,'testimonialsPage']);
		add_submenu_page('apollo-social','Settings','Settings','manage_options','apollo-settings',[self::class,'settingsPage']);
	}

	public static function dashboardPage(): void {
		$online=\Apollo\Modules\Members\OnlineUsersRepository::countOnline();
		$pending=\Apollo\Modules\Moderation\PendingUsersRepository::countPending();
		$spammers=\Apollo\Modules\Moderation\SpammerRepository::count();
		$verified=\Apollo\Modules\Members\VerifiedUsersRepository::countVerified();
		$emailStats=\Apollo\Modules\Email\EmailQueueRepository::countByStatus();
		echo'<div class="wrap"><h1>Apollo Social Dashboard</h1>';
		echo'<div class="apollo-dashboard-widgets">';
		echo'<div class="widget"><h3>Online Users</h3><span class="count">'.$online.'</span></div>';
		echo'<div class="widget"><h3>Pending Users</h3><span class="count">'.$pending.'</span></div>';
		echo'<div class="widget"><h3>Spammers</h3><span class="count">'.$spammers.'</span></div>';
		echo'<div class="widget"><h3>Verified Users</h3><span class="count">'.$verified.'</span></div>';
		echo'<div class="widget"><h3>Pending Emails</h3><span class="count">'.($emailStats['pending']??0).'</span></div>';
		echo'</div></div>';
	}

	public static function membersPage(): void {
		$search=sanitize_text_field($_GET['s']??'');
		$page=max(1,(int)($_GET['paged']??1));
		$limit=20;$offset=($page-1)*$limit;
		$members=\Apollo\Modules\Members\MembersDirectoryRepository::search(['search'=>$search,'limit'=>$limit,'offset'=>$offset]);
		$total=\Apollo\Modules\Members\MembersDirectoryRepository::count(['search'=>$search]);
		echo'<div class="wrap"><h1>Members</h1>';
		echo'<form method="get"><input type="hidden" name="page" value="apollo-members">';
		echo'<input type="search" name="s" value="'.esc_attr($search).'" placeholder="Search...">';
		echo'<input type="submit" class="button" value="Search"></form>';
		echo'<table class="wp-list-table widefat striped"><thead><tr>';
		echo'<th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th>';
		echo'</tr></thead><tbody>';
		foreach($members as $m){
			$isVerified=\Apollo\Modules\Members\VerifiedUsersRepository::isVerified((int)$m['ID']);
			$isSpammer=\Apollo\Modules\Moderation\SpammerRepository::isSpammer((int)$m['ID']);
			echo'<tr>';
			echo'<td>'.esc_html($m['ID']).'</td>';
			echo'<td>'.esc_html($m['display_name']).'</td>';
			echo'<td>'.esc_html($m['user_email']).'</td>';
			echo'<td>'.($isVerified?'✓ Verified':'').($isSpammer?' ⚠ Spammer':'').'</td>';
			echo'<td><a href="'.admin_url('user-edit.php?user_id='.$m['ID']).'">Edit</a></td>';
			echo'</tr>';
		}
		echo'</tbody></table>';
		echo'<p>Total: '.$total.'</p></div>';
	}

	public static function pendingUsersPage(): void {
		if(isset($_POST['action'])&&$_POST['action']==='approve'&&wp_verify_nonce($_POST['_wpnonce'],'apollo_pending')){
			\Apollo\Modules\Moderation\PendingUsersRepository::approve((int)$_POST['user_id']);
			echo'<div class="notice notice-success"><p>User approved.</p></div>';
		}
		if(isset($_POST['action'])&&$_POST['action']==='reject'&&wp_verify_nonce($_POST['_wpnonce'],'apollo_pending')){
			\Apollo\Modules\Moderation\PendingUsersRepository::reject((int)$_POST['user_id'],$_POST['notes']??'');
			echo'<div class="notice notice-success"><p>User rejected.</p></div>';
		}
		$pending=\Apollo\Modules\Moderation\PendingUsersRepository::getPending();
		echo'<div class="wrap"><h1>Pending Users</h1>';
		if(empty($pending)){echo'<p>No pending users.</p></div>';return;}
		echo'<table class="wp-list-table widefat striped"><thead><tr>';
		echo'<th>User</th><th>Email</th><th>Registered</th><th>Reason</th><th>Actions</th>';
		echo'</tr></thead><tbody>';
		foreach($pending as $p){
			echo'<tr>';
			echo'<td>'.esc_html($p['display_name']).'</td>';
			echo'<td>'.esc_html($p['user_email']).'</td>';
			echo'<td>'.esc_html($p['user_registered']).'</td>';
			echo'<td>'.esc_html($p['reason']).'</td>';
			echo'<td>';
			echo'<form method="post" style="display:inline">';
			wp_nonce_field('apollo_pending');
			echo'<input type="hidden" name="user_id" value="'.$p['user_id'].'">';
			echo'<input type="hidden" name="action" value="approve">';
			echo'<button type="submit" class="button button-primary">Approve</button></form> ';
			echo'<form method="post" style="display:inline">';
			wp_nonce_field('apollo_pending');
			echo'<input type="hidden" name="user_id" value="'.$p['user_id'].'">';
			echo'<input type="hidden" name="action" value="reject">';
			echo'<button type="submit" class="button">Reject</button></form>';
			echo'</td></tr>';
		}
		echo'</tbody></table></div>';
	}

	public static function spammersPage(): void {
		if(isset($_POST['action'])&&$_POST['action']==='unmark'&&wp_verify_nonce($_POST['_wpnonce'],'apollo_spammer')){
			\Apollo\Modules\Moderation\SpammerRepository::unmarkSpammer((int)$_POST['user_id']);
			echo'<div class="notice notice-success"><p>User unmarked as spammer.</p></div>';
		}
		$spammers=\Apollo\Modules\Moderation\SpammerRepository::getAll();
		echo'<div class="wrap"><h1>Spammers</h1>';
		if(empty($spammers)){echo'<p>No spammers.</p></div>';return;}
		echo'<table class="wp-list-table widefat striped"><thead><tr>';
		echo'<th>User</th><th>Email</th><th>Marked By</th><th>Reason</th><th>Date</th><th>Actions</th>';
		echo'</tr></thead><tbody>';
		foreach($spammers as $s){
			echo'<tr>';
			echo'<td>'.esc_html($s['display_name']).'</td>';
			echo'<td>'.esc_html($s['user_email']).'</td>';
			echo'<td>'.esc_html($s['marked_by_name']??'').'</td>';
			echo'<td>'.esc_html($s['reason']).'</td>';
			echo'<td>'.esc_html($s['marked_at']).'</td>';
			echo'<td><form method="post">';
			wp_nonce_field('apollo_spammer');
			echo'<input type="hidden" name="user_id" value="'.$s['user_id'].'">';
			echo'<input type="hidden" name="action" value="unmark">';
			echo'<button type="submit" class="button">Unmark</button></form></td>';
			echo'</tr>';
		}
		echo'</tbody></table></div>';
	}

	public static function verifiedUsersPage(): void {
		if(isset($_POST['action'])&&$_POST['action']==='verify'&&wp_verify_nonce($_POST['_wpnonce'],'apollo_verify')){
			\Apollo\Modules\Members\VerifiedUsersRepository::verify((int)$_POST['user_id'],$_POST['reason']??'');
			echo'<div class="notice notice-success"><p>User verified.</p></div>';
		}
		if(isset($_POST['action'])&&$_POST['action']==='unverify'&&wp_verify_nonce($_POST['_wpnonce'],'apollo_verify')){
			\Apollo\Modules\Members\VerifiedUsersRepository::unverify((int)$_POST['user_id']);
			echo'<div class="notice notice-success"><p>Verification removed.</p></div>';
		}
		$verified=\Apollo\Modules\Members\VerifiedUsersRepository::getVerifiedUsers();
		echo'<div class="wrap"><h1>Verified Users</h1>';
		echo'<form method="post"><h3>Verify a User</h3>';
		wp_nonce_field('apollo_verify');
		echo'<input type="hidden" name="action" value="verify">';
		echo'<input type="number" name="user_id" placeholder="User ID" required> ';
		echo'<input type="text" name="reason" placeholder="Reason"> ';
		echo'<button type="submit" class="button button-primary">Verify</button></form>';
		echo'<table class="wp-list-table widefat striped"><thead><tr>';
		echo'<th>ID</th><th>Name</th><th>Email</th><th>Actions</th>';
		echo'</tr></thead><tbody>';
		foreach($verified as $v){
			echo'<tr>';
			echo'<td>'.esc_html($v['ID']).'</td>';
			echo'<td>'.esc_html($v['display_name']).'</td>';
			echo'<td>'.esc_html($v['user_email']).'</td>';
			echo'<td><form method="post" style="display:inline">';
			wp_nonce_field('apollo_verify');
			echo'<input type="hidden" name="user_id" value="'.$v['ID'].'">';
			echo'<input type="hidden" name="action" value="unverify">';
			echo'<button type="submit" class="button">Remove Verification</button></form></td>';
			echo'</tr>';
		}
		echo'</tbody></table></div>';
	}

	public static function memberTypesPage(): void {echo'<div class="wrap"><h1>Member Types</h1><p>Configure member types.</p></div>';}
	public static function groupsPage(): void {echo'<div class="wrap"><h1>Groups</h1><p>Manage groups.</p></div>';}
	public static function activityPage(): void {echo'<div class="wrap"><h1>Activity</h1><p>Monitor activity streams.</p></div>';}
	public static function gamificationPage(): void {echo'<div class="wrap"><h1>Points & Ranks</h1><p>Configure gamification.</p></div>';}
	public static function achievementsPage(): void {echo'<div class="wrap"><h1>Achievements</h1><p>Manage achievements.</p></div>';}
	public static function competitionsPage(): void {echo'<div class="wrap"><h1>Competitions</h1><p>Manage competitions.</p></div>';}
	public static function noticesPage(): void {echo'<div class="wrap"><h1>Notices</h1><p>Manage sitewide notices.</p></div>';}
	public static function profileFieldsPage(): void {echo'<div class="wrap"><h1>Profile Fields</h1><p>Configure extended profile fields.</p></div>';}
	public static function emailQueuePage(): void {echo'<div class="wrap"><h1>Email Queue</h1><p>Monitor email queue.</p></div>';}
	public static function teamPage(): void {echo'<div class="wrap"><h1>Team Members</h1><p>Manage team members.</p></div>';}
	public static function mapPage(): void {echo'<div class="wrap"><h1>Map Pins</h1><p>Manage map locations.</p></div>';}
	public static function testimonialsPage(): void {echo'<div class="wrap"><h1>Testimonials</h1><p>Manage testimonials.</p></div>';}
	public static function settingsPage(): void {echo'<div class="wrap"><h1>Settings</h1><p>Apollo Social settings.</p></div>';}
}
