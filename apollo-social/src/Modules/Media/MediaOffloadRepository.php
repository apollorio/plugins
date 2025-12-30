<?php
declare(strict_types=1);
namespace Apollo\Modules\Media;

final class MediaOffloadRepository {
	private const TABLE='apollo_media_offload';

	public static function register(int $attachmentId,string $provider,string $remoteUrl,array $meta=[]): bool {
		global $wpdb;
		return $wpdb->replace($wpdb->prefix.self::TABLE,[
			'attachment_id'=>$attachmentId,
			'provider'=>sanitize_key($provider),
			'remote_url'=>esc_url_raw($remoteUrl),
			'remote_path'=>sanitize_text_field($meta['remote_path']??''),
			'file_size'=>isset($meta['file_size'])?(int)$meta['file_size']:null,
			'mime_type'=>sanitize_mime_type($meta['mime_type']??''),
			'is_synced'=>1,
			'meta'=>!empty($meta)?wp_json_encode($meta):null
		])!==false;
	}

	public static function get(int $attachmentId): ?array {
		global $wpdb;
		$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE attachment_id=%d",$attachmentId),ARRAY_A);
		if($r)$r['meta']=json_decode($r['meta']??'',true);
		return $r?:null;
	}

	public static function getRemoteUrl(int $attachmentId): ?string {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT remote_url FROM {$wpdb->prefix}".self::TABLE." WHERE attachment_id=%d AND is_synced=1",$attachmentId));
	}

	public static function isOffloaded(int $attachmentId): bool {
		global $wpdb;
		return (bool)$wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}".self::TABLE." WHERE attachment_id=%d AND is_synced=1",$attachmentId));
	}

	public static function markUnsync(int $attachmentId): bool {
		global $wpdb;
		return $wpdb->update($wpdb->prefix.self::TABLE,['is_synced'=>0],['attachment_id'=>$attachmentId])!==false;
	}

	public static function delete(int $attachmentId): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->prefix.self::TABLE,['attachment_id'=>$attachmentId])!==false;
	}

	public static function getByProvider(string $provider,int $limit=100,int $offset=0): array {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE provider=%s ORDER BY synced_at DESC LIMIT %d OFFSET %d",
			$provider,$limit,$offset
		),ARRAY_A)?:[];
	}

	public static function getTotalSize(?string $provider=null): int {
		global $wpdb;
		$w=$provider?"WHERE provider='{$wpdb->_real_escape($provider)}'":'';
		return (int)$wpdb->get_var("SELECT SUM(file_size) FROM {$wpdb->prefix}".self::TABLE." {$w}");
	}

	public static function countOffloaded(?string $provider=null): int {
		global $wpdb;
		$w=$provider?"WHERE provider='{$wpdb->_real_escape($provider)}'":'';
		return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." {$w}");
	}

	public static function filterAttachmentUrl(string $url,int $attachmentId): string {
		$remote=self::getRemoteUrl($attachmentId);
		return $remote?:$url;
	}
}
