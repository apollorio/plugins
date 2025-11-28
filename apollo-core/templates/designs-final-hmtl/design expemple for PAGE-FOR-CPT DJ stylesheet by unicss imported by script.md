<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Marta Supernova · Apollo Roster</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Tailwind + UNI -->
  <script src="https://cdn.tailwindcss.com"></script>
   <!-- Motion One + SoundCloud -->
  <script src="https://unpkg.com/@motionone/dom@10.16.4/dist/index.js"></script>
  
  <script src="https://w.soundcloud.com/player/api.js"></script>
  
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">

  <style>
     * { box-sizing: border-box; }
    html, body { font-size: 16px; }
    body {
      margin: 0;
      background: #ffffff;
      color: var(--fly-strong);
      font-family: "Urbanist", system-ui, -apple-system, BlinkMacSystemFont,
        "Segoe UI", Oxygen, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
      font-size: 16px;
    }

   
  </style>
</head>
<body>
<section class="dj-shell">
  <div class="dj-page" id="djPage">
    <div class="dj-content">

      <!-- HEADER -->
      <header class="dj-header">
        <div class="dj-header-left">
          <span>Apollo::rio · DJ Roster</span>
          <strong id="dj-header-name"></strong>
        </div>
        <a href="#" id="mediakit-link" class="dj-pill-link">
          <i class="ri-clipboard-line"></i>Media kit
        </a>
      </header>

      <!-- HERO -->
      <section class="dj-hero" id="djHero">
        <div class="dj-hero-name">
          <div class="dj-tagline" id="dj-tagline"></div>
          <div class="dj-name-main" id="dj-name"></div>
          <div class="dj-name-sub" id="dj-roles"></div>
          <div class="dj-projects" id="dj-projects"></div>
        </div>
        <figure class="dj-hero-photo" id="djPhoto">
          <img id="dj-avatar" src="" alt="DJ Photo">
        </figure>
      </section>

      <!-- PLAYER -->
      <section class="dj-player-block" id="djPlayerBlock">
        <div>
          <div class="dj-player-title">Feature set para escuta</div>
          <div class="dj-player-sub" id="track-title"></div>
        </div>

        <main class="vinyl-zone">
          <div
            class="vinyl-player is-paused"
            id="vinylPlayer"
            role="button"
            aria-label="Play / Pause set"
          >
            <div class="vinyl-shadow"></div>

            <div class="vinyl-disc">
              <div class="vinyl-beam"></div>
              <div class="vinyl-rings"></div>

              <div class="vinyl-label">
                <div class="vinyl-label-text" id="vinylLabelText">
                  MARTA<br>SUPERNOVA
                </div>
              </div>

              <div class="vinyl-hole"></div>
            </div>

            <div class="tonearm">
              <div class="tonearm-base"></div>
              <div class="tonearm-shaft"></div>
              <div class="tonearm-head"></div>
            </div>
          </div>
        </main>

        <p class="now-playing">
          Set de referência em destaque no <strong>SoundCloud</strong>.
        </p>

        <iframe id="scPlayer" scrolling="no" frameborder="no" allow="autoplay" src=""></iframe>

        <div class="player-cta-row">
          <button class="btn-player-main" id="vinylToggle" type="button">
            <i class="ri-play-fill" id="vinylIcon"></i>
            <span>Play / Pause set</span>
          </button>
          <p class="player-note">
            Contato e condições completas no media kit e rider técnico.
          </p>
        </div>
      </section>

      <!-- INFO GRID -->
      <section class="dj-info-grid">
        <div class="dj-info-block">
          <h2>Sobre</h2>
          <div class="dj-bio-excerpt" id="dj-bio-excerpt"></div>
          <button type="button" class="dj-bio-toggle" id="bioToggle">
            <span>ler bio completa</span>
            <i class="ri-arrow-right-up-line"></i>
          </button>
        </div>
        <div class="dj-info-block">
          <h2>Links principais</h2>

          <div>
            <div class="dj-links-label">Música</div>
            <div class="dj-links-row" id="music-links"></div>
          </div>
          <div>
            <div class="dj-links-label">Social</div>
            <div class="dj-links-row" id="social-links"></div>
          </div>
          <div>
            <div class="dj-links-label">Assets</div>
            <div class="dj-links-row" id="asset-links"></div>
          </div>
          <p class="more-platforms" id="more-platforms"></p>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="dj-footer">
        <span>Apollo::rio<br>Roster preview</span>
        <span>Para bookers,<br>selos e clubes</span>
      </footer>
    </div>
  </div>
