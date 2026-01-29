<?php
/**
 * Tab: Settings
 * File: template-parts/user/tab-settings.php
 */

$user_id         = get_current_user_id();
$current_user    = wp_get_current_user();
$user_bio        = get_user_meta( $user_id, 'description', true );
$user_location   = get_user_meta( $user_id, 'user_location', true );
$privacy_profile = get_user_meta( $user_id, 'privacy_profile', true ) ?: 'public';
$notify_events   = get_user_meta( $user_id, 'notify_events', true ) !== 'no';
$notify_messages = get_user_meta( $user_id, 'notify_messages', true ) !== 'no';
$notify_docs     = get_user_meta( $user_id, 'notify_docs', true ) !== 'no';
?>

<div class="section-header">
	<div class="section-title">Configurações da Conta</div>
</div>

<div class="settings-container">
	<form id="user-settings-form" method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'apollo_user_settings', 'settings_nonce' ); ?>
		<input type="hidden" name="action" value="apollo_update_user_settings">

		<!-- Profile Settings -->
		<div class="settings-section">
			<h3 class="settings-section-title"><i class="ri-user-line"></i> Perfil</h3>

			<div class="settings-field">
				<label for="display_name">Nome de exibição</label>
				<input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $current_user->display_name ); ?>" required>
			</div>

			<div class="settings-field">
				<label for="user_email">Email</label>
				<input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
			</div>

			<div class="settings-field">
				<label for="user_bio">Bio</label>
				<textarea id="user_bio" name="user_bio" rows="3" maxlength="200"><?php echo esc_textarea( $user_bio ); ?></textarea>
				<span class="field-hint">Máximo 200 caracteres</span>
			</div>

			<div class="settings-field">
				<label for="user_location">Localização</label>
				<input type="text" id="user_location" name="user_location" value="<?php echo esc_attr( $user_location ); ?>" placeholder="<?php esc_attr_e( 'Ex: Copacabana, RJ', 'apollo' ); ?>">
			</div>

			<div class="settings-field">
				<label for="user_avatar">Avatar</label>
				<input type="file" id="user_avatar" name="user_avatar" accept="image/*">
				<span class="field-hint">JPG, PNG ou GIF. Máximo 2MB.</span>
			</div>
		</div>

		<!-- Privacy Settings -->
		<div class="settings-section">
			<h3 class="settings-section-title"><i class="ri-lock-2-line"></i> Privacidade</h3>

			<div class="settings-field">
				<label for="privacy_profile">Visibilidade do perfil</label>
				<select id="privacy_profile" name="privacy_profile">
					<option value="public" <?php selected( $privacy_profile, 'public' ); ?>>Público</option>
					<option value="members" <?php selected( $privacy_profile, 'members' ); ?>>Apenas membros Apollo</option>
					<option value="private" <?php selected( $privacy_profile, 'private' ); ?>>Privado</option>
				</select>
			</div>
		</div>

		<!-- Notification Settings -->
		<div class="settings-section">
			<h3 class="settings-section-title"><i class="ri-notification-3-line"></i> Notificações</h3>

			<div class="settings-field-checkbox">
				<label>
					<input type="checkbox" name="notify_events" value="yes" <?php checked( $notify_events ); ?>>
					<span>Notificar sobre eventos</span>
				</label>
			</div>

			<div class="settings-field-checkbox">
				<label>
					<input type="checkbox" name="notify_messages" value="yes" <?php checked( $notify_messages ); ?>>
					<span>Notificar sobre mensagens</span>
				</label>
			</div>

			<div class="settings-field-checkbox">
				<label>
					<input type="checkbox" name="notify_docs" value="yes" <?php checked( $notify_docs ); ?>>
					<span>Notificar sobre documentos</span>
				</label>
			</div>
		</div>

		<!-- Password Change -->
		<div class="settings-section">
			<h3 class="settings-section-title"><i class="ri-key-2-line"></i> Senha</h3>

			<div class="settings-field">
				<label for="current_password">Senha atual</label>
				<input type="password" id="current_password" name="current_password" autocomplete="current-password">
			</div>

			<div class="settings-field">
				<label for="new_password">Nova senha</label>
				<input type="password" id="new_password" name="new_password" autocomplete="new-password">
			</div>

			<div class="settings-field">
				<label for="confirm_password">Confirmar nova senha</label>
				<input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
			</div>
		</div>

		<div class="settings-actions">
			<button type="submit" class="btn-full"><i class="ri-save-line"></i> Salvar Alterações</button>
			<div id="settings-message" class="settings-message"></div>
		</div>
	</form>

	<!-- Danger Zone -->
	<div class="settings-section danger-zone">
		<h3 class="settings-section-title"><i class="ri-alert-line"></i> Zona de Perigo</h3>
		<p class="danger-desc">Ações irreversíveis que afetam permanentemente sua conta.</p>
		<button type="button" class="btn-danger" onclick="if(confirm('Tem certeza que deseja deletar sua conta? Esta ação é irreversível.')) { window.location.href='<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=apollo_delete_account' ), 'delete_account' ); ?>'; }">
			<i class="ri-delete-bin-line"></i> Deletar Conta
		</button>
	</div>
</div>
