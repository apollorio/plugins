<?php
declare(strict_types=1);
namespace Apollo\Modules\Profile;

final class ProfileCompletenessRepository {
	private const CACHE_KEY='apollo_profile_completeness_';
	private const CACHE_TTL=3600;

	public static function calculate(int $userId): array {
		$cached=wp_cache_get(self::CACHE_KEY.$userId,'apollo');
		if($cached!==false){return $cached;}
		$weights=self::getFieldWeights();
		$total=0;$filled=0;$missing=[];$steps=[];
		foreach($weights as $field=>$config){
			$total+=$config['weight'];
			$value=self::getFieldValue($userId,$field,$config['source']);
			if(self::isFieldFilled($value,$field)){
				$filled+=$config['weight'];
				$steps[$field]=['completed'=>true,'label'=>$config['label']];
			}else{
				$missing[]=['field'=>$field,'label'=>$config['label'],'weight'=>$config['weight'],'link'=>$config['link']??''];
				$steps[$field]=['completed'=>false,'label'=>$config['label']];
			}
		}
		$percent=$total>0?round(($filled/$total)*100):0;
		$result=['percent'=>$percent,'filled'=>$filled,'total'=>$total,'missing'=>$missing,'steps'=>$steps,'next_step'=>$missing[0]??null];
		wp_cache_set(self::CACHE_KEY.$userId,$result,'apollo',self::CACHE_TTL);
		return $result;
	}

	public static function getPercent(int $userId): int {
		return (int)self::calculate($userId)['percent'];
	}

	public static function getMissing(int $userId): array {
		return self::calculate($userId)['missing'];
	}

	public static function getNextStep(int $userId): ?array {
		return self::calculate($userId)['next_step'];
	}

	public static function invalidateCache(int $userId): void {
		wp_cache_delete(self::CACHE_KEY.$userId,'apollo');
	}

	private static function getFieldWeights(): array {
		return apply_filters('apollo_profile_completeness_weights',[
			'avatar'=>['weight'=>15,'label'=>'Foto de perfil','source'=>'avatar','link'=>'/profile/edit/avatar'],
			'cover'=>['weight'=>10,'label'=>'Capa do perfil','source'=>'meta:cover_image','link'=>'/profile/edit/cover'],
			'display_name'=>['weight'=>10,'label'=>'Nome de exibição','source'=>'user:display_name','link'=>'/profile/edit'],
			'bio'=>['weight'=>10,'label'=>'Biografia','source'=>'meta:description','link'=>'/profile/edit'],
			'location'=>['weight'=>8,'label'=>'Localização','source'=>'profile:location','link'=>'/profile/edit/details'],
			'website'=>['weight'=>5,'label'=>'Website','source'=>'user:user_url','link'=>'/profile/edit'],
			'social_links'=>['weight'=>5,'label'=>'Redes sociais','source'=>'meta:social_links','link'=>'/profile/edit/social'],
			'phone'=>['weight'=>7,'label'=>'Telefone','source'=>'profile:phone','link'=>'/profile/edit/contact'],
			'birth_date'=>['weight'=>5,'label'=>'Data de nascimento','source'=>'profile:birth_date','link'=>'/profile/edit/details'],
			'gender'=>['weight'=>3,'label'=>'Gênero','source'=>'profile:gender','link'=>'/profile/edit/details'],
			'occupation'=>['weight'=>7,'label'=>'Profissão','source'=>'profile:occupation','link'=>'/profile/edit/work'],
			'company'=>['weight'=>5,'label'=>'Empresa','source'=>'profile:company','link'=>'/profile/edit/work'],
			'education'=>['weight'=>5,'label'=>'Educação','source'=>'profile:education','link'=>'/profile/edit/education'],
			'interests'=>['weight'=>5,'label'=>'Interesses','source'=>'profile:interests','link'=>'/profile/edit/interests']
		]);
	}

	private static function getFieldValue(int $userId, string $field, string $source): mixed {
		$parts=explode(':',$source);
		$type=$parts[0]??'';
		$key=$parts[1]??$field;
		return match($type){
			'user'=>self::getUserField($userId,$key),
			'meta'=>get_user_meta($userId,$key,true),
			'avatar'=>self::hasAvatar($userId),
			'profile'=>self::getProfileField($userId,$key),
			default=>null
		};
	}

	private static function getUserField(int $userId, string $field): mixed {
		$user=get_userdata($userId);
		return $user?($user->$field??null):null;
	}

	private static function hasAvatar(int $userId): bool {
		$custom=get_user_meta($userId,'apollo_avatar',true);
		if($custom){return true;}
		$url=get_avatar_url($userId,['default'=>'blank']);
		return $url&&!str_contains($url,'gravatar.com/avatar/');
	}

	private static function getProfileField(int $userId, string $fieldSlug): mixed {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_field_values';
		$f=$wpdb->prefix.'apollo_profile_fields';
		return $wpdb->get_var($wpdb->prepare(
			"SELECT v.value FROM {$t} v JOIN {$f} f ON v.field_id=f.id WHERE v.user_id=%d AND f.slug=%s",
			$userId,$fieldSlug
		));
	}

	private static function isFieldFilled(mixed $value, string $field): bool {
		if($value===null||$value===''||$value===false){return false;}
		if(is_array($value)){return count($value)>0;}
		if($field==='avatar'){return (bool)$value;}
		return true;
	}

	public static function getUsersWithLowCompletion(int $below=50, int $limit=100): array {
		global $wpdb;
		$users=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->users} ORDER BY user_registered DESC LIMIT %d",$limit*3));
		$results=[];
		foreach($users as $uid){
			$percent=self::getPercent((int)$uid);
			if($percent<$below){$results[]=['user_id'=>(int)$uid,'percent'=>$percent];}
			if(count($results)>=$limit){break;}
		}
		return $results;
	}

	public static function getAverageCompletion(): float {
		global $wpdb;
		$users=$wpdb->get_col("SELECT ID FROM {$wpdb->users} LIMIT 1000");
		if(!$users){return 0;}
		$total=0;
		foreach($users as $uid){$total+=self::getPercent((int)$uid);}
		return round($total/count($users),1);
	}

	public static function getCompletionDistribution(): array {
		global $wpdb;
		$users=$wpdb->get_col("SELECT ID FROM {$wpdb->users} LIMIT 1000");
		$dist=['0-25'=>0,'26-50'=>0,'51-75'=>0,'76-99'=>0,'100'=>0];
		foreach($users as $uid){
			$p=self::getPercent((int)$uid);
			if($p===100){$dist['100']++;}
			elseif($p>=76){$dist['76-99']++;}
			elseif($p>=51){$dist['51-75']++;}
			elseif($p>=26){$dist['26-50']++;}
			else{$dist['0-25']++;}
		}
		return $dist;
	}

	public static function awardPointsForCompletion(int $userId): void {
		$data=self::calculate($userId);
		if($data['percent']===100){
			$awarded=get_user_meta($userId,'apollo_profile_complete_awarded',true);
			if(!$awarded){
				do_action('apollo_award_points',$userId,100,'profile_complete','Perfil 100% completo');
				update_user_meta($userId,'apollo_profile_complete_awarded',current_time('mysql'));
			}
		}
	}
}