</section>

<!-- BIO MODAL -->
<div class="dj-bio-modal-backdrop" id="bioBackdrop" data-open="false">
  <div class="dj-bio-modal">
    <div class="dj-bio-modal-header">
      <h3 id="dj-bio-modal-title">Bio completa</h3>
      <button type="button" class="dj-bio-modal-close" id="bioClose">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="dj-bio-modal-body" id="bio-full"></div>
  </div>
</div>

<script>
// ------------------------------------------------------------
// GLOBALS for SoundCloud
// ------------------------------------------------------------
let scWidget = null;
let widgetReady = false;

// ------------------------------------------------------------
// DATA CONFIGURATION
// ------------------------------------------------------------
const DJ_DATA = {
  name: "Marta Supernova",
  tagline: "Electro narratives from Rio BRA",
  roles: "DJ · Producer · Live Selector",

  avatar: "http://localhost:10004/wp-content/uploads/2025/11/Sorriencia-e-Eventos-na-Praia-683x1024.png",

  projects: ["Apollo::rio", "Dismantle"],

  bioExcerpt:
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam lorem magna, tincidunt sit amet tempus vitae, sagittis ut nunc. Cras lacus lacus, vestibulum non viverra nec, dapibus volutpat purus. Ut mollis, arcu a sollicitudin luctus, turpis sem volutpat ex, ac convallis sapien libero vel massa.",

  bioFull: `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam lorem magna, tincidunt sit amet tempus vitae, sagittis ut nunc. Cras lacus lacus, vestibulum non viverra nec, dapibus volutpat purus. Ut mollis, arcu a sollicitudin luctus, turpis sem volutpat ex, ac convallis sapien libero vel massa. Nam tincidunt turpis accumsan, gravida ligula et, consectetur felis. Nulla tristique pharetra varius. Aliquam non consectetur mauris. Nulla hendrerit vel ligula eget egestas.
Nam viverra augue nec sem suscipit posuere. Phasellus maximus tempus orci, a dignissim mauris lobortis sed. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Mauris sagittis metus eget massa viverra, a malesuada odio pretium.
Curabitur sed mi sagittis, lacinia nibh a, fermentum leo. Sed commodo feugiat risus, maximus sollicitudin sapien ultrices vel. Vivamus augue justo, imperdiet eu mi ac, fringilla porttitor nunc.`,

  soundcloudTrack: "https://soundcloud.com/antoninlb/gilberto-gil-palco-zumble-disco-house-remix-papaya",

  trackTitle: "Gilberto Gil – Palco (Zumble Disco House Remix)",

  musicLinks: [
    { label: "SoundCloud", icon: "ri-soundcloud-line", url: "https://soundcloud.com", active: true },
    { label: "Spotify", icon: "ri-spotify-line", url: "https://spotify.com", active: false },
    { label: "YouTube", icon: "ri-youtube-line", url: "https://youtube.com", active: false }
  ],

  socialLinks: [
    { label: "Instagram", icon: "ri-instagram-line", url: "https://instagram.com/apollo.rio.br" },
    { label: "Facebook", icon: "ri-facebook-circle-line", url: "https://facebook.com/apollo.rio.br" },
    { label: "Twitter", icon: "ri-twitter-x-line", url: "https://twitter.com/apollo.rio.br" },
    { label: "TikTok", icon: "ri-tiktok-line", url: "https://tiktok.com/@apollo.rio.br" }
  ],

  assetLinks: [
    { label: "Media kit", icon: "ri-clipboard-line", url: "https://drive.google.com" },
    { label: "Rider", icon: "ri-clipboard-fill", url: "https://drive.google.com" },
    { label: "Mix / Playlist", icon: "ri-play-list-2-line", url: "https://drive.google.com" }
  ],

  mediakitUrl: "https://drive.google.com",

  morePlatforms: "Mixcloud · Beatport · Bandcamp · Resident Advisor · Site oficial"
};

