<?php
declare(strict_types=1);
namespace Apollo\Api;

final class RestApiHandlers {

	public static function register(): void {
		add_action('rest_api_init',[self::class,'registerRoutes']);
	}

	public static function registerRoutes(): void {
		$ns='apollo/v1';
		register_rest_route($ns,'/members',['methods'=>'GET','callback'=>[self::class,'getMembers'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/members/(?P<id>\d+)',['methods'=>'GET','callback'=>[self::class,'getMember'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/members/online',['methods'=>'GET','callback'=>[self::class,'getOnlineMembers'],'permission_callback'=>'is_user_logged_in']);
		register_rest_route($ns,'/activity',['methods'=>'GET','callback'=>[self::class,'getActivity'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/activity',['methods'=>'POST','callback'=>[self::class,'postActivity'],'permission_callback'=>'is_user_logged_in']);
		register_rest_route($ns,'/groups',['methods'=>'GET','callback'=>[self::class,'getGroups'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/groups/(?P<id>\d+)',['methods'=>'GET','callback'=>[self::class,'getGroup'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/leaderboard',['methods'=>'GET','callback'=>[self::class,'getLeaderboard'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/competitions',['methods'=>'GET','callback'=>[self::class,'getCompetitions'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/map/pins',['methods'=>'GET','callback'=>[self::class,'getMapPins'],'permission_callback'=>'__return_true']);
		register_rest_route($ns,'/me',['methods'=>'GET','callback'=>[self::class,'getMe'],'permission_callback'=>'is_user_logged_in']);
		register_rest_route($ns,'/me/stats',['methods'=>'GET','callback'=>[self::class,'getMyStats'],'permission_callback'=>'is_user_logged_in']);
	}

	public static function getMembers(\WP_REST_Request $req): \WP_REST_Response {
		$args=['search'=>$req->get_param('search')?:'','limit'=>min(100,(int)($req->get_param('limit')?:20)),'offset'=>(int)($req->get_param('offset')?:0)];
		$members=\Apollo\Modules\Members\MembersDirectoryRepository::search($args);
		$total=\Apollo\Modules\Members\MembersDirectoryRepository::count($args);
		return new\WP_REST_Response(['members'=>$members,'total'=>$total],200);
	}

	public static function getMember(\WP_REST_Request $req): \WP_REST_Response {
		$id=(int)$req->get_param('id');
		$user=get_userdata($id);
		if(!$user)return new\WP_REST_Response(['error'=>'User not found'],404);
		$data=['id'=>$user->ID,'name'=>$user->display_name,'avatar'=>get_avatar_url($user->ID,['size'=>200]),'is_verified'=>\Apollo\Modules\Members\VerifiedUsersRepository::isVerified($id),'is_online'=>\Apollo\Modules\Members\OnlineUsersRepository::isOnline($id)];
		return new\WP_REST_Response($data,200);
	}

	public static function getOnlineMembers(\WP_REST_Request $req): \WP_REST_Response {
		$limit=min(100,(int)($req->get_param('limit')?:50));
		$users=\Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers($limit);
		return new\WP_REST_Response(['users'=>$users,'count'=>count($users)],200);
	}

	public static function getActivity(\WP_REST_Request $req): \WP_REST_Response {
		$args=['user_id'=>$req->get_param('user_id')?(int)$req->get_param('user_id'):null,'component'=>$req->get_param('component')?:'','limit'=>min(100,(int)($req->get_param('limit')?:20)),'offset'=>(int)($req->get_param('offset')?:0)];
		$activity=\Apollo\Modules\Activity\ActivityRepository::getFeed($args);
		return new\WP_REST_Response(['activity'=>$activity],200);
	}

	public static function postActivity(\WP_REST_Request $req): \WP_REST_Response {
		$userId=get_current_user_id();
		$content=sanitize_textarea_field($req->get_param('content')?:'');
		if(empty($content))return new\WP_REST_Response(['error'=>'Content required'],400);
		$id=\Apollo\Modules\Activity\ActivityRepository::create(['user_id'=>$userId,'action'=>'status_update','component'=>'activity','type'=>'status','content'=>$content,'privacy'=>sanitize_key($req->get_param('privacy')?:'public')]);
		return new\WP_REST_Response(['id'=>$id],201);
	}

	public static function getGroups(\WP_REST_Request $req): \WP_REST_Response {
		$args=['search'=>$req->get_param('search')?:'','status'=>'public','limit'=>min(100,(int)($req->get_param('limit')?:20)),'offset'=>(int)($req->get_param('offset')?:0)];
		$groups=\Apollo\Modules\Groups\GroupsRepository::search($args);
		return new\WP_REST_Response(['groups'=>$groups],200);
	}

	public static function getGroup(\WP_REST_Request $req): \WP_REST_Response {
		$id=(int)$req->get_param('id');
		$group=\Apollo\Modules\Groups\GroupsRepository::get($id);
		if(!$group)return new\WP_REST_Response(['error'=>'Group not found'],404);
		if($group['status']==='hidden'&&!is_user_logged_in())return new\WP_REST_Response(['error'=>'Not allowed'],403);
		return new\WP_REST_Response($group,200);
	}

	public static function getLeaderboard(\WP_REST_Request $req): \WP_REST_Response {
		$limit=min(100,(int)($req->get_param('limit')?:50));
		$leaderboard=\Apollo\Modules\Gamification\PointsRepository::getLeaderboard('default',$limit);
		return new\WP_REST_Response(['leaderboard'=>$leaderboard],200);
	}

	public static function getCompetitions(\WP_REST_Request $req): \WP_REST_Response {
		$active=\Apollo\Modules\Gamification\CompetitionsRepository::getActive();
		$upcoming=\Apollo\Modules\Gamification\CompetitionsRepository::getUpcoming();
		return new\WP_REST_Response(['active'=>$active,'upcoming'=>$upcoming],200);
	}

	public static function getMapPins(\WP_REST_Request $req): \WP_REST_Response {
		$cat=$req->get_param('category')?:'';
		$pins=$cat?\Apollo\Modules\Maps\MapPinsRepository::getByCategory($cat):\Apollo\Modules\Maps\MapPinsRepository::getAll();
		$geojson=$req->get_param('format')==='geojson';
		return new\WP_REST_Response($geojson?\Apollo\Modules\Maps\MapPinsRepository::toGeoJson($pins):['pins'=>$pins],200);
	}

	public static function getMe(\WP_REST_Request $req): \WP_REST_Response {
		$userId=get_current_user_id();
		$user=get_userdata($userId);
		return new\WP_REST_Response([
			'id'=>$userId,'name'=>$user->display_name,'email'=>$user->user_email,
			'avatar'=>get_avatar_url($userId,['size'=>200]),
			'is_verified'=>\Apollo\Modules\Members\VerifiedUsersRepository::isVerified($userId),
			'settings'=>\Apollo\Modules\Members\UserSettingsRepository::getWithDefaults($userId)
		],200);
	}

	public static function getMyStats(\WP_REST_Request $req): \WP_REST_Response {
		$userId=get_current_user_id();
		$stats=\Apollo\Modules\MyData\MyDataRepository::getMyStats($userId);
		return new\WP_REST_Response($stats,200);
	}
}
