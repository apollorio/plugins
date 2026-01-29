<?php
/**
 * Template: Apollo User Page Linktree Editor (Public)
 * Purpose: Public-facing builder for user_page CPT with mobile-first Linktree layout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user_id = get_query_var( 'apollo_user_id' );
if ( ! $user_id ) {
    wp_safe_redirect( home_url() );
    exit;
}

$user = get_userdata( $user_id );
if ( ! $user ) {
    wp_safe_redirect( home_url() );
    exit;
}

$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );
if ( ! $post_id ) {
    wp_safe_redirect( home_url() );
    exit;
}

// Permissions: only owner or editors can modify
if ( get_current_user_id() !== (int) $user_id && ! current_user_can( 'edit_post', $post_id ) ) {
    wp_safe_redirect( home_url() );
    exit;
}

$default_state = array(
    'profile' => array(
        'name'    => 'Apollo::rio',
        'bio'     => 'Connecting culture, music & design in Rio de Janeiro.',
        'avatar'  => 'https://images.unsplash.com/photo-1599566150163-29194dcaad36?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80',
        'bg'      => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'primary' => '#18181b',
        'accent'  => '#F97316',
    ),
    'blocks'  => array(
        array(
            'id'   => 'b0',
            'type' => 'header',
            'text' => 'Destaques',
        ),
        array(
            'id'    => 'b1',
            'type'  => 'link',
            'title' => 'Official Website',
            'sub'   => 'apollo.rio.br',
            'url'   => '#',
            'icon'  => 'globe-s',
        ),
        array(
            'id'    => 'b2',
            'type'  => 'event',
            'title' => 'Summer Launch Party',
            'day'   => '24',
            'month' => 'DEC',
            'url'   => '#',
        ),
        array(
            'id'    => 'b3',
            'type'  => 'cards',
            'cards' => array(
                array(
                    'title' => 'Agenda',
                    'img'   => 'https://images.unsplash.com/photo-1483412033650-1015ddeb83d1?auto=format&fit=crop&w=400&q=80',
                ),
                array(
                    'title' => 'Projetos',
                    'img'   => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=400&q=80',
                ),
            ),
        ),
        array(
            'id'   => 'b4',
            'type' => 'marquee',
            'text' => 'TICKETS AVAILABLE • LIMITED TIME • ',
        ),
        array(
            'id'   => 'b5',
            'type' => 'divider',
        ),
        array(
            'id'   => 'b6',
            'type' => 'text',
            'text' => 'Siga-nos nas redes sociais!',
        ),
        array(
            'id'    => 'b7',
            'type'  => 'social',
            'icons' => array(
                array(
                    'icon' => 'instagram-s',
                    'url'  => 'https://instagram.com',
                ),
                array(
                    'icon' => 'twitter-s',
                    'url'  => 'https://twitter.com',
                ),
                array(
                    'icon' => 'linkedin-s',
                    'url'  => 'https://linkedin.com',
                ),
            ),
        ),
        array(
            'id'      => 'b8',
            'type'    => 'image',
            'img'     => 'https://placehold.co/600x400',
            'caption' => 'Nossa equipe',
        ),
        array(
            'id'   => 'b9',
            'type' => 'video',
            'url'  => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ),
    ),
);

$saved_layout = get_post_meta( $post_id, 'apollo_userpage_layout_v1', true );

if ( is_array( $saved_layout ) && isset( $saved_layout['profile'], $saved_layout['blocks'] ) ) {
    $state = $saved_layout;
} elseif ( is_array( $saved_layout ) && isset( $saved_layout['grid'] ) ) {
    // Legacy grid fallback
    $state = $default_state;
} else {
    $state = $default_state;
}

$save_nonce = wp_create_nonce( 'apollo_userpage_save' );
$load_nonce = wp_create_nonce( 'apollo_userpage_load' );
$ajax_url   = admin_url( 'admin-ajax.php' );

if ( function_exists( 'apollo_ensure_base_assets' ) ) {
    apollo_ensure_base_assets();
}

wp_enqueue_script( 'sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true );

get_header();
?>

<script src="https://cdn.apollo.rio.br"></script>

<style>
/* --- 1. CORE THEME (Shadcn/Zinc) --- */
:root {
  --font-sans: "Roboto", Roboto, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  --font-body: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  --background: #ffffff;
  --foreground: #09090b;
  --muted: #f4f4f5;
  --muted-foreground: #71717a;
  --border: #e4e4e7;
  --input: #e4e4e7;
  --primary: #18181b;
  --primary-foreground: #fafafa;
  --secondary: #f4f4f5;
  --secondary-foreground: #18181b;
  --accent: #f4f4f5;
  --accent-foreground: #18181b;
  --destructive: #ef4444;
  --destructive-foreground: #fafafa;
  --ring: #18181b;
  --radius: 0.5rem;
  --brand-orange: #F97316;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
* { box-sizing: border-box; outline: none; }
body, html {
  font-family: var(--font-body);
  background-color: var(--background);
  color: var(--foreground);
  height: 100%;
  margin: 0;
  overflow: hidden;
}
h1, h2, h3, h4, h5, h6 { font-family: var(--font-sans); }

/* --- 2. MICRO-BULMA REPLACEMENT --- */
.button {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem; font-weight: 500;
  line-height: 1.5;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background-color: white;
  color: var(--foreground);
  cursor: pointer;
  transition: all 0.15s ease;
  height: 2.25rem;
  white-space: nowrap;
}
.button:hover { border-color: var(--ring); background-color: var(--accent); }
.button.is-primary { background-color: var(--primary); color: var(--primary-foreground); border-color: var(--primary); }
.button.is-primary:hover { opacity: 0.9; }
.button.is-small { font-size: 0.75rem; height: 1.75rem; padding: 0 0.5rem; }
.button.is-light { background-color: var(--secondary); color: var(--secondary-foreground); border-color: transparent; }
.button.is-white { background-color: transparent; border-color: transparent; }
.button.is-fullwidth { width: 100%; display: flex; }
.button .icon { margin-right: 0.25rem; }
.button.is-small .icon { margin-right: 0; }

.input, .textarea, .select select {
  display: block; width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--foreground);
  background-color: white;
  border: 1px solid var(--input);
  border-radius: var(--radius);
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input:focus, .textarea:focus, .select select:focus {
  border-color: var(--ring);
  box-shadow: 0 0 0 1px var(--ring);
}
.textarea { min-height: 80px; resize: vertical; }
.label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; color: var(--foreground); }
.field { margin-bottom: 0.75rem; }
.help { display: block; font-size: 0.75rem; margin-top: 0.25rem; color: var(--muted-foreground); }
.columns { display: flex; margin-left: -0.5rem; margin-right: -0.5rem; margin-top: -0.5rem; }
.columns:last-child { margin-bottom: -0.5rem; }
.column { flex: 1; padding: 0.5rem; }
.columns.is-mobile { display: flex; }
.columns.is-variable.is-1 { margin-left: -0.25rem; margin-right: -0.25rem; }
.columns.is-variable.is-1 > .column { padding: 0.25rem; }

/* Dropdown */
.dropdown { position: relative; display: inline-flex; vertical-align: top; }
.dropdown-trigger { cursor: pointer; }
.dropdown-menu {
  display: none; position: absolute; right: 0; top: 100%; z-index: 20;
  min-width: 12rem; padding-top: 4px;
}
.dropdown.is-active .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }
.dropdown-content {
  background-color: white;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  padding: 0.5rem 0;
}
.dropdown-item {
  display: flex; align-items: center;
  padding: 0.375rem 1rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--foreground);
  cursor: pointer;
  text-decoration: none;
}
.dropdown-item:hover { background-color: var(--accent); }
.dropdown-divider { background-color: var(--border); height: 1px; margin: 0.5rem 0; border: none; }

