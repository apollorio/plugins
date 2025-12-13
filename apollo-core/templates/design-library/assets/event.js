/* Preparations on PHP file:

<?php
$gallery_promo_slider = get_post_meta(get_the_ID(), '_3_imagens_promo', true);
$local_lat = get_post_meta(get_the_ID(), '_local_latitude', true);
$local_long = get_post_meta(get_the_ID(), '_local_longitude', true);
$local_address = get_post_meta(get_the_ID(), '_local_address', true);
?>

<div id="promoTrack" class="promo-track">
  <?php if (!empty($gallery_promo_slider) && is_array($gallery_promo_slider)) : ?>
    <?php foreach ($gallery_promo_slider as $img_id) : ?>
      <div class="promo-slide">
        <img src="<?php echo esc_url(wp_get_attachment_url($img_id)); ?>" alt="">
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<div class="promo-prev">‹</div>
<div class="promo-next">›</div>

<div id="eventMap"
     data-lat="<?php echo esc_attr($local_lat); ?>"
     data-lng="<?php echo esc_attr($local_long); ?>"
     data-address="<?php echo esc_attr($local_address); ?>"></div>
     
     
     # ENQUEUE THIS JS 
     
     wp_enqueue_script(
  'apollo-events',
  get_stylesheet_directory_uri() . '/assets/js/apollo-events.js',
  [],
  '1.0',
  true
);

*/

document.addEventListener('DOMContentLoaded', () => {
  const iconContainer = document.querySelector('.icon-container');
  const icon = iconContainer.querySelector('i');

  iconContainer.addEventListener('click', (e) => {
    e.preventDefault();

    // Ensure transparent background before any animation (already set in CSS, but reinforce if needed)
    iconContainer.style.background = 'transparent';
    icon.style.background = 'transparent';

    if (icon.classList.contains('ri-rocket-line')) {
      // Favorite action with rocket animation
      iconContainer.classList.add('fly-away');

      setTimeout(() => {
        iconContainer.classList.remove('fly-away');
        icon.className = 'ri-ai-agent-fill fade-in';
        iconContainer.style.borderColor = 'rgba(0,0,0,0.2)';
      }, 1500);

      // Rest of the favorite logic (avatars, etc.)
      let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
      let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;

      if (visibleCount < maxVisible) {
        const newAvatar = document.createElement('div');
        newAvatar.classList.add('avatar');
        const gender = Math.random() < 0.5 ? 'men' : 'women';
        const id = Math.floor(Math.random() * 99) + 1;
        newAvatar.style.backgroundImage = `url('https://randomuser.me/api/portraits/${gender}/${id}.jpg')`;
        avatarsContainer.insertBefore(newAvatar, countEl);
      } else {
        hiddenCount += 1;
        countEl.textContent = `+${hiddenCount}`;
      }

      updateResult();
    } else {
      // Unfavorite action
      let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
      let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
      let total = visibleCount + hiddenCount;

      icon.className = 'ri-rocket-line fade-in';
    }
  });

  // Initial update
  const avatarsContainer = document.querySelector('.avatars-explosion');
  const countEl = avatarsContainer.querySelector('.avatar-count');
  const resultEl = document.getElementById('result');

  function initialUpdateResult() {
    const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
    const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    resultEl.textContent = visibleCount + hiddenCount;
  }

  initialUpdateResult();
});


