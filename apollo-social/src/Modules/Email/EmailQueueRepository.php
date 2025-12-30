<?php
declare(strict_types=1);
namespace Apollo\Modules\Email;

final class EmailQueueRepository {
	private const TABLE='apollo_email_queue';

	public static function enqueue(array $d): int {
		global $wpdb;
		$wpdb->insert($wpdb->prefix.self::TABLE,[
			'recipient_id'=>isset($d['recipient_id'])?(int)$d['recipient_id']:null,
			'recipient_email'=>sanitize_email($d['recipient_email']),
			'subject'=>sanitize_text_field($d['subject']),
			'body'=>$d['body'],
			'template'=>sanitize_key($d['template']??'default'),
			'priority'=>(int)($d['priority']??5),
			'scheduled_at'=>$d['scheduled_at']??current_time('mysql'),
			'meta'=>isset($d['meta'])?wp_json_encode($d['meta']):null,
			'status'=>'pending'
		]);
		return (int)$wpdb->insert_id;
	}

	public static function get(int $id): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id),ARRAY_A);
		if($r)$r['meta']=json_decode($r['meta']??'',true);
		return $r?:null;
	}

	public static function getPending(int $limit=50): array {
		global $wpdb;
		$now=current_time('mysql');
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE status='pending' AND scheduled_at<=%s ORDER BY priority DESC,scheduled_at LIMIT %d",
			$now,$limit
		),ARRAY_A)?:[];
	}

	public static function markSent(int $id): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['status'=>'sent','sent_at'=>current_time('mysql')],['id'=>$id])!==false;
	}

	public static function markFailed(int $id,string $error=''): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,[
			'status'=>'failed',
			'attempts'=>$wpdb->get_var($wpdb->prepare("SELECT attempts FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id))+1,
			'error_message'=>sanitize_text_field($error)
		],['id'=>$id])!==false;
	}

	public static function retry(int $id): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['status'=>'pending','error_message'=>null],['id'=>$id])!==false;
	}

	public static function process(int $batchSize=20): array {
		$results=['sent'=>0,'failed'=>0];
		$emails=self::getPending($batchSize);
		foreach($emails as $email){
			$meta=json_decode($email['meta']??'',true)?:[];
			$headers=['Content-Type: text/html; charset=UTF-8'];
			if(!empty($meta['from_name'])&&!empty($meta['from_email'])){
				$headers[]='From: '.sanitize_text_field($meta['from_name']).' <'.sanitize_email($meta['from_email']).'>';
			}
			$sent=wp_mail($email['recipient_email'],$email['subject'],$email['body'],$headers);
			if($sent){
				self::markSent((int)$email['id']);
				$results['sent']++;
			}else{
				global $phpmailer;
				$error=$phpmailer->ErrorInfo??'Unknown error';
				self::markFailed((int)$email['id'],$error);
				$results['failed']++;
			}
		}
		return $results;
	}

	public static function cleanup(int $daysOld=30): int {
		global $wpdb;
		return (int)$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}".self::TABLE." WHERE status='sent' AND sent_at<DATE_SUB(NOW(),INTERVAL %d DAY)",$daysOld
		));
	}

	public static function countByStatus(): array {
		global $wpdb;
		$rows=$wpdb->get_results("SELECT status,COUNT(*) as cnt FROM {$wpdb->prefix}".self::TABLE." GROUP BY status",ARRAY_A)?:[];
		$result=[];
		foreach($rows as $r){$result[$r['status']]=(int)$r['cnt'];}
		return $result;
	}
}