/* Utilities */
.mb-2 { margin-bottom: 0.5rem !important; }
.mr-2 { margin-right: 0.5rem !important; }
.text-danger { color: var(--destructive) !important; }
.is-hidden { display: none !important; }

/* --- 3. LAYOUT (SIDEBAR + PREVIEW) --- */
.app-layout { display: flex; height: 100vh; width: 100vw; position: relative; }

/* Sidebar */
.app-sidebar {
  position: absolute; top: 25px; left: 25px; bottom: 25px;
  width: 340px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: 20px;
  display: flex; flex-direction: column;
  z-index: 20;
  box-shadow: var(--shadow-md);
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.sidebar-header {
  height: 4rem;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 1.25rem;
  border-bottom: 1px solid transparent;
}
.sidebar-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1rem; letter-spacing: -0.02em; }
.sidebar-brand-icon { width: 28px; height: 28px; background: var(--brand-orange); color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.sidebar-content { flex: 1; overflow-y: auto; padding: 1.25rem; }
.sidebar-content::-webkit-scrollbar { width: 4px; }
.sidebar-content::-webkit-scrollbar-thumb { background: #e4e4e7; border-radius: 4px; }

.sidebar-group-label {
  font-size: 0.75rem; font-weight: 600; color: var(--muted-foreground);
  text-transform: uppercase; letter-spacing: 0.05em;
  margin: 1.5rem 0 0.75rem 0;
}
.sidebar-group-label:first-child { margin-top: 0; }

.sidebar-nav { display: flex; gap: 4px; background: var(--secondary); padding: 4px; border-radius: var(--radius); margin-bottom: 1.5rem; }
.nav-item {
  flex: 1; text-align: center; padding: 6px; font-size: 0.8rem; font-weight: 500;
  color: var(--muted-foreground); border-radius: 4px; cursor: pointer; transition: all 0.2s;
}
.nav-item:hover { color: var(--foreground); }
.nav-item.is-active { background: white; color: var(--foreground); font-weight: 600; box-shadow: var(--shadow-sm); }

/* Editor Cards */
.editor-card {
  background: white; border: 1px solid var(--border); border-radius: var(--radius);
  padding: 1rem; margin-bottom: 0.75rem; position: relative;
  transition: border-color 0.2s;
}
.editor-card:hover { border-color: #a1a1aa; }
.editor-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.editor-type-badge {
  font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
  background: var(--secondary); color: var(--secondary-foreground); padding: 2px 6px; border-radius: 4px;
}
.drag-handle { cursor: grab; color: var(--muted-foreground); margin-right: 8px; }

/* --- 4. PREVIEW AREA --- */
.app-main {
  flex: 1;
  background-color: #fafafa;
  background-image: radial-gradient(#e4e4e7 1px, transparent 1px);
  background-size: 24px 24px;
  display: flex; align-items: center; justify-content: center;
  position: relative;
}

.phone-frame {
  --s: 1;
  width: min(92vw, clamp(280px, calc((100vh - 80px) * 9 / 16), 440px));
  aspect-ratio: 9 / 16;
  height: auto;
  border-radius: calc(48px * var(--s));
  background: #fff;
  box-shadow:
    0 0 0 calc(12px * var(--s)) #111,
    0 calc(25px * var(--s)) calc(50px * var(--s)) calc(-12px * var(--s)) rgba(0,0,0,0.5);
  overflow: hidden;
  position: relative;
}

.dynamic-island {
  position: absolute; top: calc(12px * var(--s)); left: 50%; transform: translateX(-50%);
  width: 50%; height: calc(36px * var(--s)); background: #000; border-radius: calc(20px * var(--s)); z-index: 50;
}

.phone-screen {
  width: 100%; height: 100%; background: #fff;
  overflow-y: auto; overflow-x: hidden; position: relative;
  font-size: clamp(12px, calc(16px * var(--s)), 16px);
}
.phone-screen::-webkit-scrollbar { width: 0; }

/* Content in Preview */
.p-bg-layer {
  position: absolute; inset: 0;
  background-size: cover; background-position: center;
  opacity: 0.15; filter: blur(30px); transform: scale(1.2); z-index: 0;
}

/* Texture overlay with blend mode */
.texture-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  background-size: cover;
  background-position: center;
  background-repeat: repeat;
  mix-blend-mode: screen;
  opacity: 0.4;
  pointer-events: none;
  transition: background-image 0.5s ease;
}

.p-container {
  position: relative; z-index: 3;
  padding:
    calc(4.5rem * var(--s))
    calc(1.5rem * var(--s))
    calc(2rem * var(--s));
  gap: calc(1rem * var(--s));
  display: flex; flex-direction: column; min-height: 100%;
}

.p-header { text-align: center; margin-bottom: 1rem; }
.p-avatar {
  width: clamp(56px, calc(96px * var(--s)), 96px);
  height: clamp(56px, calc(96px * var(--s)), 96px);
  border-radius: 50%; object-fit: cover;
  border: clamp(2px, calc(4px * var(--s)), 4px) solid #fff;
  box-shadow: var(--shadow-md);
  margin: 0 auto clamp(0.6rem, calc(1rem * var(--s)), 1rem);
}
.p-name {
  font-size: clamp(1.05rem, calc(1.25rem * var(--s)), 1.25rem);
  font-weight: 700; color: #18181b; margin-bottom: 0.25rem; letter-spacing: -0.025em;
}
.p-bio {
  font-size: clamp(0.78rem, calc(0.875rem * var(--s)), 0.875rem);
  color: #71717a; line-height: 1.45; max-width: 90%; margin: 0 auto;
}

/* Block Styles */
.p-block {
  background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5); border-radius: calc(16px * var(--s));
  overflow: hidden; cursor: pointer; text-decoration: none; color: inherit;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: transform 0.2s, background 0.2s;
}
.p-block:active { transform: scale(0.98); }
.p-block:hover { background: rgba(255, 255, 255, 0.9); }

/* Link Block */
.p-link-content {
  padding: clamp(0.7rem, calc(1rem * var(--s)), 1rem);
  display: flex; align-items: center;
  gap: clamp(0.5rem, calc(0.75rem * var(--s)), 0.75rem);
}
.p-icon-box {
  width: clamp(34px, calc(42px * var(--s)), 42px);
  height: clamp(34px, calc(42px * var(--s)), 42px);
  border-radius: clamp(8px, calc(10px * var(--s)), 10px);
  background: #f4f4f5;
  display: flex; align-items: center; justify-content: center;
  color: #18181b;
  font-size: clamp(1rem, calc(1.2rem * var(--s)), 1.2rem);
}

/* Apollo Masked Icon Support */
.p-icon-masked {
  width: clamp(18px, calc(24px * var(--s)), 24px);
  height: clamp(18px, calc(24px * var(--s)), 24px);
  background-color: currentColor;
  -webkit-mask-size: contain; mask-size: contain;
  -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;
  -webkit-mask-position: center; mask-position: center;
}

.p-text-group { flex: 1; min-width: 0; }
.p-title {
  font-weight: 600;
  font-size: clamp(0.88rem, calc(0.95rem * var(--s)), 0.95rem);
  color: #18181b; margin-bottom: 2px;
}
.p-sub {
  font-size: clamp(0.68rem, calc(0.75rem * var(--s)), 0.75rem);
  color: #71717a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Event Block */
.p-event {
  display: flex;
  padding: clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem);
  background: #fff; align-items: center;
  gap: clamp(0.5rem, calc(0.75rem * var(--s)), 0.75rem);
}
.p-event-date {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  width: clamp(48px, calc(60px * var(--s)), 60px);
  height: clamp(48px, calc(60px * var(--s)), 60px);
  background: #fafafa; border: 1px solid #e4e4e7;
  border-radius: clamp(10px, calc(12px * var(--s)), 12px);
  flex-shrink: 0;
}
.p-day {
  font-size: clamp(1.05rem, calc(1.25rem * var(--s)), 1.25rem);
  font-weight: 800; color: var(--brand-orange); line-height: 1;
}
.p-month {
  font-size: clamp(0.58rem, calc(0.65rem * var(--s)), 0.65rem);
  text-transform: uppercase; font-weight: 700; color: #a1a1aa; margin-top: 2px;
}

/* Marquee Block */
.p-marquee-shell {
  background: #18181b; color: #fff;
  padding: clamp(0.55rem, calc(0.75rem * var(--s)), 0.75rem) 0;
  border: none;
}
.p-marquee-track { white-space: nowrap; overflow: hidden; display: flex; }
.p-marquee-content {
  display: inline-block; animation: marquee 12s linear infinite;
  padding-left: 100%; font-weight: 600; text-transform: uppercase;
  font-size: clamp(0.72rem, calc(0.8rem * var(--s)), 0.8rem);
  letter-spacing: 0.05em;
}
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-200%); } }

