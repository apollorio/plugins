<?php
/**
 * Documents Sync Hooks
 *
 * FASE 8: Hooks idempotentes para sincronização CPT ↔ Índice
 *
 * Hooks registrados:
 * - save_post_apollo_document: upsert no índice
 * - trashed_post: refletir no índice
 * - before_delete_post: limpar do índice
 * - untrashed_post: restaurar no índice
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

use Apollo\Infrastructure\ApolloLogger;

/**
 * Documents Sync Hooks - Maintains consistency between CPT and index table
 */
class DocumentsSyncHooks {

	/** @var bool Prevent recursive saves */
	private static bool $syncing = false;

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Save hook
		add_action( 'save_post_apollo_document', array( __CLASS__, 'onSaveDocument' ), 20, 3 );

		// Trash hooks
		add_action( 'trashed_post', array( __CLASS__, 'onTrashDocument' ), 10, 1 );
		add_action( 'untrashed_post', array( __CLASS__, 'onUntrashDocument' ), 10, 1 );

		// Delete hook
		add_action( 'before_delete_post', array( __CLASS__, 'onDeleteDocument' ), 10, 1 );

		// Status change hook
		add_action( 'transition_post_status', array( __CLASS__, 'onStatusChange' ), 10, 3 );
	}

	/**
	 * Hook: save_post_apollo_document
	 *
	 * Syncs document to index table on save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public static function onSaveDocument( int $post_id, $post, bool $update ): void {
		// Prevent recursion
		if ( self::$syncing ) {
			return;
		}

		// Skip autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip revisions
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Skip trash
		if ( $post->post_status === 'trash' ) {
			return;
		}

		self::$syncing = true;

		try {
			// Ensure file_id exists
			$file_id = get_post_meta( $post_id, '_apollo_doc_file_id', true );
			if ( empty( $file_id ) ) {
				$file_id = wp_generate_uuid4();
				update_post_meta( $post_id, '_apollo_doc_file_id', $file_id );
			}

			// Ensure state exists
			$state = get_post_meta( $post_id, '_apollo_doc_state', true );
			if ( empty( $state ) ) {
				$state = 'draft';
				update_post_meta( $post_id, '_apollo_doc_state', $state );
			}

			// Sync to index
			DocumentsRepository::syncToIndex( $post_id );

			if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
				ApolloLogger::debug(
					'document_synced',
					array(
						'post_id' => $post_id,
						'update'  => $update,
						'state'   => $state,
					),
					ApolloLogger::CAT_SYNC
				);
			}
		} finally {
			self::$syncing = false;
		}
	}

	/**
	 * Hook: trashed_post
	 *
	 * Updates index when document is trashed.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function onTrashDocument( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'apollo_documents';

		// Update status in index
		$wpdb->update(
			$table,
			array(
				'status'     => 'trashed',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'post_id' => $post_id )
		);

		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info(
				'document_trashed',
				array(
					'post_id' => $post_id,
				),
				ApolloLogger::CAT_DOCUMENT
			);
		}
	}

	/**
	 * Hook: untrashed_post
	 *
	 * Restores document in index when untrashed.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function onUntrashDocument( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return;
		}

		// Get Apollo state from meta
		$state = get_post_meta( $post_id, '_apollo_doc_state', true ) ?: 'draft';

		global $wpdb;

		$table = $wpdb->prefix . 'apollo_documents';

		// Restore status in index
		$wpdb->update(
			$table,
			array(
				'status'     => $state,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'post_id' => $post_id )
		);

		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info(
				'document_untrashed',
				array(
					'post_id'        => $post_id,
					'restored_state' => $state,
				),
				ApolloLogger::CAT_DOCUMENT
			);
		}
	}

	/**
	 * Hook: before_delete_post
	 *
	 * Removes document from index before permanent deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function onDeleteDocument( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return;
		}

		global $wpdb;

		$docs_table = $wpdb->prefix . 'apollo_documents';
		$sigs_table = $wpdb->prefix . 'apollo_document_signatures';

		// Delete from index
		$wpdb->delete( $docs_table, array( 'post_id' => $post_id ) );

		// Delete signatures (by post_id)
		$wpdb->delete( $sigs_table, array( 'post_id' => $post_id ) );

		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::warning(
				'document_deleted',
				array(
					'post_id' => $post_id,
				),
				ApolloLogger::CAT_DOCUMENT
			);
		}
	}

	/**
	 * Hook: transition_post_status
	 *
	 * Syncs Apollo state when WP status changes.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public static function onStatusChange( string $new_status, string $old_status, $post ): void {
		if ( $post->post_type !== 'apollo_document' ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		// Prevent recursion
		if ( self::$syncing ) {
			return;
		}

		// Log status change
		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::debug(
				'document_wp_status_changed',
				array(
					'post_id'    => $post->ID,
					'old_status' => $old_status,
					'new_status' => $new_status,
				),
				ApolloLogger::CAT_DOCUMENT
			);
		}
	}
}
