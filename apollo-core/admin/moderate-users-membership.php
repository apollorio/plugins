<?php
/**
 * Apollo Core - Membership Management Admin UI
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render membership types manager (admin only)
 */
function apollo_render_membership_types_manager() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$memberships     = apollo_get_memberships();
	$custom_only     = get_option( 'apollo_memberships', array() );
	$defaults        = apollo_get_default_memberships();
	?>
	<div class="apollo-membership-types-manager" style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
		<h2><?php esc_html_e( 'Membership Types Manager', 'apollo-core' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Manage membership types available in the system. Only administrators can create, edit, or delete membership types.', 'apollo-core' ); ?>
		</p>

		<p style="margin-bottom: 15px;">
			<button type="button" class="button button-primary" id="apollo-add-membership-btn">
				<?php esc_html_e( 'Add Membership Type', 'apollo-core' ); ?>
			</button>
			<button type="button" class="button" id="apollo-export-memberships-btn">
				<?php esc_html_e( 'Export JSON', 'apollo-core' ); ?>
			</button>
			<button type="button" class="button" id="apollo-import-memberships-btn">
				<?php esc_html_e( 'Import JSON', 'apollo-core' ); ?>
			</button>
		</p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 40px;"><?php esc_html_e( 'Color', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Label', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Frontend Label', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Text Color', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $memberships as $slug => $data ) : ?>
					<?php
					$is_default = isset( $defaults[ $slug ] );
					$is_protected = 'nao-verificado' === $slug || $is_default;
					?>
					<tr data-membership-slug="<?php echo esc_attr( $slug ); ?>">
						<td>
							<div style="width: 30px; height: 30px; border-radius: 4px; background-color: <?php echo esc_attr( $data['color'] ); ?>; border: 1px solid #ddd;"></div>
						</td>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo esc_html( $data['label'] ); ?></td>
						<td><?php echo esc_html( $data['frontend_label'] ); ?></td>
						<td>
							<div style="display: flex; align-items: center; gap: 8px;">
								<div style="width: 20px; height: 20px; border-radius: 2px; background-color: <?php echo esc_attr( $data['text_color'] ); ?>; border: 1px solid #ddd;"></div>
								<code><?php echo esc_html( $data['text_color'] ); ?></code>
							</div>
						</td>
						<td>
							<?php if ( $is_protected ) : ?>
								<span class="badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
									<?php esc_html_e( 'Default', 'apollo-core' ); ?>
								</span>
							<?php else : ?>
								<span class="badge" style="background: #dba617; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
									<?php esc_html_e( 'Custom', 'apollo-core' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! $is_protected ) : ?>
								<button type="button" class="button button-small apollo-edit-membership-btn" data-slug="<?php echo esc_attr( $slug ); ?>">
									<?php esc_html_e( 'Edit', 'apollo-core' ); ?>
								</button>
								<button type="button" class="button button-small apollo-delete-membership-btn" data-slug="<?php echo esc_attr( $slug ); ?>" style="color: #b32d2e;">
									<?php esc_html_e( 'Delete', 'apollo-core' ); ?>
								</button>
							<?php else : ?>
								<span style="color: #999;">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Add/Edit Membership Modal -->
	<div id="apollo-membership-modal" style="display: none;">
		<div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000; display: flex; align-items: center; justify-content: center;">
			<div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
				<h2 id="apollo-membership-modal-title"><?php esc_html_e( 'Add Membership Type', 'apollo-core' ); ?></h2>
				<form id="apollo-membership-form">
					<input type="hidden" id="membership-action" value="create">
					<input type="hidden" id="membership-original-slug" value="">
					
					<p>
						<label for="membership-slug"><strong><?php esc_html_e( 'Slug', 'apollo-core' ); ?></strong> <span style="color: red;">*</span></label><br>
						<input type="text" id="membership-slug" class="widefat" placeholder="ex: vip-member" required pattern="[a-z0-9\-]+" />
						<span class="description"><?php esc_html_e( 'Lowercase letters, numbers, and hyphens only.', 'apollo-core' ); ?></span>
					</p>

					<p>
						<label for="membership-label"><strong><?php esc_html_e( 'Label', 'apollo-core' ); ?></strong> <span style="color: red;">*</span></label><br>
						<input type="text" id="membership-label" class="widefat" placeholder="<?php esc_attr_e( 'VIP Member', 'apollo-core' ); ?>" required />
						<span class="description"><?php esc_html_e( 'Internal label (admin use).', 'apollo-core' ); ?></span>
					</p>

					<p>
						<label for="membership-frontend-label"><strong><?php esc_html_e( 'Frontend Label', 'apollo-core' ); ?></strong> <span style="color: red;">*</span></label><br>
						<input type="text" id="membership-frontend-label" class="widefat" placeholder="<?php esc_attr_e( 'VIP', 'apollo-core' ); ?>" required />
						<span class="description"><?php esc_html_e( 'Label shown to users on the front-end.', 'apollo-core' ); ?></span>
					</p>

					<p>
						<label for="membership-color"><strong><?php esc_html_e( 'Background Color', 'apollo-core' ); ?></strong> <span style="color: red;">*</span></label><br>
						<input type="color" id="membership-color" value="#9AA0A6" required />
					</p>

					<p>
						<label for="membership-text-color"><strong><?php esc_html_e( 'Text Color', 'apollo-core' ); ?></strong> <span style="color: red;">*</span></label><br>
						<input type="color" id="membership-text-color" value="#6E7376" required />
					</p>

					<div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
						<button type="button" class="button" id="apollo-membership-modal-cancel"><?php esc_html_e( 'Cancel', 'apollo-core' ); ?></button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'apollo-core' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Import Modal -->
	<div id="apollo-import-modal" style="display: none;">
		<div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000; display: flex; align-items: center; justify-content: center;">
			<div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
				<h2><?php esc_html_e( 'Import Memberships', 'apollo-core' ); ?></h2>
				<form id="apollo-import-form">
					<p>
						<label for="import-json"><strong><?php esc_html_e( 'Paste JSON Data', 'apollo-core' ); ?></strong></label><br>
						<textarea id="import-json" rows="12" class="widefat" placeholder='{"version": "1.0.0", "memberships": {...}}' required></textarea>
					</p>

					<div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
						<button type="button" class="button" id="apollo-import-modal-cancel"><?php esc_html_e( 'Cancel', 'apollo-core' ); ?></button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'apollo-core' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render per-user membership selector in Moderate Users tab
 *
 * @param WP_User $user User object.
 */
function apollo_render_user_membership_selector( $user ) {
	$memberships     = apollo_get_memberships();
	$current         = apollo_get_user_membership( $user->ID );
	$can_edit        = current_user_can( 'edit_apollo_users' );
	?>
	<select class="apollo-user-membership-select" data-user-id="<?php echo esc_attr( $user->ID ); ?>" <?php disabled( ! $can_edit ); ?>>
		<?php foreach ( $memberships as $slug => $data ) : ?>
			<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
				<?php echo esc_html( $data['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Enqueue membership admin scripts
 *
 * @param string $hook Current admin page hook.
 */
function apollo_enqueue_membership_admin_assets( $hook ) {
	if ( 'toplevel_page_apollo-moderation' !== $hook ) {
		return;
	}

	wp_add_inline_script(
		'apollo-moderation-admin',
		'
		jQuery(document).ready(function($) {
			const restUrl = "' . esc_url( rest_url( 'apollo/v1/' ) ) . '";
			const nonce = "' . wp_create_nonce( 'wp_rest' ) . '";
			const canManage = ' . ( current_user_can( 'manage_options' ) ? 'true' : 'false' ) . ';

			// Add membership button
			$("#apollo-add-membership-btn").on("click", function() {
				$("#membership-action").val("create");
				$("#membership-original-slug").val("");
				$("#membership-slug").val("").prop("disabled", false);
				$("#membership-label").val("");
				$("#membership-frontend-label").val("");
				$("#membership-color").val("#9AA0A6");
				$("#membership-text-color").val("#6E7376");
				$("#apollo-membership-modal-title").text("' . esc_js( __( 'Add Membership Type', 'apollo-core' ) ) . '");
				$("#apollo-membership-modal").show();
			});

			// Edit membership button
			$(".apollo-edit-membership-btn").on("click", function() {
				const slug = $(this).data("slug");
				const row = $("tr[data-membership-slug=\"" + slug + "\"]");
				
				$("#membership-action").val("update");
				$("#membership-original-slug").val(slug);
				$("#membership-slug").val(slug).prop("disabled", true);
				$("#membership-label").val(row.find("td:eq(2)").text());
				$("#membership-frontend-label").val(row.find("td:eq(3)").text());
				$("#membership-color").val(row.find("td:eq(0) div").css("background-color"));
				$("#membership-text-color").val(row.find("td:eq(4) div").css("background-color"));
				$("#apollo-membership-modal-title").text("' . esc_js( __( 'Edit Membership Type', 'apollo-core' ) ) . '");
				$("#apollo-membership-modal").show();
			});

			// Cancel modal
			$("#apollo-membership-modal-cancel").on("click", function() {
				$("#apollo-membership-modal").hide();
			});

			// Submit membership form
			$("#apollo-membership-form").on("submit", function(e) {
				e.preventDefault();
				
				const action = $("#membership-action").val();
				const slug = $("#membership-slug").val();
				const label = $("#membership-label").val();
				const frontendLabel = $("#membership-frontend-label").val();
				const color = $("#membership-color").val();
				const textColor = $("#membership-text-color").val();

				let endpoint = restUrl + "memberships/create";
				let data = {
					slug: slug,
					label: label,
					frontend_label: frontendLabel,
					color: color,
					text_color: textColor
				};

				if (action === "update") {
					endpoint = restUrl + "memberships/update";
					data.slug = $("#membership-original-slug").val();
				}

				$.ajax({
					url: endpoint,
					method: "POST",
					headers: { "X-WP-Nonce": nonce },
					data: data,
					beforeSend: function() {
						$("#apollo-membership-form button[type=submit]").prop("disabled", true).text("' . esc_js( __( 'Saving...', 'apollo-core' ) ) . '");
					},
					success: function(response) {
						alert(response.message || "' . esc_js( __( 'Membership saved successfully', 'apollo-core' ) ) . '");
						location.reload();
					},
					error: function(xhr) {
						alert(xhr.responseJSON?.message || "' . esc_js( __( 'Error saving membership', 'apollo-core' ) ) . '");
						$("#apollo-membership-form button[type=submit]").prop("disabled", false).text("' . esc_js( __( 'Save', 'apollo-core' ) ) . '");
					}
				});
			});

			// Delete membership
			$(".apollo-delete-membership-btn").on("click", function() {
				const slug = $(this).data("slug");
				
				if (!confirm("' . esc_js( __( 'Are you sure you want to delete this membership type? All users with this membership will be reassigned to Não Verificado.', 'apollo-core' ) ) . '")) {
					return;
				}

				$.ajax({
					url: restUrl + "memberships/delete",
					method: "POST",
					headers: { "X-WP-Nonce": nonce },
					data: { slug: slug },
					success: function(response) {
						alert(response.message || "' . esc_js( __( 'Membership deleted successfully', 'apollo-core' ) ) . '");
						location.reload();
					},
					error: function(xhr) {
						alert(xhr.responseJSON?.message || "' . esc_js( __( 'Error deleting membership', 'apollo-core' ) ) . '");
					}
				});
			});

			// Export memberships
			$("#apollo-export-memberships-btn").on("click", function() {
				$.ajax({
					url: restUrl + "memberships/export",
					method: "GET",
					headers: { "X-WP-Nonce": nonce },
					success: function(response) {
						const dataStr = response.data;
						const dataBlob = new Blob([dataStr], {type: "application/json"});
						const url = URL.createObjectURL(dataBlob);
						const link = document.createElement("a");
						link.href = url;
						link.download = "apollo-memberships-" + new Date().toISOString().split("T")[0] + ".json";
						link.click();
					},
					error: function() {
						alert("' . esc_js( __( 'Error exporting memberships', 'apollo-core' ) ) . '");
					}
				});
			});

			// Import button
			$("#apollo-import-memberships-btn").on("click", function() {
				$("#apollo-import-modal").show();
			});

			// Cancel import
			$("#apollo-import-modal-cancel").on("click", function() {
				$("#apollo-import-modal").hide();
			});

			// Submit import
			$("#apollo-import-form").on("submit", function(e) {
				e.preventDefault();
				
				const json = $("#import-json").val();

				$.ajax({
					url: restUrl + "memberships/import",
					method: "POST",
					headers: { "X-WP-Nonce": nonce },
					data: { data: json },
					beforeSend: function() {
						$("#apollo-import-form button[type=submit]").prop("disabled", true).text("' . esc_js( __( 'Importing...', 'apollo-core' ) ) . '");
					},
					success: function(response) {
						alert(response.message || "' . esc_js( __( 'Import successful', 'apollo-core' ) ) . '");
						location.reload();
					},
					error: function(xhr) {
						alert(xhr.responseJSON?.message || "' . esc_js( __( 'Error importing memberships', 'apollo-core' ) ) . '");
						$("#apollo-import-form button[type=submit]").prop("disabled", false).text("' . esc_js( __( 'Import', 'apollo-core' ) ) . '");
					}
				});
			});

			// User membership select change
			$(".apollo-user-membership-select").on("change", function() {
				const select = $(this);
				const userId = select.data("user-id");
				const membership = select.val();

				if (!confirm("' . esc_js( __( 'Change membership for this user?', 'apollo-core' ) ) . '")) {
					select.val(select.find("option[selected]").val());
					return;
				}

				$.ajax({
					url: restUrl + "memberships/set",
					method: "POST",
					headers: { "X-WP-Nonce": nonce },
					data: {
						user_id: userId,
						membership_slug: membership
					},
					success: function(response) {
						alert(response.message || "' . esc_js( __( 'Membership updated', 'apollo-core' ) ) . '");
						location.reload();
					},
					error: function(xhr) {
						alert(xhr.responseJSON?.message || "' . esc_js( __( 'Error updating membership', 'apollo-core' ) ) . '");
						select.val(select.find("option[selected]").val());
					}
				});
			});
		});
		'
	);
}
add_action( 'admin_enqueue_scripts', 'apollo_enqueue_membership_admin_assets' );