/* Cards Grid Block */
.p-cards-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: clamp(8px, calc(10px * var(--s)), 10px);
  padding: 0; background: transparent; border: none; box-shadow: none;
}
.p-card-item {
  position: relative; border-radius: calc(16px * var(--s)); overflow: hidden;
  aspect-ratio: 1/1;
  border: 1px solid rgba(255,255,255,0.5);
}
.p-card-img { width: 100%; height: 100%; object-fit: cover; }
.p-card-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
  display: flex; align-items: flex-end;
  padding: clamp(8px, calc(10px * var(--s)), 10px);
}
.p-card-title {
  color: white; font-weight: 600;
  font-size: clamp(0.75rem, calc(0.85rem * var(--s)), 0.85rem);
}

/* Additional Block Styles */
.p-divider { border-top: 1px solid rgba(255,255,255,0.5); margin: 0.5rem 0; }
.p-text {
  padding: clamp(0.7rem, calc(1rem * var(--s)), 1rem);
  font-size: clamp(0.78rem, calc(0.875rem * var(--s)), 0.875rem);
  line-height: 1.5; color: #71717a;
}
.p-image { padding: 0; }
.p-image-img { width: 100%; height: auto; border-radius: calc(16px * var(--s)); }
.p-image-caption {
  text-align: center;
  font-size: clamp(0.68rem, calc(0.75rem * var(--s)), 0.75rem);
  color: #71717a;
  padding: clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem);
}
.p-video { padding: 0; }
.p-video-iframe {
  width: 100%; aspect-ratio: 16/9; border: none;
  border-radius: calc(16px * var(--s));
}
.p-social {
  display: flex; justify-content: center;
  gap: clamp(0.75rem, calc(1rem * var(--s)), 1rem);
  padding: clamp(0.7rem, calc(1rem * var(--s)), 1rem);
  background: transparent; border: none; box-shadow: none;
}
.p-social-link {
  color: #18181b;
  font-size: clamp(1.2rem, calc(1.5rem * var(--s)), 1.5rem);
  transition: color 0.2s;
}
.p-social-link:hover { color: var(--brand-orange); }
.p-newsletter {
  padding: clamp(0.7rem, calc(1rem * var(--s)), 1rem);
}
.p-newsletter form {
  display: flex;
  gap: clamp(0.2rem, calc(0.25rem * var(--s)), 0.25rem);
}
.p-newsletter input {
  flex: 1;
  padding: clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: white;
  color: var(--foreground);
}
.p-newsletter button {
  padding: clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem) clamp(0.8rem, calc(1rem * var(--s)), 1rem);
  background: var(--primary);
  color: var(--primary-foreground);
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
}

/* --- 5. MODALS --- */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
  z-index: 100; display: none; align-items: center; justify-content: center;
}
.modal-overlay.is-open { display: flex; animation: fadeIn 0.2s; }
.modal-card {
  background: #fff; width: 90%; max-width: 500px; border-radius: 1rem; padding: 0;
  box-shadow: var(--shadow-xl); transform: translateY(10px); transition: transform 0.2s;
  max-height: 80vh; display: flex; flex-direction: column;
}
.modal-overlay.is-open .modal-card { transform: translateY(0); }
.modal-header {
  padding: 1rem 1.5rem; border-bottom: 1px solid var(--border);
  display: flex; justify-content: space-between; align-items: center;
}
.modal-title { font-weight: 600; font-size: 1.1rem; }
.modal-body { padding: 1.5rem; overflow-y: auto; }
.modal-close-btn {
  background: transparent; border: none; cursor: pointer;
  font-size: 1.2rem; color: var(--muted-foreground);
}

/* Icon Grid */
.icon-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
  gap: 10px;
}
.icon-choice {
  aspect-ratio: 1; border: 1px solid var(--border); border-radius: 8px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.1s; gap: 4px;
}
.icon-choice:hover { background: var(--secondary); border-color: var(--ring); }
.icon-choice i { font-size: 1.25rem; color: var(--foreground); }
.icon-choice span {
  font-size: 0.6rem; color: var(--muted-foreground); text-align: center;
  overflow: hidden; width: 100%; white-space: nowrap; text-overflow: ellipsis; padding: 0 2px;
}

