Testing PWA Detection
Desktop Browser:

All templates show content normally

Mobile Browser (Chrome/Safari):

Visit page with App::rio or App::rio clean
Should see "Open our app" message
Click "here" → accordion opens
iOS instructions shown in detail

Mobile PWA Mode:

Install app (Add to Home Screen)
Open from home screen icon
All templates show content normally


🔍 TROUBLESHOOTING
Issue: PWA detection not working
Solution:
php// Check if cookie is set
var_dump($_COOKIE['apollo_display_mode']);

// Check JavaScript detection
console.log(window.apolloPWA.isPWA());
Issue: Templates not appearing in dropdown
Solution:

Check file names match exactly: pagx_site.php (not pagx-site.php)
Clear cache: wp cache flush
Re-save permalinks: Settings → Permalinks → Save

Issue: Header/Footer not loading
Solution:
php// Check template path
echo plugin_dir_path(__FILE__) . 'templates/partials/header.php';

// Verify file exists
if (file_exists($file)) {
    echo "File found";
}

🎨 CUSTOMIZATION
Change PWA Install Page Text
Edit in includes/class-pwa-page-builders.php, function apollo_render_pwa_install_page():
php<h1 class="apollo-pwa-title">
    <?php _e('Customize this message', 'apollo-rio'); ?>
</h1>
Add Custom Styles
Create assets/css/custom.css:
css.pagx-site .apollo-entry-title {
    color: #ff6600;
}

.pagx-appclean .apollo-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
Enqueue in includes/class-pwa-page-builders.php:
phpwp_enqueue_style(
    'apollo-custom',
    APOLLO_URL . 'assets/css/custom.css',
    ['apollo-pwa-templates'],
    APOLLO_VERSION
);

📊 COMPARISON TABLE
FeatureSite::rioApp::rioApp::rio cleanNavigation Bar✅ Yes✅ Yes❌ NoFooter Widgets✅ Yes✅ Yes❌ NoDesktop Content✅ Always✅ Always✅ AlwaysMobile Browser✅ Content⚠️ Install⚠️ InstallMobile PWA✅ Content✅ Content✅ ContentSEO Friendly✅ Yes⚠️ Partial❌ NoUse CasePublic pagesMember areaApp views

✅ CHECKLIST

 Plugin activated
 Templates appear in dropdown
 Android URL configured in settings
 PWA manifest.json created
 App icons uploaded (192x192, 512x512)
 Test desktop: content shows
 Test mobile browser: install page shows (App::rio)
 Test mobile PWA: content shows
 Navigation menus assigned
 Footer widgets configured
 Elementor compatibility tested


🔒 SECURITY NOTES
All templates:

✅ Use esc_url() for URLs
✅ Use esc_attr() for attributes
✅ Use __() for translations
✅ Check defined('ABSPATH') at top
✅ Use wp_nonce for forms
✅ Sanitize all user input