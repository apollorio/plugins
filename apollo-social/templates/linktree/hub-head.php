<?php
/**
 * Template Part: HUB::rio Linktree - Head Section
 * Contains all CSS and base styles for the Linktree editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="antialiased">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>HUB::rio — Apollo Linktree Editor</title>

  <!-- Apollo CDN -->
  <script src="https://cdn.apollo.rio.br"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css">

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

    /* --- 7. BLOCK PICKER (Mega Dropdown) --- */
    .add-block-container {
      position: relative;
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1rem;
    }

    .add-block-btn {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--brand-orange), #ea580c);
      color: white;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 4px 14px rgba(249, 115, 22, 0.4);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 11;
    }
    .add-block-btn:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5);
    }
    .add-block-btn.is-active {
      transform: rotate(45deg);
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .block-picker-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(4px);
      z-index: 30;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
    }
    .block-picker-overlay.is-open {
      opacity: 1;
      pointer-events: all;
    }

    .block-picker-panel {
      position: absolute;
      top: 60px;
      right: 0;
      width: 320px;
      max-height: 480px;
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      z-index: 31;
      overflow: hidden;
      opacity: 0;
      transform: translateY(-10px) scale(0.95);
      pointer-events: none;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .block-picker-panel.is-open {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: all;
    }

    .block-picker-header {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
    }
    .block-picker-search-wrapper {
      position: relative;
    }
    .block-picker-search {
      width: 100%;
      padding: 0.6rem 0.75rem 0.6rem 2.25rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.875rem;
      transition: border-color 0.15s;
    }
    .block-picker-search:focus {
      border-color: var(--brand-orange);
      outline: none;
    }
    .block-picker-search-icon {
      position: absolute;
      left: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted-foreground);
      font-size: 1rem;
    }

    .block-picker-body {
      max-height: 400px;
      overflow-y: auto;
      padding: 0.75rem;
    }
    .block-picker-body::-webkit-scrollbar {
      width: 4px;
    }
    .block-picker-body::-webkit-scrollbar-thumb {
      background: #e4e4e7;
      border-radius: 4px;
    }

    .block-category {
      margin-bottom: 1rem;
    }
    .block-category:last-child {
      margin-bottom: 0;
    }
    .block-category-label {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted-foreground);
      padding: 0 0.25rem;
      margin-bottom: 0.5rem;
    }

    .block-grid {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .block-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.6rem 0.75rem;
      background: var(--secondary);
      border: 1px solid transparent;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .block-option:hover {
      background: white;
      border-color: var(--brand-orange);
      box-shadow: 0 2px 8px rgba(249, 115, 22, 0.15);
    }

    .block-option-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: linear-gradient(135deg, var(--brand-orange), #ea580c);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .block-option-info {
      flex: 1;
      min-width: 0;
    }
    .block-option-title {
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--foreground);
      margin-bottom: 2px;
    }
    .block-option-desc {
      font-size: 0.7rem;
      color: var(--muted-foreground);
      line-height: 1.3;
    }

    /* --- 8. EMPTY STATE --- */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--muted-foreground);
    }
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.3;
    }
    .empty-state p {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--foreground);
    }
    .empty-state span {
      font-size: 0.875rem;
    }

    /* --- 9. EDITOR CARD ACTIONS --- */
    .editor-card-actions {
      display: flex;
      gap: 4px;
    }
    .btn-icon {
      width: 28px;
      height: 28px;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--muted-foreground);
      transition: all 0.15s;
    }
    .btn-icon:hover {
      background: var(--secondary);
      color: var(--foreground);
    }
    .btn-icon.btn-danger:hover {
      background: #fef2f2;
      color: var(--destructive);
    }

    .btn-group {
      display: flex;
      gap: 4px;
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.875rem;
      cursor: pointer;
    }
    .checkbox-label input[type="checkbox"] {
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    /* --- 10. STYLE MODAL --- */
    #styleModal .modal-body {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    #styleModal .field-full {
      grid-column: 1 / -1;
    }

    .color-input-wrapper {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .color-input-wrapper input[type="color"] {
      width: 40px;
      height: 32px;
      border: 1px solid var(--border);
      border-radius: 6px;
      cursor: pointer;
      padding: 2px;
    }
    .color-input-wrapper input[type="text"] {
      flex: 1;
    }

    .range-input-wrapper {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .range-input-wrapper input[type="range"] {
      width: 100%;
      cursor: pointer;
    }
    .range-value {
      font-size: 0.75rem;
      color: var(--muted-foreground);
      text-align: right;
    }

    .modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    /* --- 11. PREVIEW BLOCK STYLES --- */
    .p-card-simple {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem;
      text-decoration: none;
    }
    .p-card-simple .p-card-title {
      font-weight: 600;
      color: inherit;
    }

    .p-card-icon {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      text-decoration: none;
    }
    .p-card-icon.icon-right {
      flex-direction: row-reverse;
    }

    .p-image-link img,
    .p-image-overlay img {
      width: 100%;
      height: auto;
      display: block;
    }

    .p-image-overlay {
      position: relative;
      display: block;
    }
    .p-overlay-title {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 1rem;
      background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
      color: white;
      font-weight: 600;
    }

    .p-event-external {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      text-decoration: none;
    }
    .p-date-box {
      width: 50px;
      text-align: center;
      flex-shrink: 0;
    }
    .p-date-box .p-day {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--brand-orange);
    }
    .p-date-box .p-month {
      font-size: 0.65rem;
      text-transform: uppercase;
      font-weight: 700;
      color: var(--muted-foreground);
    }

    .p-event-internal {
      display: flex;
      gap: 0.75rem;
      padding: 0.5rem;
      text-decoration: none;
    }
    .p-event-thumb {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background-size: cover;
      background-position: center;
      flex-shrink: 0;
    }
    .p-event-info {
      flex: 1;
      min-width: 0;
    }
    .p-event-info .p-event-date {
      font-size: 0.7rem;
      color: var(--brand-orange);
      font-weight: 600;
    }
    .p-event-info .p-event-title {
      font-weight: 600;
      font-size: 0.9rem;
    }

    .p-youtube iframe,
    .p-soundcloud iframe,
    .p-spotify iframe {
      width: 100%;
      border: none;
      border-radius: 8px;
    }
    .p-youtube iframe {
      aspect-ratio: 16/9;
    }

    .p-section-title {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted-foreground);
      margin-bottom: 0.75rem;
    }

    .p-testimonials {
      padding: 1rem;
    }
    .p-testimonial {
      padding: 0.75rem;
      background: var(--secondary);
      border-radius: 8px;
      margin-bottom: 0.5rem;
    }
    .p-testimonial:last-child {
      margin-bottom: 0;
    }
    .p-testimonial-stars {
      color: #fbbf24;
      margin-bottom: 0.25rem;
    }
    .p-testimonial-text {
      font-size: 0.85rem;
      color: var(--foreground);
      font-style: italic;
      margin-bottom: 0.25rem;
    }
    .p-testimonial-author {
      font-size: 0.75rem;
      color: var(--muted-foreground);
    }

    .p-rating {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 1rem;
      gap: 0.5rem;
    }
    .p-rating-label {
      font-size: 0.85rem;
      font-weight: 600;
    }
    .p-rating-stars {
      font-size: 1.5rem;
      color: #fbbf24;
    }
    .p-rating-value {
      font-size: 0.75rem;
      color: var(--muted-foreground);
    }

    .p-orkut-rate {
      display: flex;
      justify-content: space-around;
      padding: 1rem;
      text-align: center;
    }
    .p-orkut-item {
      cursor: pointer;
      transition: transform 0.2s;
    }
    .p-orkut-item:hover {
      transform: scale(1.05);
    }
    .p-orkut-icons {
      font-size: 1.25rem;
      margin-bottom: 4px;
    }
    .p-orkut-count {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--foreground);
    }
    .p-orkut-label {
      font-size: 0.65rem;
      color: var(--muted-foreground);
      text-transform: uppercase;
      font-weight: 600;
    }

    .p-social-links {
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      padding: 1rem;
      background: transparent;
      border: none;
    }
    .p-social-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--secondary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--foreground);
      font-size: 1.1rem;
      transition: all 0.2s;
      text-decoration: none;
    }
    .p-social-icon:hover {
      background: var(--brand-orange);
      color: white;
    }

    .p-share {
      text-align: center;
      padding: 1rem;
    }
    .p-share-label {
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      color: var(--muted-foreground);
      margin-bottom: 0.75rem;
    }
    .p-share-buttons {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
    }
    .p-share-buttons button {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .p-share-buttons button:hover {
      background: var(--brand-orange);
      border-color: var(--brand-orange);
      color: white;
    }

    .p-user-bubble {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      padding: 1rem;
      background: transparent;
      border: none;
    }
    .p-user-bubble img {
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid white;
      box-shadow: var(--shadow-md);
    }
    .p-user-bubble.bubble-small img { width: 48px; height: 48px; }
    .p-user-bubble.bubble-medium img { width: 72px; height: 72px; }
    .p-user-bubble.bubble-large img { width: 96px; height: 96px; }

    .p-coauthors,
    .p-latest-news,
    .p-events-interested {
      padding: 1rem;
    }

    .p-marquee {
      overflow: hidden;
      background: var(--primary);
      color: white;
      padding: 0.75rem 0;
    }
    .p-marquee-track {
      display: flex;
      white-space: nowrap;
      animation: marquee 12s linear infinite;
    }
    .marquee-slow .p-marquee-track { animation-duration: 20s; }
    .marquee-fast .p-marquee-track { animation-duration: 6s; }
    .p-marquee-text {
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-size: 0.8rem;
    }

    .p-text {
      padding: 1rem;
      line-height: 1.6;
    }

    .p-divider {
      margin: 0.5rem 1rem;
      border: none;
      border-top: 1px solid var(--border);
    }

    .orkut-rate-display {
      display: flex;
      gap: 1rem;
      padding: 0.5rem;
      background: var(--secondary);
      border-radius: 8px;
    }
    .orkut-item {
      flex: 1;
      text-align: center;
    }
    .orkut-item span {
      font-size: 0.7rem;
      color: var(--muted-foreground);
    }
    .orkut-item strong {
      display: block;
      font-size: 1.1rem;
      color: var(--foreground);
    }

    /* --- 12. TOAST NOTIFICATIONS --- */
    .hub-toast {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%) translateY(100px);
      padding: 0.75rem 1.5rem;
      background: var(--primary);
      color: var(--primary-foreground);
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 500;
      box-shadow: var(--shadow-xl);
      z-index: 200;
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hub-toast.show {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
    }
    .hub-toast--success {
      background: #22c55e;
    }
    .hub-toast--error {
      background: var(--destructive);
    }

    /* --- 13. AVATAR HERO (Morphing Animation) --- */
    .avatar-hero {
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      margin: 0 auto 1rem;
    }

    .avatar-hero-box {
      width: clamp(80px, 25vmin, 120px);
      height: clamp(80px, 25vmin, 120px);
      border: 1px dashed rgba(34,34,34,0.25);
      position: relative;
    }

    .avatar-hero-box::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
      border-radius: 50%;
      border: 1px dashed rgba(34,34,34,0.25);
      transform: scale(1.42);
    }

    .avatar-hero-spin {
      width: 100%;
      height: 100%;
      animation: avatar-spin 12s ease-in-out infinite alternate;
      position: relative;
    }

    .avatar-hero-shape {
      width: 100%;
      height: 100%;
      transition: border-radius 1s ease-out;
      border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
      animation: avatar-morph 8s ease-in-out infinite both alternate;
      position: absolute;
      overflow: hidden;
      z-index: 5;
    }

    .avatar-hero-image {
      width: 142%;
      height: 142%;
      position: absolute;
      left: -21%;
      top: -21%;
      background-size: 100%;
      background-position: center center;
      display: flex;
      color: #003;
      font-size: 5vw;
      font-weight: bold;
      align-items: center;
      justify-content: center;
      text-align: center;
      text-transform: uppercase;
      animation: avatar-spin 12s ease-in-out infinite alternate-reverse;
      opacity: 1;
      z-index: 2;
    }

    @keyframes avatar-morph {
      0% { border-radius: 40% 60% 60% 40% / 60% 30% 70% 40%; }
      100% { border-radius: 40% 60%; }
    }

    @keyframes avatar-spin {
      to {
        transform: rotate(1turn);
      }
    }

    /* --- 14. TEXT BLOCK STYLES --- */
    .p-title-block {
      padding: 0.75rem 1rem;
      font-weight: 700;
      line-height: 1.3;
    }

    .p-paragraph-block {
      padding: 0.75rem 1rem;
      line-height: 1.6;
      font-size: 0.9rem;
    }

    .p-bio-block {
      padding: 0.5rem 1rem;
      line-height: 1.5;
      font-size: 0.85rem;
      font-style: italic;
      color: var(--muted-foreground);
    }
  </style>

  <?php wp_head(); ?>
</head>