/* Stats */
.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
.stat-item { padding: 1rem; background: var(--secondary); border-radius: 0.75rem; text-align: center; }
.stat-num { font-size: 1.5rem; font-weight: 700; color: var(--brand-orange); }
.stat-lbl {
  font-size: 0.75rem; color: var(--muted-foreground);
  text-transform: uppercase; font-weight: 600;
}
.chart-box {
  height: 100px; display: flex; align-items: flex-end; gap: 8px;
  justify-content: space-between; margin-top: 1rem;
}
.chart-bar {
  background: #e4e4e7; border-radius: 4px; flex: 1;
  transition: height 0.5s ease;
}
.chart-bar:hover { background: var(--brand-orange); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* --- 6. MOBILE PREVIEW MODE --- */
body.mobile-preview-mode {
  overflow: hidden;
}
body.mobile-preview-mode .app-sidebar {
  transform: translateX(-120%);
  opacity: 0;
  pointer-events: none;
}
body.mobile-preview-mode .app-main {
  background: #000;
  background-image: none;
}
body.mobile-preview-mode .phone-frame {
  width: 100vw;
  max-width: 100vw;
  height: 100vh;
  max-height: 100vh;
  border-radius: 0;
  box-shadow: none;
  aspect-ratio: auto;
  animation: slideInFromRight 0.4s ease-out;
}
body.mobile-preview-mode .dynamic-island {
  display: none;
}

@keyframes slideInFromRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Mobile Preview Controls */
.mobile-preview-controls {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 200;
  display: flex;
  gap: 10px;
  align-items: center;
}

.preview-toggle-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.preview-toggle-btn:hover {
  transform: scale(1.1);
}
.preview-toggle-btn svg {
  width: 28px;
  height: 28px;
  color: #18181b;
  transition: all 0.3s;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
body.mobile-preview-mode .preview-toggle-btn .preview-icon-open {
  display: none;
}
body.mobile-preview-mode .preview-toggle-btn .preview-icon-close {
  display: block !important;
  color: white;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

/* Texture Navigation Controls */
.texture-nav-controls {
  position: fixed;
  top: 50%;
  left: 0;
  right: 0;
  transform: translateY(-50%);
  z-index: 199;
  display: none;
  justify-content: space-between;
  padding: 0 20px;
  pointer-events: none;
}
body.mobile-preview-mode .texture-nav-controls {
  display: flex;
}

.texture-nav-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  pointer-events: all;
}
.texture-nav-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
  border-color: rgba(255, 255, 255, 0.4);
}
.texture-nav-btn i {
  font-size: 24px;
  color: white;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

/* Texture overlay with blend mode */
.texture-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  background-size: cover;
  background-position: center;
  background-repeat: repeat;
  mix-blend-mode: screen;
  opacity: 0.4;
  pointer-events: none;
  transition: background-image 0.5s ease;
}

body.mobile-preview-mode .p-bg-layer {
  opacity: 0.3;
}

/* Mobile responsiveness */
@media (max-width: 980px) {
  body, html { overflow: auto; }
  .app-layout { flex-direction: column; height: auto; min-height: 100vh; }
  .app-sidebar {
    position: relative;
    top: 0; left: 0; bottom: auto;
    width: 100%;
    height: auto;
    border-radius: 0;
    border-left: 0; border-right: 0;
    transition: transform 0.4s ease, opacity 0.4s ease;
  }
  .app-main {
    min-height: 60vh;
    padding: 14px;
  }
  .phone-frame {
    width: min(94vw, 420px);
  }
  .mobile-preview-controls {
    top: 10px;
    right: 10px;
  }
  .preview-toggle-btn svg {
    width: 32px;
    height: 32px;
  }
  .texture-nav-btn {
    width: 45px;
    height: 45px;
  }
  .texture-nav-controls {
    padding: 0 10px;
  }

  /* Mobile preview mode overrides for mobile devices */
  body.mobile-preview-mode {
    overflow: hidden;
  }
  body.mobile-preview-mode .app-layout {
    height: 100vh;
    overflow: hidden;
  }
  body.mobile-preview-mode .app-sidebar {
    position: fixed;
    transform: translateX(-120%);
    opacity: 0;
    pointer-events: none;
    z-index: 10;
  }
  body.mobile-preview-mode .app-main {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    min-height: 100vh;
    padding: 0;
    background: #000;
    background-image: none;
    z-index: 15;
  }
  body.mobile-preview-mode .phone-frame {
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
    box-shadow: none;
  }
  body.mobile-preview-mode .dynamic-island {
    display: none;
  }
}
</style>

<div class="app-layout" data-user-id="<?php echo esc_attr( $user_id ); ?>">
  <!-- Mobile Preview Controls -->
  <div class="mobile-preview-controls">
    <button class="preview-toggle-btn" onclick="toggleMobilePreview()" id="preview-toggle">
      <svg class="preview-icon preview-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 3C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H21ZM18 12H16V15H13V17H18V12ZM11 7H6V12H8V9H11V7Z"></path></svg>
      <svg class="preview-icon preview-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M5.63611 12.7071L7.46454 14.5355L8.87875 13.1213L7.05033 11.2929L8.46454 9.87869L10.293 11.7071L11.7072 10.2929L9.87875 8.46448L11.293 7.05026L13.1214 8.87869L14.5356 7.46448L12.7072 5.63605L15.5356 2.80762C15.9261 2.4171 16.5593 2.4171 16.9498 2.80762L21.1925 7.05026C21.583 7.44079 21.583 8.07395 21.1925 8.46448L8.46454 21.1924C8.07401 21.5829 7.44085 21.5829 7.05033 21.1924L2.80768 16.9498C2.41716 16.5592 2.41716 15.9261 2.80768 15.5355L5.63611 12.7071ZM14.1214 18.3635L18.364 14.1208L20.9997 16.7565V20.9999H16.7578L14.1214 18.3635ZM5.63597 9.87806L2.80754 7.04963C2.41702 6.65911 2.41702 6.02594 2.80754 5.63542L5.63597 2.80699C6.02649 2.41647 6.65966 2.41647 7.05018 2.80699L9.87861 5.63542L5.63597 9.87806Z"></path></svg>
    </button>
    <button class="button is-small is-primary" id="btn-save-top">Salvar</button>
  </div>

  <!-- Texture Navigation (only visible in mobile preview mode) -->
  <div class="texture-nav-controls">
    <button class="texture-nav-btn" onclick="previousTexture()">
      <i class="i-arrow-left-s-s"></i>
    </button>
    <button class="texture-nav-btn" onclick="nextTexture()">
      <i class="i-arrow-right-s-s"></i>
    </button>
  </div>

  <!-- SIDEBAR -->
  <aside class="app-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="apollo-s" style="width:20px;height:20px;"></i></div>
        <span>HUB::rio</span>
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
        <button class="button is-small is-primary" id="btn-save">Salvar</button>
        <button class="button is-small is-white" onclick="toggleSidebar()">
          <i class="ri-settings-fill" style="color:#a1a1aa"></i>
        </button>
      </div>
    </div>
    <div class="sidebar-content">
      <div class="sidebar-nav">
        <div class="nav-item is-active" onclick="switchTab('editor')" id="nav-editor">Editor</div>
        <div class="nav-item" onclick="switchTab('profile')" id="nav-profile">Perfil</div>
        <div class="nav-item" onclick="switchTab('analytics')" id="nav-analytics">Analytics</div>
      </div>

      <!-- TAB: EDITOR -->
      <div id="tab-editor">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
          <div class="sidebar-group-label" style="margin:0">Seus Blocos</div>
          <div class="dropdown is-right is-hoverable">
            <div class="dropdown-trigger">
              <button class="button is-small is-primary" aria-haspopup="true">
                <span class="icon"><i class="ri-add-fill"></i></span>
                <span>Adicionar</span>
              </button>
            </div>
            <div class="dropdown-menu" role="menu">
              <div class="dropdown-content">
                <a class="dropdown-item" onclick="addBlock('header')"><i class="ri-input-method-line mr-2"></i> Cabecalho</a>
                <a class="dropdown-item" onclick="addBlock('link')"><i class="ri-link mr-2"></i> Link Simples</a>
                <a class="dropdown-item" onclick="addBlock('event')"><i class="ri-calendar-line mr-2"></i> Evento</a>
                <a class="dropdown-item" onclick="addBlock('cards')"><i class="ri-grid-line mr-2"></i> Grade de Cards</a>
                <a class="dropdown-item" onclick="addBlock('marquee')"><i class="ri-text-wrap-fill mr-2"></i> Marquee</a>
                <hr class="dropdown-divider">
                <a class="dropdown-item" onclick="addBlock('divider')"><i class="ri-separator mr-2"></i> Divisor</a>
                <a class="dropdown-item" onclick="addBlock('text')"><i class="ri-text mr-2"></i> Texto</a>
                <a class="dropdown-item" onclick="addBlock('image')"><i class="ri-image-line mr-2"></i> Imagem</a>
                <a class="dropdown-item" onclick="addBlock('video')"><i class="ri-video-line mr-2"></i> Video</a>
                <a class="dropdown-item" onclick="addBlock('social')"><i class="ri-group-line mr-2"></i> Icones Sociais</a>
                <a class="dropdown-item" onclick="addBlock('newsletter')"><i class="ri-mail-add-line mr-2"></i> Newsletter Signup</a>
              </div>
            </div>
          </div>
        </div>
        <div id="blocks-container"></div>
      </div>

      <!-- TAB: PROFILE -->
      <div id="tab-profile" class="is-hidden">
        <div class="sidebar-group-label">Informacoes Basicas</div>
        <div class="field">
          <label class="label">Nome</label>
          <input class="input" type="text" id="inp-name">
        </div>
        <div class="field">
          <label class="label">Bio</label>
          <textarea class="textarea" id="inp-bio"></textarea>
        </div>
        <div class="sidebar-group-label">Imagens</div>
        <div class="field">
          <label class="label">Avatar URL</label>
          <div class="columns is-mobile is-variable is-1">
            <div class="column is-three-quarters">
              <input class="input" type="text" id="inp-avatar">
            </div>
            <div class="column">
              <div style="width:100%; height:100%; background:#f4f4f5; border-radius:6px; overflow:hidden;">
                <img id="prev-avatar" style="width:100%; height:100%; object-fit:cover;">
              </div>
            </div>
          </div>
        </div>
        <div class="field">
          <label class="label">Fundo URL</label>
          <input class="input" type="text" id="inp-bg" placeholder="https://...">
        </div>
        <div class="sidebar-group-label">Tema</div>
        <div class="field">
          <label class="label">Cor Primaria</label>
          <input class="input" type="color" id="inp-primary" value="#18181b">
        </div>
        <div class="field">
          <label class="label">Cor de Destaque</label>
          <input class="input" type="color" id="inp-accent" value="#F97316">
        </div>
      </div>

      <!-- TAB: ANALYTICS -->
      <div id="tab-analytics" class="is-hidden">
        <div style="text-align:center; padding: 3rem 1rem; color: var(--muted-foreground);">
          <div style="width:64px; height:64px; background:var(--secondary); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
            <i class="ri-pie-chart-line" style="font-size:1.5rem;"></i>
          </div>
          <h3 style="font-weight:600; color:var(--foreground)">Analytics Global</h3>
          <p style="font-size:0.8rem; margin-top:0.5rem">Selecione blocos individuais no editor para ver estatisticas de clique.</p>
        </div>
      </div>
    </div>
  </aside>

  <!-- PREVIEW AREA -->
  <main class="app-main">
    <div class="phone-frame">
      <div class="dynamic-island"></div>
      <div class="phone-screen">
        <div class="texture-overlay" id="texture-overlay"></div>
        <div id="p-bg" class="p-bg-layer"></div>
        <div class="p-container">
          <header class="p-header">
            <img id="p-avatar" class="p-avatar" src="" alt="">
            <h1 id="p-name" class="p-name"></h1>
            <p id="p-bio" class="p-bio"></p>
          </header>
          <div id="p-blocks-area" style="display:flex; flex-direction:column; gap:0.75rem;"></div>
          <div style="margin-top:auto; padding-top:2rem; text-align:center; opacity:0.3; font-size:1.5rem"></div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- STATS MODAL -->
<div class="modal-overlay" id="stats-modal">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title"><i class="ri-bar-chart-line" style="color:var(--brand-orange)"></i> Performance</div>
      <button class="modal-close-btn" onclick="closeModal('stats-modal')"><i class="ri-close-line"></i></button>
    </div>
    <div class="modal-body">
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-num" id="st-clicks">0</div>
          <div class="stat-lbl">Cliques</div>
        </div>
        <div class="stat-item">
          <div class="stat-num" id="st-ctr">0%</div>
          <div class="stat-lbl">CTR Medio</div>
        </div>
      </div>
      <div class="sidebar-group-label" style="margin:0 0 0.5rem 0">Engajamento (7 Dias)</div>
      <div class="chart-box" id="st-chart"></div>
    </div>
  </div>
</div>

<!-- ICON PICKER MODAL -->
<div class="modal-overlay" id="icon-modal">
  <div class="modal-card" style="height:70vh">
    <div class="modal-header">
      <div class="modal-title">Selecionar Icone</div>
      <button class="modal-close-btn" onclick="closeModal('icon-modal')"><i class="ri-close-line"></i></button>
    </div>
    <div class="modal-body">
      <input class="input mb-2" id="icon-search" placeholder="Buscar icone..." oninput="filterIcons(this.value)">
      <div class="icon-grid" id="icon-grid"></div>
    </div>
  </div>
</div>

<script src="https://assets.apollo.rio.br/icon.js"></script>
<script>
'use strict';

const initialState = <?php echo wp_json_encode( $state ); ?>;
const ajaxurl = window.ajaxurl || '<?php echo esc_url( $ajax_url ); ?>';
const SAVE_NONCE = '<?php echo esc_js( $save_nonce ); ?>';
const LOAD_NONCE = '<?php echo esc_js( $load_nonce ); ?>';
const CURRENT_USER_ID = <?php echo (int) $user_id; ?>;

/* --- TEXTURE FILES --- */
const textureFiles = [
  "001.jpg", "005.jpg", "008.jpg", "013.jpg", "027.jpg",
  "039.jpg", "057.jpg", "058.jpg", "060.jpg", "148.jpg",
  "150.jpg", "172.jpg", "181.jpg", "182.jpg", "257.jpg",
  "270.jpg", "274.jpg", "328.jpg", "334.jpg", "339.jpg", "357.jpg"
];
const textureBasePath = "https://assets.apollo.rio.br/img/textures/";
let currentTextureIndex = 10;

/* --- APOLLO ICON SYSTEM --- */
const CDN = "https://assets.apollo.rio.br/i";
const loadedIcons = new Set();

const styleTag = document.createElement("style");
styleTag.innerHTML = `.p-icon-masked{display:inline-block;width:1em;height:1em;background-color:currentColor;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;}`;
document.head.appendChild(styleTag);

function injectIconStyle(cls, name) {
  if (!name) return;
  const normalized = String(name).replace(/\.svg$/, '');
  if (loadedIcons.has(cls + '::' + normalized)) return;
  const s = document.createElement("style");
  const u = `${CDN}/${normalized}.svg`;
  s.innerHTML = `.${cls}{ -webkit-mask-image: url(${u}); mask-image: url(${u}); }`;
  document.head.appendChild(s);
  loadedIcons.add(cls + '::' + normalized);
}

const iconData = [
  {name:'star', file:'star.svg'}, {name:'heart', file:'heart-s.svg'}, {name:'rocket', file:'rocket-s.svg'},
  {name:'instagram', file:'instagram-s.svg'}, {name:'twitter', file:'twitter-s.svg'}, {name:'linkedin', file:'linkedin-s.svg'},
  {name:'github', file:'github-s.svg'}, {name:'globe', file:'globe-s.svg'}, {name:'mail', file:'mail-s.svg'},
  {name:'phone', file:'phone-s.svg'}, {name:'whatsapp', file:'whatsapp-s.svg'}, {name:'spotify', file:'spotify-s.svg'},
  {name:'youtube', file:'youtube-s.svg'}, {name:'play', file:'play-circle-s.svg'}, {name:'pause', file:'pause-circle-s.svg'},
  {name:'camera', file:'camera-s.svg'}, {name:'image', file:'image-s.svg'}, {name:'calendar', file:'calendar-s.svg'},
  {name:'map-pin', file:'map-pin-s.svg'}, {name:'shopping-bag', file:'shopping-bag-s.svg'}, {name:'credit-card', file:'bank-card-s.svg'},
  {name:'user', file:'user-s.svg'}, {name:'users', file:'group-s.svg'}, {name:'lock', file:'lock-s.svg'},
  {name:'search', file:'search-s.svg'}, {name:'home', file:'home-s.svg'}, {name:'menu', file:'menu-s.svg'},
  {name:'link', file:'link.svg'}, {name:'external-link', file:'external-link-s.svg'}, {name:'download', file:'download-s.svg'},
  {name:'cloud', file:'cloud-s.svg'}, {name:'bolt', file:'flashlight-s.svg'}, {name:'fire', file:'fire-s.svg'},
  {name:'gift', file:'gift-s.svg'}, {name:'medal', file:'medal-s.svg'}, {name:'trophy', file:'trophy-s.svg'},
  {name:'crown', file:'vip-crown-s.svg'}, {name:'diamond', file:'diamond-s.svg'}, {name:'ticket', file:'ticket-s.svg'},
  {name:'music', file:'music-s.svg'}, {name:'video', file:'video-s.svg'}, {name:'mic', file:'mic-s.svg'},
  {name:'book', file:'book-s.svg'}, {name:'briefcase', file:'briefcase-s.svg'}, {name:'coffee', file:'cup-s.svg'}
];

/* --- APPLICATION STATE --- */
let state = JSON.parse(JSON.stringify(initialState || {}));
let activeBlockIndex = null;
let activeSocialIndex = null;

document.addEventListener('DOMContentLoaded', () => {
  updateTexture();
  renderAll();
  bindProfileInputs();
  populateIconGrid();
  initDragAndDrop();
  attachSaveButtons();
});

function attachSaveButtons() {
  const btn1 = document.getElementById('btn-save');
  const btn2 = document.getElementById('btn-save-top');
  [btn1, btn2].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', saveLayout);
  });
}