// ------------------------------------------------------------
// HELPERS
// ------------------------------------------------------------
function toggleVinylPlayback() {
  if (!widgetReady || !scWidget) {
    console.log("SoundCloud widget não está pronto ainda");
    return;
  }

  scWidget.isPaused((paused) => {
    if (paused) {
      scWidget.play();
    } else {
      scWidget.pause();
    }
  });
}

// ------------------------------------------------------------
// APP INITIALIZATION
// ------------------------------------------------------------
function initDJRoster() {
  const { animate } = window.Motion || {};

  // Basic info
  document.getElementById("dj-header-name").textContent = DJ_DATA.name.toUpperCase();
  document.getElementById("dj-tagline").textContent = DJ_DATA.tagline;
  document.getElementById("dj-name").innerHTML = DJ_DATA.name.split(" ").join("<br>");
  document.getElementById("dj-roles").textContent = DJ_DATA.roles;
  document.getElementById("track-title").textContent = DJ_DATA.trackTitle;

  const vinylLabelText = document.getElementById("vinylLabelText");
  if (vinylLabelText) {
    vinylLabelText.innerHTML = DJ_DATA.name.split(" ").join("<br>");
  }

  const avatar = document.getElementById("dj-avatar");
  avatar.src = DJ_DATA.avatar;
  avatar.alt = `Retrato de ${DJ_DATA.name}`;

  const mediaKitLink = document.getElementById("mediakit-link");
  mediaKitLink.href = DJ_DATA.mediakitUrl;

  // Projects
  const projectsContainer = document.getElementById("dj-projects");
  DJ_DATA.projects.forEach((project, i) => {
    const span = document.createElement("span");
    span.textContent = project;
    if (i === 0) span.style.fontWeight = "800";
    projectsContainer.appendChild(span);
  });

  // Bio
  document.getElementById("dj-bio-excerpt").textContent = DJ_DATA.bioExcerpt;
  document.getElementById("bio-full").innerHTML = DJ_DATA.bioFull
    .split("\n\n")
    .map((p) => `<p>${p}</p>`)
    .join("");
  document.getElementById("dj-bio-modal-title").textContent = `Bio completa · ${DJ_DATA.name}`;

  // Music links
  const musicContainer = document.getElementById("music-links");
  DJ_DATA.musicLinks.forEach((link) => {
    const a = document.createElement("a");
    a.href = link.url;
    a.className = `dj-link-pill ${link.active ? "active" : ""}`;
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.innerHTML = `<i class="${link.icon}"></i> ${link.label}`;
    musicContainer.appendChild(a);
  });

  // Social links
  const socialContainer = document.getElementById("social-links");
  DJ_DATA.socialLinks.forEach((link) => {
    const a = document.createElement("a");
    a.href = link.url;
    a.className = "dj-link-pill";
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.innerHTML = `<i class="${link.icon}"></i> ${link.label}`;
    socialContainer.appendChild(a);
  });

  // Asset links
  const assetContainer = document.getElementById("asset-links");
  DJ_DATA.assetLinks.forEach((link) => {
    const a = document.createElement("a");
    a.href = link.url;
    a.className = "dj-link-pill";
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.innerHTML = `<i class="${link.icon}"></i> ${link.label}`;
    assetContainer.appendChild(a);
  });

  // More platforms
  document.getElementById("more-platforms").innerHTML =
    `<span>More platforms:</span> ${DJ_DATA.morePlatforms}`;

  // SoundCloud setup
  const scIframe = document.getElementById("scPlayer");
  const scUrl = encodeURIComponent(DJ_DATA.soundcloudTrack);
  scIframe.src =
    `https://w.soundcloud.com/player/?url=${scUrl}` +
    "&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false" +
    "&show_user=false&show_reposts=false&show_teaser=false";

  const vinylPlayer = document.getElementById("vinylPlayer");
  const vinylToggle = document.getElementById("vinylToggle");
  const vinylIcon = document.getElementById("vinylIcon");

  function initWidget() {
    if (typeof SC === "undefined" || !SC.Widget) {
      setTimeout(initWidget, 100);
      return;
    }

    scWidget = SC.Widget(scIframe);

    scWidget.bind(SC.Widget.Events.READY, () => {
      widgetReady = true;
      console.log("SoundCloud widget pronto");
    });

    scWidget.bind(SC.Widget.Events.PLAY, () => {
      vinylPlayer.classList.add("is-playing");
      vinylPlayer.classList.remove("is-paused");
      vinylIcon.className = "ri-pause-fill";
    });

    scWidget.bind(SC.Widget.Events.PAUSE, () => {
      vinylPlayer.classList.remove("is-playing");
      vinylPlayer.classList.add("is-paused");
      vinylIcon.className = "ri-play-fill";
    });

    scWidget.bind(SC.Widget.Events.FINISH, () => {
      vinylPlayer.classList.remove("is-playing");
      vinylPlayer.classList.add("is-paused");
      vinylIcon.className = "ri-play-fill";
    });
  }

  setTimeout(initWidget, 500);

  // Play/Pause handlers (button + vinil)
  vinylToggle.addEventListener("click", toggleVinylPlayback);
  vinylPlayer.addEventListener("click", toggleVinylPlayback);

  // Bio Modal
  const bioBackdrop = document.getElementById("bioBackdrop");
  const bioToggle = document.getElementById("bioToggle");
  const bioClose = document.getElementById("bioClose");

  bioToggle.addEventListener("click", () => {
    bioBackdrop.dataset.open = "true";
    if (animate) animate(bioBackdrop, { opacity: [0, 1] }, { duration: 0.3 });
  });

  bioClose.addEventListener("click", () => {
    if (animate) {
      animate(bioBackdrop, { opacity: [1, 0] }, { duration: 0.2 }).finished.then(() => {
        bioBackdrop.dataset.open = "false";
      });
    } else {
      bioBackdrop.dataset.open = "false";
    }
  });

  bioBackdrop.addEventListener("click", (e) => {
    if (e.target === bioBackdrop) bioClose.click();
  });

  // Animations (page)
  if (animate) {
    animate(
      document.getElementById("djPage"),
      { opacity: [0, 1], y: [20, 0] },
      { duration: 0.6, easing: [0.25, 0.8, 0.25, 1] }
    );
    animate(
      document.getElementById("djHero"),
      { opacity: [0, 1], y: [15, 0] },
      { duration: 0.5, delay: 0.15, easing: [0.25, 0.8, 0.25, 1] }
    );
    animate(
      document.getElementById("djPhoto"),
      { opacity: [0, 1], scale: [0.95, 1] },
      { duration: 0.5, delay: 0.2, easing: [0.25, 0.8, 0.25, 1] }
    );
    animate(
      document.getElementById("djPlayerBlock"),
      { opacity: [0, 1], y: [15, 0] },
      { duration: 0.5, delay: 0.3, easing: [0.25, 0.8, 0.25, 1] }
    );

    vinylPlayer.addEventListener("mouseenter", () =>
      animate(vinylPlayer, { scale: 1.03 }, { duration: 0.3 })
    );
    vinylPlayer.addEventListener("mouseleave", () =>
      animate(vinylPlayer, { scale: 1 }, { duration: 0.3 })
    );
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initDJRoster);
} else {
  initDJRoster();
}
</script>

  <script src="https://assets.apollo.rio.br/base.js"></script>
</body>
</html>
