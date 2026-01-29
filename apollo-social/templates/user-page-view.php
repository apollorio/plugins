<?php
/**
 * Template: Apollo User Page Linktree View (Public)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_query_var( 'apollo_user_id' );
if ( ! $user_id ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

$user = get_userdata( $user_id );
if ( ! $user ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );

$default_state = array(
	'profile' => array(
		'name'    => $user->display_name ?: 'Apollo User',
		'bio'     => 'Bem-vindo ao meu perfil.',
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
	),
);

$saved_layout = $post_id ? get_post_meta( $post_id, 'apollo_userpage_layout_v1', true ) : array();

if ( is_array( $saved_layout ) && isset( $saved_layout['profile'], $saved_layout['blocks'] ) ) {
	$state = $saved_layout;
} else {
	$state = $default_state;
}

if ( function_exists( 'apollo_ensure_base_assets' ) ) {
	apollo_ensure_base_assets();
}

get_header();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css">

<style>
:root {
	--font-sans: "Roboto", Roboto, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
	--font-body: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
	--background: #0b0b0f;
	--foreground: #09090b;
	--primary: <?php echo esc_html( $state['profile']['primary'] ?? '#18181b' ); ?>;
	--brand-orange: <?php echo esc_html( $state['profile']['accent'] ?? '#F97316' ); ?>;
	--radius: 0.5rem;
}
* { box-sizing: border-box; }
body, html { margin:0; padding:0; font-family: var(--font-body); background:#000; color: var(--foreground); }

.page-shell {
	min-height: 100vh;
	background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.04), transparent 30%),
							radial-gradient(circle at 80% 10%, rgba(255,255,255,0.05), transparent 25%),
							#0b0b0f;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40px 16px;
	position: relative;
	overflow: hidden;
}

.texture-overlay {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	background-repeat: repeat;
	mix-blend-mode: screen;
	opacity: 0.35;
	pointer-events: none;
	transition: background-image 0.5s ease;
	filter: blur(0.2px);
}

.phone-frame {
	--s: 1;
	width: min(92vw, 420px);
	aspect-ratio: 9 / 16;
	border-radius: calc(46px * var(--s));
	background: #fff;
	box-shadow:
		0 0 0 calc(12px * var(--s)) #0b0b0f,
		0 calc(25px * var(--s)) calc(50px * var(--s)) calc(-12px * var(--s)) rgba(0,0,0,0.6);
	overflow: hidden;
	position: relative;
	z-index: 2;
}

.dynamic-island { position:absolute; top:calc(12px*var(--s)); left:50%; transform:translateX(-50%); width:50%; height:calc(36px*var(--s)); background:#000; border-radius:calc(20px*var(--s)); z-index:3; }
.phone-screen { width:100%; height:100%; background:#fff; overflow-y:auto; overflow-x:hidden; position:relative; font-size:clamp(12px, calc(16px*var(--s)), 16px); }
.phone-screen::-webkit-scrollbar { width:0; }

.p-bg-layer { position:absolute; inset:0; background-size:cover; background-position:center; opacity:0.18; filter:blur(30px); transform:scale(1.2); z-index:0; }
.p-container { position:relative; z-index:3; padding:calc(4.5rem * var(--s)) calc(1.5rem * var(--s)) calc(2rem * var(--s)); display:flex; flex-direction:column; gap:calc(1rem * var(--s)); min-height:100%; }
.p-header { text-align:center; margin-bottom:1rem; }
.p-avatar { width:clamp(56px, calc(96px * var(--s)), 96px); height:clamp(56px, calc(96px * var(--s)), 96px); border-radius:50%; object-fit:cover; border:clamp(2px, calc(4px * var(--s)), 4px) solid #fff; box-shadow:0 10px 25px rgba(0,0,0,0.12); margin:0 auto clamp(0.6rem, calc(1rem * var(--s)), 1rem); }
.p-name { font-size:clamp(1.05rem, calc(1.25rem * var(--s)), 1.25rem); font-weight:700; color:#18181b; margin-bottom:0.25rem; letter-spacing:-0.025em; }
.p-bio { font-size:clamp(0.78rem, calc(0.875rem * var(--s)), 0.875rem); color:#71717a; line-height:1.45; max-width:90%; margin:0 auto; }

.p-block { background: rgba(255,255,255,0.72); backdrop-filter: blur(12px); border:1px solid rgba(255,255,255,0.55); border-radius:calc(16px * var(--s)); overflow:hidden; text-decoration:none; color:inherit; box-shadow:0 2px 4px rgba(0,0,0,0.06); transition:transform 0.2s, background 0.2s; }
.p-block:hover { background: rgba(255,255,255,0.92); }
.p-link-content { padding:clamp(0.7rem, calc(1rem * var(--s)), 1rem); display:flex; align-items:center; gap:clamp(0.5rem, calc(0.75rem * var(--s)), 0.75rem); }
.p-icon-box { width:clamp(34px, calc(42px * var(--s)), 42px); height:clamp(34px, calc(42px * var(--s)), 42px); border-radius:clamp(8px, calc(10px * var(--s)), 10px); background:#f4f4f5; display:flex; align-items:center; justify-content:center; color:#18181b; }
.p-icon-masked { width:clamp(18px, calc(24px * var(--s)), 24px); height:clamp(18px, calc(24px * var(--s)), 24px); background-color:currentColor; -webkit-mask-size:contain; mask-size:contain; -webkit-mask-repeat:no-repeat; mask-repeat:no-repeat; -webkit-mask-position:center; mask-position:center; }
.p-text-group { flex:1; min-width:0; }
.p-title { font-weight:600; font-size:clamp(0.88rem, calc(0.95rem * var(--s)), 0.95rem); color:#18181b; margin-bottom:2px; }
.p-sub { font-size:clamp(0.68rem, calc(0.75rem * var(--s)), 0.75rem); color:#71717a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.p-event { display:flex; padding:clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem); background:#fff; align-items:center; gap:clamp(0.5rem, calc(0.75rem * var(--s)), 0.75rem); }
.p-event-date { display:flex; flex-direction:column; align-items:center; justify-content:center; width:clamp(48px, calc(60px * var(--s)), 60px); height:clamp(48px, calc(60px * var(--s)), 60px); background:#fafafa; border:1px solid #e4e4e7; border-radius:clamp(10px, calc(12px * var(--s)), 12px); flex-shrink:0; }
.p-day { font-size:clamp(1.05rem, calc(1.25rem * var(--s)), 1.25rem); font-weight:800; color:var(--brand-orange); line-height:1; }
.p-month { font-size:clamp(0.58rem, calc(0.65rem * var(--s)), 0.65rem); text-transform:uppercase; font-weight:700; color:#a1a1aa; margin-top:2px; }

.p-marquee-shell { background:#18181b; color:#fff; padding:clamp(0.55rem, calc(0.75rem * var(--s)), 0.75rem) 0; }
.p-marquee-track { white-space:nowrap; overflow:hidden; display:flex; }
.p-marquee-content { display:inline-block; animation: marquee 12s linear infinite; padding-left:100%; font-weight:600; text-transform:uppercase; font-size:clamp(0.72rem, calc(0.8rem * var(--s)), 0.8rem); letter-spacing:0.05em; }
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-200%); } }

.p-cards-grid { display:grid; grid-template-columns:1fr 1fr; gap:clamp(8px, calc(10px * var(--s)), 10px); }
.p-card-item { position:relative; border-radius:calc(16px * var(--s)); overflow:hidden; aspect-ratio:1/1; border:1px solid rgba(255,255,255,0.5); }
.p-card-img { width:100%; height:100%; object-fit:cover; }
.p-card-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,0.8), transparent); display:flex; align-items:flex-end; padding:clamp(8px, calc(10px * var(--s)), 10px); }
.p-card-title { color:white; font-weight:600; font-size:clamp(0.75rem, calc(0.85rem * var(--s)), 0.85rem); }

.p-divider { border-top:1px solid rgba(255,255,255,0.5); margin:0.5rem 0; }
.p-text { padding:clamp(0.7rem, calc(1rem * var(--s)), 1rem); font-size:clamp(0.78rem, calc(0.875rem * var(--s)), 0.875rem); line-height:1.5; color:#71717a; }
.p-image-img { width:100%; height:auto; border-radius:calc(16px * var(--s)); }
.p-image-caption { text-align:center; font-size:clamp(0.68rem, calc(0.75rem * var(--s)), 0.75rem); color:#71717a; padding:clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem); }
.p-video-iframe { width:100%; aspect-ratio:16/9; border:none; border-radius:calc(16px * var(--s)); }
.p-social { display:flex; justify-content:center; gap:clamp(0.75rem, calc(1rem * var(--s)), 1rem); padding:clamp(0.7rem, calc(1rem * var(--s)), 1rem); }
.p-social-link { color:#18181b; font-size:clamp(1.2rem, calc(1.5rem * var(--s)), 1.5rem); transition:color 0.2s; }
.p-social-link:hover { color:var(--brand-orange); }
.p-newsletter form { display:flex; gap:clamp(0.2rem, calc(0.25rem * var(--s)), 0.25rem); }
.p-newsletter input { flex:1; padding:clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem); border:1px solid #e4e4e7; border-radius:var(--radius); }
.p-newsletter button { padding:clamp(0.4rem, calc(0.5rem * var(--s)), 0.5rem) clamp(0.8rem, calc(1rem * var(--s)), 1rem); background:var(--primary); color:#fff; border:none; border-radius:var(--radius); cursor:pointer; }

@media (max-width: 720px) {
	.page-shell { padding: 20px 10px; }
	.phone-frame { width: min(96vw, 420px); }
}
</style>

<div class="page-shell">
	<div class="texture-overlay" id="texture-overlay"></div>
	<div class="phone-frame">
		<div class="dynamic-island"></div>
		<div class="phone-screen">
			<div id="p-bg" class="p-bg-layer"></div>
			<div class="p-container">
				<header class="p-header">
					<img id="p-avatar" class="p-avatar" src="" alt="">
					<h1 id="p-name" class="p-name"></h1>
					<p id="p-bio" class="p-bio"></p>
				</header>
				<div id="p-blocks-area" style="display:flex; flex-direction:column; gap:0.75rem;"></div>
			</div>
		</div>
	</div>
</div>

<script src="https://assets.apollo.rio.br/icon.js"></script>
<script>
'use strict';

const state = <?php echo wp_json_encode( $state ); ?>;
const textureFiles = [
	"001.jpg", "005.jpg", "008.jpg", "013.jpg", "027.jpg",
	"039.jpg", "057.jpg", "058.jpg", "060.jpg", "148.jpg",
	"150.jpg", "172.jpg", "181.jpg", "182.jpg", "257.jpg",
	"270.jpg", "274.jpg", "328.jpg", "334.jpg", "339.jpg", "357.jpg"
];
const textureBasePath = "https://assets.apollo.rio.br/img/textures/";
let currentTextureIndex = 10;
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

document.addEventListener('DOMContentLoaded', () => {
	updateTheme();
	updateTexture();
	renderPreview();
	setupResponsivePhoneScale();
});

function updateTheme() {
	document.documentElement.style.setProperty('--primary', state.profile?.primary || '#18181b');
	document.documentElement.style.setProperty('--brand-orange', state.profile?.accent || '#F97316');
}

function updateTexture() {
	const overlay = document.getElementById('texture-overlay');
	if (!overlay) return;
	const url = textureBasePath + textureFiles[currentTextureIndex];
	overlay.style.backgroundImage = `url('${url}')`;
}

function renderPreview() {
	document.getElementById('p-name').textContent = state.profile?.name || '';
	document.getElementById('p-bio').textContent = state.profile?.bio || '';
	document.getElementById('p-avatar').src = state.profile?.avatar || '';

	const bg = document.getElementById('p-bg');
	if (state.profile?.bg) {
		bg.style.backgroundImage = `url('${state.profile.bg}')`;
		bg.style.opacity = '0.18';
	} else {
		bg.style.opacity = '0';
	}

	const container = document.getElementById('p-blocks-area');
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
			el.innerHTML = `
				<img class="p-image-img" src="${b.img || ''}" alt="${b.caption || ''}">
				${b.caption ? `<div class="p-image-caption">${b.caption}</div>` : ''}
			`;
		} else if (b.type === 'video') {
			el = document.createElement('div');
			el.innerHTML = `<iframe class="p-video-iframe" src="${b.url || ''}" allowfullscreen></iframe>`;
		} else if (b.type === 'social') {
			el = document.createElement('div');
			el.className = 'p-social';
			(b.icons || []).forEach(icon => {
				const link = document.createElement('a');
				link.className = 'p-social-link';
				link.href = icon.url || '#';
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

function setupResponsivePhoneScale(){
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
	setScale();
	if('ResizeObserver' in window){
		new ResizeObserver(setScale).observe(document.querySelector('.phone-frame'));
	}
}
</script>

<?php get_footer(); ?>