/* --- DRAG AND DROP --- */
function initDragAndDrop() {
  const container = document.getElementById('blocks-container');
  if (!container) return;
  container.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', e.target.dataset.index);
    e.target.style.opacity = '0.5';
  });
  container.addEventListener('dragend', (e) => {
    e.target.style.opacity = '1';
  });
  container.addEventListener('dragover', (e) => {
    e.preventDefault();
  });
  container.addEventListener('drop', (e) => {
    e.preventDefault();
    const fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
    const toIndex = parseInt(e.target.closest('.editor-card').dataset.index);
    if (Number.isNaN(fromIndex) || Number.isNaN(toIndex)) return;
    if (fromIndex !== toIndex) {
      const [moved] = state.blocks.splice(fromIndex, 1);
      state.blocks.splice(toIndex, 0, moved);
      renderAll();
    }
  });
}

/* --- RENDERING SYSTEM --- */
function renderAll() {
  updateTheme();
  renderPreview();
  renderEditor();
}

function updateTheme() {
  document.documentElement.style.setProperty('--primary', state.profile.primary || '#18181b');
  document.documentElement.style.setProperty('--brand-orange', state.profile.accent || '#F97316');
}

function renderPreview() {
  const nameEl = document.getElementById('p-name');
  const bioEl = document.getElementById('p-bio');
  const avatarEl = document.getElementById('p-avatar');
  if (nameEl) nameEl.textContent = state.profile.name || '';
  if (bioEl) bioEl.textContent = state.profile.bio || '';
  if (avatarEl) avatarEl.src = state.profile.avatar || '';

  const bg = document.getElementById('p-bg');
  if (bg) {
    if (state.profile.bg) {
      bg.style.backgroundImage = `url('${state.profile.bg}')`;
      bg.style.opacity = '0.15';
    } else {
      bg.style.opacity = '0';
    }
  }

  const container = document.getElementById('p-blocks-area');
  if (!container) return;
  container.innerHTML = '';

  (state.blocks || []).forEach(b => {
    let el;
    if (b.type === 'header') {
      el = document.createElement('h3');
      el.style.cssText = "font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#18181b; margin:0.5rem 0 0.25rem 0.5rem; opacity:0.8";
      el.textContent = b.text || '';
    } else if (b.type === 'link') {
      el = document.createElement('a');
      el.className = 'p-block';
      el.href = b.url || '#';
      el.onclick = (e) => e.preventDefault();
      const iconKey = b.icon || 'link';
      const cls = `icon-${iconKey}`;
      injectIconStyle(cls, iconKey);
      const iconHtml = `<div class="p-icon-masked ${cls}"></div>`;
      el.innerHTML = `
        <div class="p-link-content">
          <div class="p-icon-box">${iconHtml}</div>
          <div class="p-text-group">
            <div class="p-title">${b.title || ''}</div>
            <div class="p-sub">${b.sub || ''}</div>
          </div>
          <div style="color:#d4d4d8"><i class="ri-arrow-right-s-line"></i></div>
        </div>
      `;
    } else if (b.type === 'event') {
      el = document.createElement('a');
      el.className = 'p-block';
      el.href = b.url || '#';
      el.onclick = (e) => e.preventDefault();
      el.innerHTML = `
        <div class="p-event">
          <div class="p-event-date">
            <span class="p-day">${b.day || ''}</span>
            <span class="p-month">${b.month || ''}</span>
          </div>
          <div class="p-text-group">
            <div class="p-title" style="font-size:1rem">${b.title || ''}</div>
            <div style="font-size:0.75rem; color:var(--brand-orange); font-weight:600">Get Tickets</div>
          </div>
          <div style="width:32px; height:32px; background:#18181b; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem">
            <i class="ri-arrow-right-line"></i>
          </div>
        </div>
      `;
    } else if (b.type === 'marquee') {
      el = document.createElement('div');
      el.className = 'p-block p-marquee-shell';
      el.innerHTML = `
        <div class="p-marquee-track">
          <span class="p-marquee-content">${b.text || ''} &nbsp; • &nbsp; ${b.text || ''} &nbsp; • &nbsp; </span>
        </div>
      `;
    } else if (b.type === 'cards') {
      el = document.createElement('div');
      el.className = 'p-cards-grid';
      (b.cards || []).forEach(c => {
        const card = document.createElement('div');
        card.className = 'p-card-item';
        card.innerHTML = `
          <img class="p-card-img" src="${c.img || ''}">
          <div class="p-card-overlay"><span class="p-card-title">${c.title || ''}</span></div>
        `;
        el.appendChild(card);
      });
    } else if (b.type === 'divider') {
      el = document.createElement('div');
      el.className = 'p-divider';
    } else if (b.type === 'text') {
      el = document.createElement('div');
      el.className = 'p-text';
      el.textContent = b.text || '';
    } else if (b.type === 'image') {
      el = document.createElement('div');
      el.className = 'p-image';
      el.innerHTML = `
        <img class="p-image-img" src="${b.img || ''}" alt="${b.caption || ''}">
        ${b.caption ? `<div class="p-image-caption">${b.caption}</div>` : ''}
      `;
    } else if (b.type === 'video') {
      el = document.createElement('div');
      el.className = 'p-video';
      el.innerHTML = `<iframe class="p-video-iframe" src="${b.url || ''}" allowfullscreen></iframe>`;
    } else if (b.type === 'social') {
      el = document.createElement('div');
      el.className = 'p-social';
      (b.icons || []).forEach(icon => {
        const link = document.createElement('a');
        link.className = 'p-social-link';
        link.href = icon.url || '#';
        link.onclick = (e) => e.preventDefault();
        const cls = `icon-${icon.icon || 'link'}`;
        injectIconStyle(cls, icon.icon || 'link');
        link.innerHTML = `<div class="p-icon-masked ${cls}"></div>`;
        el.appendChild(link);
      });
    } else if (b.type === 'newsletter') {
      el = document.createElement('div');
      el.className = 'p-block p-newsletter';
      el.innerHTML = `
        <div class="p-title" style="text-align:center; margin-bottom:0.5rem;">${b.title || ''}</div>
        <form onsubmit="return false;">
          <input placeholder="${b.placeholder || ''}" type="email">
          <button>${b.button || 'Enviar'}</button>
        </form>
      `;
    }
    if (el) container.appendChild(el);
  });
}

