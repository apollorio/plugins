<?php
/**
 * DJ Contacts Table Template
 * WordPress-compatible template with dynamic data
 */
?>

<div class="container p-10" style="max-width:1180px;margin:auto;">
	<div class="glass-table-card glass">

	<!-- Header -->
	<div class="table-header">
		<h3><?php echo esc_html( $title ?? 'DJ Contacts' ); ?></h3>
	</div>

	<!-- Table -->
	<div class="table-wrapper">
		<table class="table">
		<thead>
			<tr>
			<th><?php _e( 'Name', 'apollo-social' ); ?></th>
			<th><?php _e( 'Role', 'apollo-social' ); ?></th>
			<th><?php _e( 'Email', 'apollo-social' ); ?></th>
			<th><?php _e( 'Phone', 'apollo-social' ); ?></th>
			<th><?php _e( 'Score', 'apollo-social' ); ?></th>
			<th><?php _e( 'Platform', 'apollo-social' ); ?></th>
			<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $contacts ) && is_array( $contacts ) ) : ?>
				<?php foreach ( $contacts as $contact ) : ?>
				<tr>
				<td>
					<div style="display:flex;align-items:center;gap:.75rem;">
					<img src="<?php echo esc_url( $contact['avatar'] ?? '' ); ?>" class="avatar" alt="<?php echo esc_attr( $contact['name'] ?? '' ); ?>">
					<a href="<?php echo esc_url( $contact['profile_url'] ?? '#' ); ?>" style="color:var(--text-primary);font-weight:600;">
						<?php echo esc_html( $contact['name'] ?? '' ); ?>
					</a>
					</div>
				</td>
				<td><?php echo esc_html( $contact['role'] ?? '' ); ?></td>
				<td>
					<a href="mailto:<?php echo esc_attr( $contact['email'] ?? '' ); ?>" style="color:var(--text-main);">
					<?php echo esc_html( $contact['email'] ?? '' ); ?>
					</a>
				</td>
				<td>
					<a href="tel:<?php echo esc_attr( $contact['phone'] ?? '' ); ?>" style="color:var(--text-main);">
					<?php echo esc_html( $contact['phone'] ?? '' ); ?>
					</a>
				</td>
				<td>
					<span class="badge badge-<?php echo esc_attr( get_score_class( $contact['score'] ?? 0 ) ); ?>">
					<?php echo esc_html( ( $contact['score'] ?? 0 ) . '/10' ); ?>
					</span>
				</td>
				<td>
					<a href="<?php echo esc_url( $contact['platform_url'] ?? '#' ); ?>" style="color:var(--text-primary);">
					<?php echo esc_html( $contact['platform'] ?? '' ); ?>
					</a>
				</td>
				<td class="gear-cell" style="position:relative;">
					<div class="gear-btn">
					<i class="ri-settings-6-line"></i>
					</div>
					<div class="gear-menu glass">
					<a href="<?php echo esc_url( $contact['profile_url'] ?? '#' ); ?>" class="gear-item">
						<i class="ri-user-line"></i> <?php _e( 'View Profile', 'apollo-social' ); ?>
					</a>
					<a href="<?php echo esc_url( $contact['message_url'] ?? '#' ); ?>" class="gear-item">
						<i class="ri-message-3-line"></i> <?php _e( 'Send Message', 'apollo-social' ); ?>
					</a>
					<a href="#" class="gear-item" onclick="return confirm('<?php _e( 'Are you sure you want to remove this contact?', 'apollo-social' ); ?>');">
						<i class="ri-delete-bin-line"></i> <?php _e( 'Remove', 'apollo-social' ); ?>
					</a>
					</div>
				</td>
				</tr>
			<?php endforeach; ?>
			<?php else : ?>
			<!-- Sample data for demonstration -->
			<tr>
				<td>
				<div style="display:flex;align-items:center;gap:.75rem;">
					<img src="https://images.unsplash.com/photo-1502823403499-6ccfcf4fb453?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80" class="avatar" alt="Robert">
					<a href="#" style="color:var(--text-primary);font-weight:600;">Robert Fox</a>
				</div>
				</td>
				<td>DJ/Producer</td>
				<td><a href="mailto:robert.fox@example.com" style="color:var(--text-main);">robert.fox@example.com</a></td>
				<td><a href="tel:202-555-0152" style="color:var(--text-main);">202-555-0152</a></td>
				<td><span class="badge badge-success">7/10</span></td>
				<td><a href="#" style="color:var(--text-primary);">SoundCloud</a></td>
				<td class="gear-cell" style="position:relative;">
				<div class="gear-btn">
					<i class="ri-settings-6-line"></i>
				</div>
				<div class="gear-menu glass">
					<a href="#" class="gear-item"><i class="ri-user-line"></i> View Profile</a>
					<a href="#" class="gear-item"><i class="ri-message-3-line"></i> Send Message</a>
					<a href="#" class="gear-item"><i class="ri-delete-bin-line"></i> Remove</a>
				</div>
				</td>
			</tr>

			<tr>
				<td>
				<div style="display:flex;align-items:center;gap:.75rem;">
					<img src="https://images.unsplash.com/photo-1610271340738-726e199f0258?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80" class="avatar" alt="Darlene">
					<a href="#" style="color:var(--text-primary);font-weight:600;">Darlene Robertson</a>
				</div>
				</td>
				<td>Event Promoter</td>
				<td><a href="mailto:darlene@example.com" style="color:var(--text-main);">darlene@example.com</a></td>
				<td><a href="tel:224-567-2662" style="color:var(--text-main);">224-567-2662</a></td>
				<td><span class="badge badge-warning">5/10</span></td>
				<td><a href="#" style="color:var(--text-primary);">Instagram</a></td>
				<td class="gear-cell" style="position:relative;">
				<div class="gear-btn">
					<i class="ri-settings-6-line"></i>
				</div>
				<div class="gear-menu glass">
					<a href="#" class="gear-item"><i class="ri-user-line"></i> View Profile</a>
					<a href="#" class="gear-item"><i class="ri-message-3-line"></i> Send Message</a>
					<a href="#" class="gear-item"><i class="ri-delete-bin-line"></i> Remove</a>
				</div>
				</td>
			</tr>

			<tr>
				<td>
				<div style="display:flex;align-items:center;gap:.75rem;">
					<img src="https://images.unsplash.com/photo-1610878722345-79c5eaf6a48c?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80" class="avatar" alt="Theresa">
					<a href="#" style="color:var(--text-primary);font-weight:600;">Theresa Webb</a>
				</div>
				</td>
				<td>Club Manager</td>
				<td><a href="mailto:theresa@example.com" style="color:var(--text-main);">theresa@example.com</a></td>
				<td><a href="tel:401-505-6800" style="color:var(--text-main);">401-505-6800</a></td>
				<td><span class="badge badge-danger">2/10</span></td>
				<td><a href="#" style="color:var(--text-primary);">Facebook</a></td>
				<td class="gear-cell" style="position:relative;">
				<div class="gear-btn">
					<i class="ri-settings-6-line"></i>
				</div>
				<div class="gear-menu glass">
					<a href="#" class="gear-item"><i class="ri-user-line"></i> View Profile</a>
					<a href="#" class="gear-item"><i class="ri-message-3-line"></i> Send Message</a>
					<a href="#" class="gear-item"><i class="ri-delete-bin-line"></i> Remove</a>
				</div>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
		</table>
	</div>
	</div>
</div>

<?php
/**
 * Helper method to get score badge class
 */
if ( ! function_exists( 'get_score_class' ) ) {
	function get_score_class( $score ) {
		if ( $score >= 7 ) {
			return 'success';
		}
		if ( $score >= 4 ) {
			return 'warning';
		}
		return 'danger';
	}
}
?>