document.getElementById('favoriteTrigger').addEventListener('click', function (event) {
  event.preventDefault();

  const iconContainer = this.querySelector('.quick-action-icon');
  const icon = iconContainer.querySelector('i');
  const avatarsContainer = document.querySelector('.avatars-explosion');
  const countEl = avatarsContainer.querySelector('.avatar-count');
  const resultEl = document.getElementById('result');
  const maxVisible = 10;

  function updateResult() {
    const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
    const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    resultEl.textContent = visibleCount + hiddenCount;
  }

  if (icon.classList.contains('ri-rocket-line')) {
    // Favorite action
    iconContainer.classList.add('fly-away');

    setTimeout(() => {
      iconContainer.classList.remove('fly-away');
      icon.className = 'ri-ai-agent-fill fade-in';
      iconContainer.style.borderColor = 'rgba(0,0,0,0.2)';
    }, 1500);

    let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;

    if (visibleCount < maxVisible) {
      const newAvatar = document.createElement('div');
      newAvatar.classList.add('avatar');
      const gender = Math.random() < 0.5 ? 'men' : 'women';
      const id = Math.floor(Math.random() * 99) + 1;
      newAvatar.style.backgroundImage = `url('https://randomuser.me/api/portraits/${gender}/${id}.jpg')`;
      avatarsContainer.insertBefore(newAvatar, countEl);
    } else {
      hiddenCount += 1;
      countEl.textContent = `+${hiddenCount}`;
    }

    updateResult();
  } else {
    // Unfavorite action
    let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
    let total = visibleCount + hiddenCount;

    if (total > 0) {
      if (hiddenCount > 0) {
        hiddenCount -= 1;
        countEl.textContent = hiddenCount > 0 ? `+${hiddenCount}` : '';
      } else if (visibleCount > 0) {
        const lastAvatar = avatarsContainer.querySelector('.avatar:last-of-type');
        if (lastAvatar) lastAvatar.remove();
      }
      updateResult();
    }

    icon.className = 'ri-rocket-line fade-in';
  }
});

// Initial update
const avatarsContainer = document.querySelector('.avatars-explosion');
const countEl = avatarsContainer.querySelector('.avatar-count');
const resultEl = document.getElementById('result');

function initialUpdateResult() {
  const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
  const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
  resultEl.textContent = visibleCount + hiddenCount;
}

initialUpdateResult();

(function () {
  var words = [
    'Entradas',
    'Ingressos',
    'Billets',
    'Ticket',
    'Acessos',
    'Biglietti'
  ], i = 0;
  var elem = document.getElementById('changingword');
  // set initial word
  if (elem) {
    elem.textContent = words[i];
  }
  function fadeOut(el, duration, callback) {
    el.style.opacity = 1;
    var start = null;
    function step(timestamp) {
      if (!start) start = timestamp;
      var progress = timestamp - start;
      var fraction = progress / duration;
      if (fraction < 1) {
        el.style.opacity = 1 - fraction;
        window.requestAnimationFrame(step);
      } else {
        el.style.opacity = 0;
        if (callback) callback();
      }
    }
    window.requestAnimationFrame(step);
  }
  function fadeIn(el, duration, callback) {
    el.style.opacity = 0;
    el.style.display = ''; // reset any display none
    var start = null;
    function step(timestamp) {
      if (!start) start = timestamp;
      var progress = timestamp - start;
      var fraction = progress / duration;
      if (fraction < 1) {
        el.style.opacity = fraction;
        window.requestAnimationFrame(step);
      } else {
        el.style.opacity = 1;
        if (callback) callback();
      }
    }
    window.requestAnimationFrame(step);
  }
  setInterval(function () {
    if (!elem) return;
    fadeOut(elem, 400, function () {
      // after fadeOut
      i = (i + 1) % words.length;
      elem.textContent = words[i];
      fadeIn(elem, 400);
    });
  }, 4000);
})();