function renderEditor() {
  const container = document.getElementById('blocks-container');
  if (!container) return;
  container.innerHTML = '';

  (state.blocks || []).forEach((b, idx) => {
    const card = document.createElement('div');
    card.className = 'editor-card';
    card.draggable = true;
    card.dataset.index = idx;

    let content = '';
    if (b.type === 'header') {
      content = `<div class="field"><label class="label">Texto do Cabecalho</label><input class="input" value="${b.text || ''}" oninput="upd(${idx}, 'text', this.value)"></div>`;
    } else if (b.type === 'link') {
      content = `
        <div class="field"><label class="label">Titulo</label><input class="input" value="${b.title || ''}" oninput="upd(${idx}, 'title', this.value)"></div>
        <div class="field"><label class="label">Subtitulo</label><input class="input" value="${b.sub || ''}" oninput="upd(${idx}, 'sub', this.value)"></div>
        <div class="field"><label class="label">URL</label><input class="input" value="${b.url || ''}" oninput="upd(${idx}, 'url', this.value)"></div>
        <div class="field">
           <label class="label">Icone</label>
           <div class="columns is-mobile is-variable is-1">
             <div class="column is-narrow">
               <div style="width:2.5rem; height:2.5rem; background:var(--secondary); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="openIconModal(${idx})">
                 <div class="p-icon-masked icon-${b.icon || 'link'}" style="background:var(--foreground)"></div>
               </div>
             </div>
             <div class="column">
               <button class="button is-small is-fullwidth" onclick="openIconModal(${idx})">Alterar Icone</button>
             </div>
           </div>
        </div>
      `;
    } else if (b.type === 'event') {
      content = `
         <div class="field"><label class="label">Evento</label><input class="input" value="${b.title || ''}" oninput="upd(${idx}, 'title', this.value)"></div>
         <div class="columns is-mobile is-variable is-1 mb-2">
           <div class="column"><label class="label">Dia</label><input class="input" value="${b.day || ''}" oninput="upd(${idx}, 'day', this.value)"></div>
           <div class="column"><label class="label">Mes</label><input class="input" value="${b.month || ''}" oninput="upd(${idx}, 'month', this.value)"></div>
         </div>
         <div class="field"><label class="label">Link</label><input class="input" value="${b.url || ''}" oninput="upd(${idx}, 'url', this.value)"></div>
      `;
    } else if (b.type === 'marquee') {
      content = `<div class="field"><label class="label">Texto (Rolagem)</label><input class="input" value="${b.text || ''}" oninput="upd(${idx}, 'text', this.value)"></div>`;
    } else if (b.type === 'cards') {
      content = `
        <label class="label">Cartoes</label>
        ${(b.cards||[]).map((c, ci) => `
           <div style="border:1px solid var(--border); padding:0.5rem; border-radius:0.5rem; margin-bottom:0.5rem;">
             <input class="input mb-2 is-small" placeholder="Titulo" value="${c.title || ''}" oninput="updCard(${idx}, ${ci}, 'title', this.value)">
             <input class="input is-small" placeholder="Imagem URL" value="${c.img || ''}" oninput="updCard(${idx}, ${ci}, 'img', this.value)">
           </div>
        `).join('')}
        <button class="button is-small is-light is-fullwidth" onclick="addCardItem(${idx})">+ Add Card</button>
      `;
    } else if (b.type === 'divider') {
      content = '<div class="help">Divisor simples.</div>';
    } else if (b.type === 'text') {
      content = `<div class="field"><label class="label">Texto</label><textarea class="textarea" oninput="upd(${idx}, 'text', this.value)">${b.text || ''}</textarea></div>`;
    } else if (b.type === 'image') {
      content = `
        <div class="field"><label class="label">URL da Imagem</label><input class="input" value="${b.img || ''}" oninput="upd(${idx}, 'img', this.value)"></div>
        <div class="field"><label class="label">Legenda</label><input class="input" value="${b.caption || ''}" oninput="upd(${idx}, 'caption', this.value)"></div>
      `;
    } else if (b.type === 'video') {
      content = `<div class="field"><label class="label">URL de Embed (YouTube)</label><input class="input" value="${b.url || ''}" oninput="upd(${idx}, 'url', this.value)"></div>`;
    } else if (b.type === 'social') {
      content = `
        <label class="label">Icones Sociais</label>
        ${(b.icons||[]).map((icon, si) => `
           <div style="border:1px solid var(--border); padding:0.5rem; border-radius:0.5rem; margin-bottom:0.5rem; display:flex; gap:0.5rem; align-items:center;">
             <div style="width:2rem; height:2rem; background:var(--secondary); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="openIconModal(${idx}, ${si})">
               <div class="p-icon-masked icon-${icon.icon || 'link'}" style="background:var(--foreground); width:1.2rem; height:1.2rem;"></div>
             </div>
             <input class="input is-small" style="flex:1;" placeholder="URL" value="${icon.url || ''}" oninput="updSocial(${idx}, ${si}, 'url', this.value)">
             <button class="button is-small is-white text-danger" onclick="delSocial(${idx}, ${si})"><i class="ri-delete-bin-line"></i></button>
           </div>
        `).join('')}
        <button class="button is-small is-light is-fullwidth" onclick="addSocialItem(${idx})">+ Add Icone Social</button>
      `;
    } else if (b.type === 'newsletter') {
      content = `
        <div class="field"><label class="label">Titulo</label><input class="input" value="${b.title || ''}" oninput="upd(${idx}, 'title', this.value)"></div>
        <div class="field"><label class="label">Placeholder do Email</label><input class="input" value="${b.placeholder || ''}" oninput="upd(${idx}, 'placeholder', this.value)"></div>
        <div class="field"><label class="label">Texto do Botao</label><input class="input" value="${b.button || ''}" oninput="upd(${idx}, 'button', this.value)"></div>
      `;
    }

    card.innerHTML = `
      <div class="editor-card-header">
        <div style="display:flex; align-items:center;">
          <span class="drag-handle"><i class="ri-drag-move-line"></i></span>
          <span class="editor-type-badge">${b.type}</span>
        </div>
        <div>
          ${['header', 'divider'].includes(b.type) ? '' : `<button class="button is-small is-white" onclick="openStats()"><i class="ri-bar-chart-line" style="color:#a1a1aa"></i></button>`}
          <button class="button is-small is-white text-danger" onclick="delBlock(${idx})"><i class="ri-delete-bin-line"></i></button>
        </div>
      </div>
      ${content}
    `;
    container.appendChild(card);
  });
}

