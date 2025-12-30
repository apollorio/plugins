<?php
declare(strict_types=1);
namespace Apollo\Modules\Person;

final class PersonRepository {
	private const TABLE='apollo_persons';

	public static function create(array $data): int {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$wpdb->insert($t,[
			'user_id'=>(int)($data['user_id']??0),
			'name'=>sanitize_text_field($data['name']),
			'slug'=>sanitize_title($data['name']),
			'title'=>sanitize_text_field($data['title']??''),
			'department'=>sanitize_text_field($data['department']??''),
			'bio'=>wp_kses_post($data['bio']??''),
			'photo'=>esc_url($data['photo']??''),
			'email'=>sanitize_email($data['email']??''),
			'phone'=>sanitize_text_field($data['phone']??''),
			'social'=>wp_json_encode($data['social']??[]),
			'skills'=>wp_json_encode($data['skills']??[]),
			'sort_order'=>(int)($data['sort_order']??0),
			'is_active'=>1,
			'created_at'=>current_time('mysql')
		],['%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s']);
		return (int)$wpdb->insert_id;
	}

	public static function update(int $personId, array $data): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$upd=[];$fmt=[];
		if(isset($data['name'])){$upd['name']=sanitize_text_field($data['name']);$fmt[]='%s';}
		if(isset($data['title'])){$upd['title']=sanitize_text_field($data['title']);$fmt[]='%s';}
		if(isset($data['department'])){$upd['department']=sanitize_text_field($data['department']);$fmt[]='%s';}
		if(isset($data['bio'])){$upd['bio']=wp_kses_post($data['bio']);$fmt[]='%s';}
		if(isset($data['photo'])){$upd['photo']=esc_url($data['photo']);$fmt[]='%s';}
		if(isset($data['email'])){$upd['email']=sanitize_email($data['email']);$fmt[]='%s';}
		if(isset($data['phone'])){$upd['phone']=sanitize_text_field($data['phone']);$fmt[]='%s';}
		if(isset($data['social'])){$upd['social']=wp_json_encode($data['social']);$fmt[]='%s';}
		if(isset($data['skills'])){$upd['skills']=wp_json_encode($data['skills']);$fmt[]='%s';}
		if(isset($data['sort_order'])){$upd['sort_order']=(int)$data['sort_order'];$fmt[]='%d';}
		if(isset($data['is_active'])){$upd['is_active']=(int)$data['is_active'];$fmt[]='%d';}
		$upd['updated_at']=current_time('mysql');$fmt[]='%s';
		return $wpdb->update($t,$upd,['id'=>$personId],$fmt,['%d'])!==false;
	}

	public static function delete(int $personId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->delete($t,['id'=>$personId],['%d'])!==false;
	}

	public static function get(int $personId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$personId),ARRAY_A);
		return $row?self::decode($row):null;
	}

	public static function getBySlug(string $slug): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE slug=%s",$slug),ARRAY_A);
		return $row?self::decode($row):null;
	}

	public static function getByUser(int $userId): ?array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE user_id=%d",$userId),ARRAY_A);
		return $row?self::decode($row):null;
	}

	public static function getAll(bool $activeOnly=true): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$where=$activeOnly?'WHERE is_active=1':'';
		$rows=$wpdb->get_results("SELECT * FROM {$t} {$where} ORDER BY sort_order ASC, name ASC",ARRAY_A)??[];
		return array_map([self::class,'decode'],$rows);
	}

	public static function getByDepartment(string $department): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE department=%s AND is_active=1 ORDER BY sort_order ASC, name ASC",
			$department
		),ARRAY_A)??[];
		return array_map([self::class,'decode'],$rows);
	}

	public static function getDepartments(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->get_col("SELECT DISTINCT department FROM {$t} WHERE department!='' AND is_active=1 ORDER BY department ASC")??[];
	}

	public static function search(string $query): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		$q='%'.$wpdb->esc_like($query).'%';
		$rows=$wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$t} WHERE is_active=1 AND (name LIKE %s OR title LIKE %s OR department LIKE %s OR bio LIKE %s) ORDER BY name ASC",
			$q,$q,$q,$q
		),ARRAY_A)??[];
		return array_map([self::class,'decode'],$rows);
	}

	public static function reorder(array $order): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		foreach($order as $position=>$personId){
			$wpdb->update($t,['sort_order'=>$position],['id'=>(int)$personId],['%d'],['%d']);
		}
		return true;
	}

	public static function linkToUser(int $personId, int $userId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->update($t,['user_id'=>$userId],['id'=>$personId],['%d'],['%d'])!==false;
	}

	public static function unlinkFromUser(int $personId): bool {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return $wpdb->update($t,['user_id'=>0],['id'=>$personId],['%d'],['%d'])!==false;
	}

	public static function getStats(): array {
		global $wpdb;
		$t=$wpdb->prefix.self::TABLE;
		return [
			'total'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}"),
			'active'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE is_active=1"),
			'linked'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE user_id>0"),
			'by_department'=>$wpdb->get_results("SELECT department,COUNT(*) as cnt FROM {$t} WHERE is_active=1 AND department!='' GROUP BY department ORDER BY cnt DESC",ARRAY_A)
		];
	}

	public static function import(array $persons): array {
		$imported=0;$errors=[];
		foreach($persons as $p){
			if(empty($p['name'])){$errors[]='Nome obrigatório';continue;}
			$id=self::create($p);
			if($id){$imported++;}
			else{$errors[]="Erro ao importar: {$p['name']}";}
		}
		return compact('imported','errors');
	}

	public static function export(): array {
		return self::getAll(false);
	}

	private static function decode(array $row): array {
		$row['social']=json_decode($row['social']??'[]',true)??[];
		$row['skills']=json_decode($row['skills']??'[]',true)??[];
		return $row;
	}
}