'use strict';
document.addEventListener('DOMContentLoaded', () => {

  /* ==============================
     PROMO GALLERY SLIDER (for _3_imagens_promo)
     ============================== */
  function initPromoSlider() {
    const promoTrack = document.getElementById('promoTrack');
    if (!promoTrack) return;

    const slides = promoTrack.querySelectorAll('.promo-slide');
    const prevBtn = document.querySelector('.promo-prev');
    const nextBtn = document.querySelector('.promo-next');
    let current = 0;

    function updateSlider() {
      const width = slides[0].offsetWidth;
      promoTrack.style.transform = `translateX(-${current * width}px)`;
    }

    prevBtn?.addEventListener('click', () => {
      current = (current - 1 + slides.length) % slides.length;
      updateSlider();
    });
    nextBtn?.addEventListener('click', () => {
      current = (current + 1) % slides.length;
      updateSlider();
    });

    // Auto-slide every 5s
    setInterval(() => {
      current = (current + 1) % slides.length;
      updateSlider();
    }, 5000);
  }
  /* ==============================
      LOCAL IMAGES SLIDER (for _local_logo or gallery)
      ============================== */
  function initLocalSlider() {
    const localTrack = document.getElementById('localTrack');
    const localDots = document.getElementById('localDots');
    if (!localTrack || !localTrack.children.length) return;

    const slides = localTrack.children;
    const total = slides.length;
    let current = 0;

    // Create navigation dots dynamically
    for (let i = 0; i < total; i++) {
      const dot = document.createElement('div');
      dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', () => goTo(i));
      localDots.appendChild(dot);
    }

    function goTo(index) {
      current = index;
      localTrack.style.transition = 'transform 0.5s ease';
      localTrack.style.transform = `translateX(-${index * 100}%)`;
      updateDots();
    }

    function updateDots() {
      localDots.querySelectorAll('.slider-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === current);
      });
    }

    // Auto-loop
    setInterval(() => {
      current = (current + 1) % total;
      goTo(current);
    }, 4000);
  }



  /* ==============================
       EVENT MAP (Leaflet + data attributes)
       ============================== */
  function initEventMap() {
    const mapEl = document.getElementById('eventMap');
    if (!mapEl || typeof L === 'undefined') return;

    const lat = parseFloat(mapEl.dataset.lat);
    const lng = parseFloat(mapEl.dataset.lng);
    const address = mapEl.dataset.address || 'Local do evento';

    if (!lat || !lng) return;

    const map = L.map('eventMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(address).openPopup();
  }

  /* ==============================
     ROUTE BUTTON (uses map data)
     ============================== */
  const routeBtn = document.getElementById('route-btn');
  if (routeBtn) {
    routeBtn.addEventListener('click', () => {
      const originInput = document.getElementById('origin-input');
      const origin = originInput?.value;
      const dest = routeBtn.dataset.destination; // e.g., injected via data-destination attr
      if (origin && dest) {
        window.open(
          `https://www.openstreetmap.org/directions?from=${encodeURIComponent(origin)}&to=${encodeURIComponent(dest)}`,
          '_blank'
        );
      } else {
        originInput.placeholder = 'Insira um endereço válido!';
        setTimeout(() => (originInput.placeholder = 'Seu endereço de partida'), 2000);
      }
    });
  }



  /* ==============================
      COPY PROMO CODE
      ============================== */
  function copyPromoCode() {
    const code = 'APOLLO'; navigator.clipboard.writeText(code).then(() => {
      const btn = event.target.closest('.copy-code-mini');
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="ri-check-line"></i>';
      setTimeout(() => {
        btn.innerHTML = originalHTML;
      }, 2000);
    });
  }
  /* ==============================
       SHARE FUNCTION
       ============================== */
  // Share Function
  document.getElementById('bottomShareBtn')?.addEventListener('click', () => {
    const shareTitle = document.title || 'Apollo :: Evento';
    if (navigator.share) {
      navigator.share({
        title: shareTitle,
        text: 'Confere esse evento no Apollo::rio!',
        url: window.location.href
      });
    } else {
      navigator.clipboard.writeText(window.location.href);
      alert('Link copiado!');
    }
  });
  // Bottom Ticket Button Smooth Scroll
  document.getElementById('bottomTicketBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('route_TICKETS').scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
  });

  function apPad(n) { return String(n).padStart(2, '0'); }

  function apFormatDateLabel(d) {
    return `${apPad(d.getDate())}/${apPad(d.getMonth() + 1)} · ${apPad(d.getHours())}h`;
  }

  function apOpenModal(id) {
    document.getElementById(id).classList.add('ap-open');
  }

  function apCloseModal(id) {
    document.getElementById(id).classList.remove('ap-open');
  }

  document.querySelectorAll('.ap-modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        apCloseModal(overlay.id);
      }
    });
  });

  const apEls = {
    sd: document.getElementById('ap-start-date'),
    st: document.getElementById('ap-start-time'),
    sl: document.getElementById('ap-duration-slider'),
    dl: document.getElementById('ap-duration-label'),
    sf: document.getElementById('ap-slider-fill'),
    bb: document.getElementById('ap-bubble'),
    ls: document.getElementById('ap-label-start'),
    le: document.getElementById('ap-label-end')
  };

  function apUpdateAll() {
    if (!apEls.sd || !apEls.st || !apEls.sl) return;
    const d = apEls.sd.value;
    const t = apEls.st.value;
    if (!d || !t) return;

    const start = new Date(`${d}T${t}:00`);
    const dur = parseInt(apEls.sl.value, 10) || 1;
    const end = new Date(start.getTime() + dur * 3600 * 1000);

    if (apEls.ls) apEls.ls.textContent = apFormatDateLabel(start);
    if (apEls.le) apEls.le.textContent = apFormatDateLabel(end);
    if (apEls.dl) apEls.dl.textContent = `DURAÇÃO: ${dur}H`;

    const min = parseInt(apEls.sl.min, 10);
    const max = parseInt(apEls.sl.max, 10);
    const pct = ((dur - min) / (max - min)) * 100;

    if (apEls.sf) apEls.sf.style.width = pct + '%';
    if (apEls.bb) {
      apEls.bb.style.left = pct + '%';
      apEls.bb.textContent = `Fim ${apPad(end.getHours())}h`;
    }
  }

  (function apInitDefaults() {
    if (!apEls.sd || !apEls.st || !apEls.sl) return;
    const now = new Date();
    now.setHours(23, 0, 0, 0);
    apEls.sd.valueAsDate = now;
    apEls.st.value = '23:00';
    apEls.sl.value = 9;
    apUpdateAll();
  })();

  apEls.sd?.addEventListener('change', apUpdateAll);
  apEls.st?.addEventListener('change', apUpdateAll);
  apEls.sl?.addEventListener('input', apUpdateAll);

  function apOpenCombobox(input) {
    input.nextElementSibling?.classList.add('ap-active');
  }

  function apCloseCombobox(input) {
    setTimeout(() => input.nextElementSibling?.classList.remove('ap-active'), 200);
  }

  function apFilterCombobox(input) {
    const filter = input.value.toLowerCase();
    const options = input.nextElementSibling?.getElementsByClassName('ap-combobox-option') || [];
    for (let i = 0; i < options.length; i++) {
      const txt = options[i].innerText;
      options[i].classList.toggle('ap-hidden', txt.toLowerCase().indexOf(filter) === -1);
    }
  }

  function apSelectSound(val, el) {
    const input = el.parentElement.previousElementSibling;
    const container = document.getElementById('ap-sounds-container');
    if (!container) return;
    if (!Array.from(container.children).some(c => c.innerText.includes(val))) {
      const chip = document.createElement('div');
      chip.className = 'ap-chip ap-active';
      chip.innerHTML = `${val} <span class="ap-chip-remove" onclick="this.parentElement.remove()">×</span>`;
      container.appendChild(chip);
    }
    if (input) {
      input.value = '';
      apFilterCombobox(input);
    }
  }

  function apSelectLocal(val, el) {
    const input = el.parentElement.previousElementSibling;
    if (!input) return;
    if (val === 'other') {
      input.value = 'Outro';
      document.getElementById('ap-manual-local').style.display = 'block';
    } else {
      input.value = val;
      document.getElementById('ap-manual-local').style.display = 'none';
    }
  }

  function apAddTimetableRow(name) {
    const container = document.getElementById('ap-timetable-list');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'ap-timetable-row';
    row.innerHTML = `
    <div class="ap-drag-handle">
      <i class="ph-bold ph-caret-up" onclick="apMoveRow(this, -1)"></i>
      <i class="ph-bold ph-caret-down" onclick="apMoveRow(this, 1)"></i>
    </div>
    <div class="ap-dj-name">${name}</div>
    <div class="ap-time-inputs">
      <input type="time"> <span style="font-size:10px;color:var(--ap-text-muted)">às</span> <input type="time">
    </div>
    <i class="ph-bold ph-x" style="cursor:pointer; font-size:12px; opacity:0.6" onclick="this.parentElement.remove()"></i>
  `;
    container.appendChild(row);
  }

  function apAddDJ(name, el) {
    const input = document.getElementById('ap-dj-input');
    const container = document.getElementById('ap-timetable-list');
    if (input) input.value = name;
    if (container) {
      const exists = Array.from(container.children).some(row => {
        const dj = row.querySelector('.ap-dj-name');
        return dj && dj.textContent === name;
      });
      if (!exists) apAddTimetableRow(name);
    }
  }

  function apMoveRow(btn, direction) {
    const row = btn.closest('.ap-timetable-row');
    if (!row) return;
    const parent = row.parentElement;
    if (direction === -1 && row.previousElementSibling) {
      parent.insertBefore(row, row.previousElementSibling);
    } else if (direction === 1 && row.nextElementSibling) {
      parent.insertBefore(row.nextElementSibling, row);
    }
  }

  function apSaveNewLocal() {
    const nameEl = document.getElementById('ap-new-local-name');
    const addrEl = document.getElementById('ap-new-local-address');
    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) return;
    const input = document.getElementById('ap-local-input');
    const dropdown = document.getElementById('ap-local-dropdown');
    if (dropdown) {
      const newOption = document.createElement('div');
      newOption.className = 'ap-combobox-option';
      newOption.innerText = name;
      newOption.onmousedown = function () { apSelectLocal(name, this); };
      if (dropdown.lastElementChild) dropdown.insertBefore(newOption, dropdown.lastElementChild);
      else dropdown.appendChild(newOption);
    }
    if (input) input.value = name;
    const manual = document.getElementById('ap-manual-local');
    if (manual) manual.style.display = 'none';
    if (nameEl) nameEl.value = '';
    if (addrEl) addrEl.value = '';
    const lat = document.getElementById('ap-local-lat');
    const lon = document.getElementById('ap-local-lon');
    if (lat) lat.value = '';
    if (lon) lon.value = '';
    apCloseModal('ap-local-modal');
  }

  function apSaveNewDJ() {
    const nameEl = document.getElementById('ap-new-dj-name');
    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) return;
    const dropdown = document.getElementById('ap-dj-dropdown');
    if (dropdown) {
      const newOption = document.createElement('div');
      newOption.className = 'ap-combobox-option';
      newOption.innerText = name;
      newOption.onmousedown = function () { apAddDJ(name, this); };
      dropdown.appendChild(newOption);
    }
    apAddTimetableRow(name);
    if (nameEl) nameEl.value = '';
    apCloseModal('ap-dj-modal');
  }

  function apRefreshGeo() {
    const icon = document.querySelector('#ap-local-modal .ph-arrows-clockwise');
    if (icon) icon.classList.add('ph-spin');
    setTimeout(() => {
      const lat = document.getElementById('ap-local-lat');
      const lon = document.getElementById('ap-local-lon');
      if (lat) lat.value = '-22.9068';
      if (lon) lon.value = '-43.1729';
      if (icon) icon.classList.remove('ph-spin');
    }, 1000);
  }

  function apFormatText(cmd) {
    const t = document.getElementById('ap-event-desc');
    if (!t) return;
    t.value += ` [${cmd}] `;
    t.focus();
  }

  function apInsertBullet() {
    const t = document.getElementById('ap-event-desc');
    if (!t) return;
    const prefix = t.value && !t.value.endsWith('\n') ? '\n' : '';
    t.value += prefix + '• ';
    t.focus();
  }

  function apPreviewFile(inputId, imgId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(imgId);
    if (!input || !preview || !input.files || !input.files[0]) return;
    const placeholder = preview.nextElementSibling;
    const reader = new FileReader();
    reader.onloadend = function () {
      preview.src = reader.result;
      preview.style.display = 'block';
      if (placeholder) placeholder.style.opacity = '0';
    };
    reader.readAsDataURL(input.files[0]);
  }

  function apTriggerGallery(idx) {
    const inputs = document.querySelectorAll('.ap-gallery-slot input[type="file"]');
    if (inputs[idx]) inputs[idx].click();
  }

  function apPreviewGallery(input, idx) {
    if (!input.files || !input.files[0]) return;
    const img = document.getElementById('ap-gal-' + idx);
    if (!img) return;
    const reader = new FileReader();
    reader.onloadend = function () {
      img.src = reader.result;
      img.style.display = 'block';
      const slot = input.parentElement;
      if (slot) {
        const icon = slot.querySelector('i');
        if (icon) icon.style.display = 'none';
      }
    };
    reader.readAsDataURL(input.files[0]);
  }

  function apSubmitForm() {
    const btn = document.querySelector('.ap-btn-primary');
    if (!btn) return;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Salvando...';
    setTimeout(() => {
      alert('Formulário Enviado!');
      btn.innerHTML = original;
      btn.disabled = false;
    }, 1000);
  }

  function apSelectCategoria(val, el) {
    const input = el.parentElement.previousElementSibling;
    if (!input) return;
    input.value = val;
    apFilterCombobox(input);
  }

  function apSelectTipo(val, el) {
    const input = el.parentElement.previousElementSibling;
    if (!input) return;
    input.value = val;
    apFilterCombobox(input);
  }



  /* ==============================
      CATEGORY FILTER (Tax: event_listing_category)
      ============================== */
  document.querySelectorAll('.event-category').forEach(btn => {
    btn.addEventListener('click', function () {
      const slug = this.dataset.slug;
      document.querySelectorAll('.event-category').forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      document.querySelectorAll('.event_listing').forEach(event => {
        const cat = event.dataset.category;
        event.style.display = slug === 'all' || cat === slug ? '' : 'none';
      });
    });
  });

  /* ============================== 
     EVENT SEARCH (Title, DJ, Local)
     ============================== */
  const searchInput = document.getElementById('eventSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.event_listing').forEach(ev => {
        const title = ev.querySelector('.event-li-title')?.textContent.toLowerCase() || '';
        const dj = ev.querySelector('.of-dj span')?.textContent.toLowerCase() || '';
        const loc = ev.querySelector('.of-location span')?.textContent.toLowerCase() || '';
        ev.style.display = title.includes(q) || dj.includes(q) || loc.includes(q) ? '' : 'none';
      });
    });
  }

  /* ==============================
     DATE FILTERING (Month)
     ============================== */
  const months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
  let currentMonth = new Date().getMonth();

  function updateDateDisplay() {
    const display = document.getElementById('dateDisplay');
    if (display) display.textContent = months[currentMonth].toUpperCase();

    const monthStr = months[currentMonth];
    document.querySelectorAll('.event_listing').forEach(ev => {
      const evMonth = ev.dataset.monthStr;
      ev.style.display = evMonth === monthStr ? '' : 'none';
    });
  }

  document.getElementById('datePrev')?.addEventListener('click', () => {
    currentMonth = (currentMonth - 1 + 12) % 12;
    updateDateDisplay();
  });
  document.getElementById('dateNext')?.addEventListener('click', () => {
    currentMonth = (currentMonth + 1) % 12;
    updateDateDisplay();
  });

  updateDateDisplay();

  /* ==============================
     INIT ALL COMPONENTS
     ============================== */
  // Initialize sliders
  document.addEventListener('DOMContentLoaded', function () {
    // Promo gallery slider
    initPromoSlider();

    // Local images slider
    initLocalSlider();

    // Map initialization
    initEventMap();
    // Initialize
    updateDateDisplay();
  });