/* --- ACTIONS --- */
function upd(idx, key, val) {
  state.blocks[idx][key] = val;
  renderAll();
}

function updCard(idx, cardIdx, key, val) {
  state.blocks[idx].cards[cardIdx][key] = val;
  renderAll();
}

function addCardItem(idx) {
  state.blocks[idx].cards = state.blocks[idx].cards || [];
  state.blocks[idx].cards.push({title:'Novo', img:'https://placehold.co/400'});
  renderAll();
}

function updSocial(idx, si, key, val) {
  state.blocks[idx].icons[si][key] = val;
  renderAll();
}

function addSocialItem(idx) {
  state.blocks[idx].icons = state.blocks[idx].icons || [];
  state.blocks[idx].icons.push({icon: 'link', url: ''});
  renderAll();
}

function delSocial(idx, si) {
  state.blocks[idx].icons.splice(si, 1);
  renderAll();
}

function delBlock(idx) {
  if(confirm('Remover bloco?')) {
    state.blocks.splice(idx, 1);
    renderAll();
  }
}

function addBlock(type) {
  const id = 'b' + Date.now();
  let newItem = { id, type };
  if(type === 'header') newItem.text = 'Novo Cabecalho';
  if(type === 'link') newItem = { ...newItem, title: 'Novo Link', sub: 'Descricao', url: '#', icon: 'link' };
  if(type === 'event') newItem = { ...newItem, title: 'Nome do Evento', day: '01', month: 'JAN', url: '#' };
  if(type === 'marquee') newItem.text = 'TEXTO DESTAQUE • ';
  if(type === 'cards') newItem.cards = [{title:'Card 1', img:'https://placehold.co/400'}];
  if(type === 'divider') {}
  if(type === 'text') newItem.text = 'Texto aqui...';
  if(type === 'image') newItem = { ...newItem, img: 'https://placehold.co/600x400', caption: '' };
  if(type === 'video') newItem.url = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
  if(type === 'social') newItem.icons = [];
  if(type === 'newsletter') newItem = { ...newItem, title: 'Assine nossa Newsletter', placeholder: 'Seu email', button: 'Inscrever-se' };
  state.blocks.push(newItem);
  renderAll();
  setTimeout(() => {
    const c = document.getElementById('blocks-container');
    if (c) c.scrollTop = c.scrollHeight;
  }, 50);
}

