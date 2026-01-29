<?php

declare(strict_types=1);
/**
 * Onboarding Wizard
 * File: template-parts/onboarding/wizard.php
 * REST: GET /onboarding/step, POST /onboarding/profile, POST /onboarding/interests
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$user_id      = get_current_user_id();
$progress     = apollo_get_onboarding_progress( $user_id );
$current_step = $progress['current_step'];
$user         = wp_get_current_user();
?>

<div class="apollo-onboarding" data-step="<?php echo $current_step; ?>">

	<div class="onboarding-progress">
		<div class="progress-bar">
			<div class="progress-fill" style="width: <?php echo ( $current_step / $progress['total_steps'] ) * 100; ?>%"></div>
		</div>
		<span class="step-indicator">Passo <?php echo $current_step + 1; ?> de <?php echo $progress['total_steps']; ?></span>
	</div>

	<!-- Step 0: Welcome -->
	<div class="onboarding-step <?php echo $current_step === 0 ? 'active' : ''; ?>" id="step-0">
		<div class="step-icon"><i class="ri-hand-heart-line"></i></div>
		<h1>Bem-vindo(a), <?php echo esc_html( $user->display_name ); ?>!</h1>
		<p>Vamos configurar seu perfil para você aproveitar ao máximo a comunidade.</p>
		<button class="btn btn-primary btn-lg btn-next" data-step="1" title="Iniciar onboarding">Começar <i class="ri-arrow-right-line"></i></button>
	</div>

	<!-- Step 1: Profile -->
	<div class="onboarding-step <?php echo $current_step === 1 ? 'active' : ''; ?>" id="step-1">
		<div class="step-icon"><i class="ri-user-line"></i></div>
		<h2>Complete seu perfil</h2>
		<form id="form-profile" class="onboarding-form">
			<div class="form-group">
				<label for="avatar-input">Foto de perfil</label>
				<div class="avatar-upload">
					<img src="<?php echo apollo_get_user_avatar( $user_id, 120 ); ?>" id="avatar-preview" alt="Foto de perfil atual">
					<input type="file" name="avatar" id="avatar-input" accept="image/*" title="Selecionar foto de perfil">
					<button type="button" class="btn btn-outline btn-sm" title="Alterar foto de perfil" onclick="document.getElementById('avatar-input').click()">
						<i class="ri-camera-line"></i> Alterar
					</button>
				</div>
			</div>
			<div class="form-group">
				<label for="onboarding-bio">Bio</label>
				<textarea id="onboarding-bio" name="bio" rows="3" placeholder="<?php esc_attr_e( 'Conte um pouco sobre você...', 'apollo' ); ?>" title="Bio do perfil"><?php echo esc_textarea( get_user_meta( $user_id, 'description', true ) ); ?></textarea>
			</div>
			<div class="form-group">
				<label for="onboarding-location">Localização</label>
				<input id="onboarding-location" type="text" name="location" value="<?php echo esc_attr( get_user_meta( $user_id, 'user_location', true ) ); ?>" placeholder="<?php esc_attr_e( 'Cidade, Estado', 'apollo' ); ?>" title="Localização">
			</div>
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
		<div class="step-actions">
			<button class="btn btn-outline btn-back" data-step="0" title="Voltar"><i class="ri-arrow-left-line"></i></button>
			<button class="btn btn-primary btn-next" data-step="2" title="Avançar para interesses">Próximo <i class="ri-arrow-right-line"></i></button>
		</div>
	</div>

	<!-- Step 2: Interests -->
	<div class="onboarding-step <?php echo $current_step === 2 ? 'active' : ''; ?>" id="step-2">
		<div class="step-icon"><i class="ri-heart-line"></i></div>
		<h2>Seus interesses</h2>
		<p>Selecione os temas que te interessam para personalizarmos seu feed.</p>
		<form id="form-interests" class="onboarding-form">
			<div class="interests-grid">
				<?php
				$interests      = array( 'Música Eletrônica', 'House', 'Techno', 'Trance', 'Bass', 'Produção Musical', 'DJing', 'Eventos', 'Festivais', 'Clubes', 'Arte', 'Fotografia', 'Sustentabilidade' );
				$user_interests = get_user_meta( $user_id, 'interests', true ) ?: array();
				foreach ( $interests as $interest ) :
					$interest_id = 'interest-' . sanitize_title( $interest );
					?>
				<label class="interest-tag <?php echo in_array( $interest, $user_interests ) ? 'selected' : ''; ?>" for="<?php echo esc_attr( $interest_id ); ?>">
					<input id="<?php echo esc_attr( $interest_id ); ?>" type="checkbox" name="interests[]" value="<?php echo esc_attr( $interest ); ?>" title="<?php echo esc_attr( $interest ); ?>" <?php checked( in_array( $interest, $user_interests ) ); ?>>
					<span><?php echo esc_html( $interest ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
		<div class="step-actions">
			<button class="btn btn-outline btn-back" data-step="1" title="Voltar"><i class="ri-arrow-left-line"></i></button>
			<button class="btn btn-primary btn-next" data-step="3" title="Avançar para conexões">Próximo <i class="ri-arrow-right-line"></i></button>
		</div>
	</div>

	<!-- Step 3: Connect -->
	<div class="onboarding-step <?php echo $current_step === 3 ? 'active' : ''; ?>" id="step-3">
		<div class="step-icon"><i class="ri-group-line"></i></div>
		<h2>Encontre pessoas</h2>
		<p>Siga algumas pessoas para começar.</p>
		<div class="suggested-users" id="suggested-users">
			<!-- Loaded via JS -->
		</div>
		<div class="step-actions">
			<button class="btn btn-outline btn-back" data-step="2" title="Voltar"><i class="ri-arrow-left-line"></i></button>
			<button class="btn btn-primary btn-next" data-step="4" title="Finalizar onboarding">Próximo <i class="ri-arrow-right-line"></i></button>
		</div>
	</div>

	<!-- Step 4: Done -->
	<div class="onboarding-step <?php echo $current_step === 4 ? 'active' : ''; ?>" id="step-4">
		<div class="step-icon success"><i class="ri-check-double-line"></i></div>
		<h2>Tudo pronto!</h2>
		<p>Seu perfil está configurado. Explore a comunidade!</p>
		<div class="completion-actions">
			<a href="<?php echo home_url( '/feed' ); ?>" class="btn btn-primary btn-lg" title="Ver feed">Ver Feed</a>
			<a href="<?php echo home_url( '/membros' ); ?>" class="btn btn-outline" title="Explorar membros">Explorar Membros</a>
			<a href="<?php echo home_url( '/eventos' ); ?>" class="btn btn-outline" title="Ver eventos">Ver Eventos</a>
		</div>
	</div>

</div>
<script src="https://cdn.apollo.rio.br/"></script>
