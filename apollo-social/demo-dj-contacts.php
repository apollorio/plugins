<?php
/**
 * DJ Contacts Table Demo
 * Standalone demo page for the glassmorphism DJ contacts table
 */

// Include WordPress environment
require_once '../../../../wp-load.php';

// Enqueue assets
wp_enqueue_style('uni-css', 'https://assets.apollo.rio.br/uni.css');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css');
wp_enqueue_style('dj-contacts-table', plugin_dir_url(__FILE__) . '../assets/css/dj-contacts-table.css');

get_header();
?>

<div class="container p-10" style="max-width:1180px;margin:auto;">
  <div class="glass-table-card glass">

    <!-- Header -->
    <div class="table-header">
      <h3>DJ Contacts - Production Ready</h3>
      <p style="margin:0.5rem 0 0 0;color:var(--text-main);font-size:0.9rem;">
        Glassmorphism table with sticky gear column, hover dropdowns, and uni.css compatibility
      </p>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Score</th>
            <th>Platform</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
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
        </tbody>
      </table>
    </div>

    <!-- Usage Instructions -->
    <div style="padding:1.5rem;border-top:1px solid var(--border-color);background:rgba(255,255,255,.02);">
      <h4 style="margin:0 0 1rem 0;color:var(--text-primary);">How to Use</h4>
      <div style="background:rgba(0,0,0,.2);padding:1rem;border-radius:0.5rem;font-family:monospace;font-size:0.85rem;">
        <div style="color:var(--accent-color);margin-bottom:0.5rem;">// WordPress Shortcode</div>
        <div>[apollo_dj_contacts title="My DJ Network"]</div>

        <div style="color:var(--accent-color);margin:1rem 0 0.5rem 0;">// PHP Template Include</div>
        <div>$table = new \Apollo\Admin\DJContactsTable();</div>
        <div>$table->renderTable(['title' => 'Custom Title']);</div>

        <div style="color:var(--accent-color);margin:1rem 0 0.5rem 0;">// CSS Classes Available</div>
        <div>.glass-table-card, .table, .avatar, .badge-success/warning/danger</div>
      </div>
    </div>
  </div>
</div>

<?php
get_footer();
?>