/* --- PROFILE & TABS --- */
function bindProfileInputs() {
  const ids = ['inp-name', 'inp-bio', 'inp-avatar', 'inp-bg', 'inp-primary', 'inp-accent'];
  const keys = ['name', 'bio', 'avatar', 'bg', 'primary', 'accent'];
  ids.forEach((id, i) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = state.profile[keys[i]] || '';
    if(keys[i] === 'avatar') {
      const prev = document.getElementById('prev-avatar');
      if (prev) prev.src = state.profile.avatar || '';
    }
    el.addEventListener('input', (e) => {
      state.profile[keys[i]] = e.target.value;
      if(keys[i] === 'avatar') {
        const prev = document.getElementById('prev-avatar');
        if (prev) prev.src = e.target.value;
      }
      renderAll();
    });
  });
}

window.switchTab = (tab) => {
  ['editor', 'profile', 'analytics'].forEach(t => {
    const tabEl = document.getElementById('tab-' + t);
    const navEl = document.getElementById('nav-' + t);
    if (tabEl) tabEl.classList.add('is-hidden');
    if (navEl) navEl.classList.remove('is-active');
  });
  const targetTab = document.getElementById('tab-' + tab);
  const targetNav = document.getElementById('nav-' + tab);
  if (targetTab) targetTab.classList.remove('is-hidden');
  if (targetNav) targetNav.classList.add('is-active');
};

/* --- ICON SYSTEM --- */
function populateIconGrid() {
  const grid = document.getElementById('icon-grid');
  if (!grid) return;
  grid.innerHTML = '';
  iconData.forEach(icon => {
    const key = (icon.file || (icon.name + '.svg')).replace(/\.svg$/, '');
    const cls = `icon-${key}`;
    injectIconStyle(cls, key);
    const el = document.createElement('div');
    el.className = 'icon-choice';
    el.setAttribute('data-icon-name', (icon.name || key));
    el.setAttribute('data-icon-key', key);
    el.innerHTML = `
       <div class="p-icon-masked ${cls}"></div>
       <span>${icon.name || key}</span>
    `;
    el.onclick = () => selectIcon(key);
    grid.appendChild(el);
  });
}

function openIconModal(idx, si = null) {
  activeBlockIndex = idx;
  activeSocialIndex = si;
  const modal = document.getElementById('icon-modal');
  if (modal) modal.classList.add('is-open');
}

function selectIcon(name) {
  if(activeBlockIndex !== null) {
    if(activeSocialIndex !== null) {
      state.blocks[activeBlockIndex].icons[activeSocialIndex].icon = name;
    } else {
      state.blocks[activeBlockIndex].icon = name;
    }
    renderAll();
  }
  closeModal('icon-modal');
}

window.filterIcons = (term) => {
  const t = (term || '').toLowerCase();
  const items = document.querySelectorAll('.icon-choice');
  items.forEach(item => {
    const name = (item.getAttribute('data-icon-name') || item.querySelector('span')?.innerText || '').toLowerCase();
    const key = (item.getAttribute('data-icon-key') || '').toLowerCase();
    item.style.display = (name.includes(t) || key.includes(t)) ? 'flex' : 'none';
  });
};

/* --- MODALS --- */
window.closeModal = (id) => {
  const el = document.getElementById(id);
  if (el) el.classList.remove('is-open');
};

window.openStats = () => {
  const m = document.getElementById('stats-modal');
  if (!m) return;
  m.classList.add('is-open');
  const clicks = document.getElementById('st-clicks');
  const ctr = document.getElementById('st-ctr');
  const chart = document.getElementById('st-chart');
  if (clicks) clicks.innerText = Math.floor(Math.random() * 800) + 100;
  if (ctr) ctr.innerText = (Math.random() * 12 + 2).toFixed(1) + '%';
  if (chart) {
    chart.innerHTML = '';
    for(let i=0; i<7; i++){
      const h = Math.floor(Math.random() * 70 + 30);
      const bar = document.createElement('div');
      bar.className = 'chart-bar';
      bar.style.height = '0%';
      chart.appendChild(bar);
      setTimeout(() => bar.style.height = h + '%', 100 + (i*50));
    }
  }
};

window.toggleSidebar = () => {
  document.body.classList.toggle('sidebar-collapsed');
};

/* --- MOBILE PREVIEW FUNCTIONS --- */
function toggleMobilePreview() {
  document.body.classList.toggle('mobile-preview-mode');
  const iconEl = document.querySelector('#preview-toggle i');
  if (!iconEl) return;
  if (document.body.classList.contains('mobile-preview-mode')) {
    iconEl.className = 'pencil-ruler-2-s';
  } else {
    iconEl.className = 'i-eye-s';
  }
}

function nextTexture() {
  currentTextureIndex = (currentTextureIndex + 1) % textureFiles.length;
  updateTexture();
}

function previousTexture() {
  currentTextureIndex = (currentTextureIndex - 1 + textureFiles.length) % textureFiles.length;
  updateTexture();
}

function updateTexture() {
  const textureOverlay = document.getElementById('texture-overlay');
  if (!textureOverlay) return;
  const textureUrl = textureBasePath + textureFiles[currentTextureIndex];
  textureOverlay.style.backgroundImage = `url('${textureUrl}')`;
}

/* --- SAVE LAYOUT --- */
function saveLayout() {
  const payload = {
    profile: state.profile,
    blocks: state.blocks,
    version: 1,
    updatedAt: new Date().toISOString(),
    updatedBy: CURRENT_USER_ID,
  };

  const userId = document.querySelector('[data-user-id]')?.dataset.userId;
  if (!userId) {
    alert('User ID ausente.');
    return;
  }

  const body = new URLSearchParams({
    action: 'apollo_userpage_save',
    nonce: SAVE_NONCE,
    user_id: userId,
    layout: JSON.stringify(payload)
  });

  fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body
  })
  .then(r => r.json())
  .then(resp => {
    if (resp && resp.success) {
      alert('Salvo com sucesso!');
    } else {
      alert('Erro ao salvar: ' + (resp?.data || 'desconhecido'));
    }
  })
  .catch(err => {
    console.error(err);
    alert('Erro de conexao');
  });
}

/* --- RESPONSIVE SCALE ENGINE --- */
(function setupResponsivePhoneScale(){
  const BASE_W = 390;
  const MIN_S = 0.62;
  const MAX_S = 1.00;
  function setScale(){
    const frame = document.querySelector('.phone-frame');
    if(!frame) return;
    const w = frame.getBoundingClientRect().width || BASE_W;
    const s = Math.max(MIN_S, Math.min(MAX_S, w / BASE_W));
    frame.style.setProperty('--s', s.toFixed(4));
  }
  window.addEventListener('resize', setScale, {passive:true});
  document.addEventListener('DOMContentLoaded', () => {
    setScale();
    const frame = document.querySelector('.phone-frame');
    if(frame && 'ResizeObserver' in window){
      new ResizeObserver(setScale).observe(frame);
    }
  });
})();
</script>

<?php get_footer(); ?>
