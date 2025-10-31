// assets/uni.js
(function(){
  if (window.__apolloUniBooted) return; window.__apolloUniBooted = true;
  const on=(el,ev,fn)=>el&&el.addEventListener(ev,fn);

document.addEventListener("DOMContentLoaded", function() {
  const words = [
    "Qual a boa do FDS?!",
    "The best Party near me in Rio",
    "Tem algo hoje aqui no Rildy?!",
    "All day, all night.. La gente es muy loca!!"
  ];

  const input = document.getElementById("eventSearchInput");

  let wordIndex = 0;
  let charIndex = 0;
  let typing = true;
  let textSoFar = "";

  // Grandpa typing style
  const baseTypeSpeed = 195;      // Slow typing (ms)
  const typeVariation = 120;      // Randomness in typing
  const eraseSpeed = 160;         // Faster erasing
  const pauseTime = 3000;         // Pause before erase
  const cursorBlinkSpeed = 500;   // Cursor blink rate

  let cursorVisible = true;
  let blinkInterval;

  function startCursorBlink() {
    if (blinkInterval) clearInterval(blinkInterval);
    blinkInterval = setInterval(() => {
      cursorVisible = !cursorVisible;
      input.placeholder = textSoFar + (cursorVisible ? "|" : " ");
    }, cursorBlinkSpeed);
  }

  function randomTypeSpeed() {
    return baseTypeSpeed + Math.random() * typeVariation;
  }

  function type() {
    const currentWord = words[wordIndex];

    if (typing) {
      if (charIndex < currentWord.length) {
        charIndex++;
        textSoFar = currentWord.substring(0, charIndex);
        input.placeholder = textSoFar + "|"; // update once per character
        setTimeout(type, randomTypeSpeed());
      } else {
        typing = false;
        setTimeout(type, pauseTime);
      }
    } else {
      if (charIndex > 0) {
        charIndex--;
        textSoFar = currentWord.substring(0, charIndex);
        input.placeholder = textSoFar + "|";
        setTimeout(type, eraseSpeed);
      } else {
        typing = true;
        wordIndex = (wordIndex + 1) % words.length;
        setTimeout(type, randomTypeSpeed());
      }
    }
  }

  // Start typing and blinking
  startCursorBlink();
  type();
});

function toggleLayout(element) {
    if (element.classList.contains('wpem-event-list-layout')) {
        // Switch to box view
        element.classList.remove('wpem-event-list-layout');
        element.classList.add('wpem-event-box-layout');
        element.title = 'Events Box View';
        element.innerHTML = '<i class="wpem-icon-stop2"></i>';
        // Add your code here to actually switch the display to boxes
    } else {
        // Switch to list view
        element.classList.remove('wpem-event-box-layout');
        element.classList.add('wpem-event-list-layout');
        element.title = 'Events List View';
        element.innerHTML = '<i class="wpem-icon-menu"></i>';
        // Add your code here to actually switch the display to list
    }
    // Toggle active class if needed
    element.classList.toggle('wpem-active-layout');
}

    document.addEventListener('DOMContentLoaded', () => {

        // --- Dark Mode Toggle ---
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        
        // Check for saved preference
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            // Save preference
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });

        // --- User Menu Dropdown ---
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        const userMenu = userMenuTrigger.parentElement;
        userMenuTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });
        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
            }
        });

        // --- Header Clock ---
       
function updateTime() {
 const now = new Date();
 const timeString = now.toLocaleTimeString(); // HH:MM:SS
      document.getElementById('agoraH').textContent = timeString;
 }

// Atualiza todo segundo
  setInterval(updateTime, 1000);
  updateTime();

        // --- Event Filtering Logic ---
        
        // --- State Variables ---
        let activeCategory = 'all';
        let searchQuery = '';
        let displayDate = new Date(); // This will be our reference month
        
        // --- Selectors ---
        const categoryButtons = document.querySelectorAll('.event-category');
        const searchInput = document.getElementById('eventSearchInput');
        const searchForm = document.getElementById('eventSearchForm');
        const allEvents = document.querySelectorAll('.event_listing');
        const dateDisplay = document.getElementById('dateDisplay');
        const datePrev = document.getElementById('datePrev');
        const dateNext = document.getElementById('dateNext');

        // --- Month Maps ---
        const monthShortNames = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        const monthMap = {
            'jan': 0, 'fev': 1, 'mar': 2, 'abr': 3, 'mai': 4, 'jun': 5,
            'jul': 6, 'ago': 7, 'set': 8, 'out': 9, 'nov': 10, 'dez': 11
        };

        // --- Main Filter Function ---
        function filterEvents() {
            const displayMonth = displayDate.getMonth(); // 0-11
            const displayYear = displayDate.getFullYear();

            allEvents.forEach(event => {
                const category = event.dataset.category;
                const monthStr = event.dataset.monthStr;
                const eventMonth = monthMap[monthStr];
                
                // TODO: Add Year check if data-year is provided
                // For this demo, we assume all events are in the current display year
                // const eventYear = parseInt(event.dataset.year, 10);
                // const showByDate = eventMonth === displayMonth && eventYear === displayYear;
                
                // This implements your request: "if month is november display only november events"
                // It's now dynamic for *any* selected month.
                const showByDate = eventMonth === displayMonth;
                
                const showByCategory = activeCategory === 'all' || category === activeCategory;
                
                const textContent = event.textContent.toLowerCase();
                const showBySearch = searchQuery === '' || textContent.includes(searchQuery);

                if (showByDate && showByCategory && showBySearch) {
                    event.classList.remove('hidden');
                    event.style.display = 'block'; // Ensure it's visible
                } else {
                    event.classList.add('hidden');
                    event.style.display = 'none'; // Hide it
                }
            });
        }
      
      
      <!--- inicio -->
function toggleLayout(element) {
    const listings = document.querySelector('.event_listings');
    if (element.classList.contains('wpem-event-list-layout')) {
        // Switch to box view
        element.classList.remove('wpem-event-list-layout');
        element.classList.add('wpem-event-box-layout');
        element.title = 'Events Box View';
        element.innerHTML = '<i class="wpem-icon-stop2"></i>';
        listings.classList.remove('list-view'); // Switch to grid (boxes)
    } else {
        // Switch to list view
        element.classList.remove('wpem-event-box-layout');
        element.classList.add('wpem-event-list-layout');
        element.title = 'Events List View';
        element.innerHTML = '<i class="wpem-icon-menu"></i>';
        listings.classList.add('list-view'); // Switch to list
    }
    // Toggle active class if needed
    element.classList.toggle('wpem-active-layout');
}
        // --- Datepicker Logic ---
        function updateDatepicker() {
            const monthName = monthShortNames[displayDate.getMonth()];
            // const year = displayDate.getFullYear();
            // dateDisplay.textContent = `${monthName} ${year}`;
            
            // FIX 1: Update date format to 'OUT 25'
            const year = displayDate.getFullYear().toString().slice(-2); // Gets '25
            dateDisplay.textContent = `${monthName} '${year}`; // Format: out '25
            
            filterEvents(); // Re-filter whenever the date changes
        }

        datePrev.addEventListener('click', () => {
            displayDate.setMonth(displayDate.getMonth() - 1);
            updateDatepicker();
        });

        dateNext.addEventListener('click', () => {
            displayDate.setMonth(displayDate.getMonth() + 1);
            updateDatepicker();
        });

        // --- Category Filter Logic ---
        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Update active button
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Update state and filter
                activeCategory = button.dataset.slug;
                filterEvents();
            });
        });

        // --- Search Filter Logic ---
        searchForm.addEventListener('submit', (e) => e.preventDefault()); // Stop form submission
        
        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.toLowerCase();
            filterEvents();
        });

        // --- Initial Load ---
        
        // FIX 2: Set initial date to the first event's month to ensure cards load
        const firstEvent = document.querySelector('.event_listing');
        if (firstEvent) {
            const firstEventMonthStr = firstEvent.dataset.monthStr; // e.g., "out"
            const firstEventMonthIndex = monthMap[firstEventMonthStr]; // e.g., 9
            
            if (firstEventMonthIndex !== undefined) {
                // Set the date object to the month of the first event
                // We assume the events are for the current or next logical year.
                
                // Get current date parts
                const now = new Date();
                const currentMonth = now.getMonth();
                const currentYear = now.getFullYear();

                // Logic to determine the year
                let eventYear = currentYear;
                // If the first event's month is *before* the current month, 
                // assume it's for *next* year.
                // e.g., Current is Nov (10), first event is Jan (0). Assume next year.
                if (firstEventMonthIndex < currentMonth) {
                    eventYear = currentYear + 1;
                }

                // Since your demo events are for Oct, Nov, Dec 2025,
                // we'll set the year to 2025 to match the data.
                // In a real app, this year would come from the event data.
                if (firstEventMonthStr === 'out' || firstEventMonthStr === 'nov' || firstEventMonthStr === 'dez') {
                    eventYear = 2025; 
                }

                displayDate.setFullYear(eventYear);
                displayDate.setMonth(firstEventMonthIndex);
            }
        }
        
        updateDatepicker(); // Set initial date display and filter events
        
    });

  // This script blocks the right-click menu
//  document.addEventListener('contextmenu', event => event.preventDefault());

  // This script blocks common copy/paste shortcuts
  // document.addEventListener('keydown', function(e) {
    // Check for Ctrl+C, Ctrl+X, Ctrl+V
    // if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x')) {
   //    e.preventDefault();
    //  alert('Copiar e colar no apollo::rio? Hoje n o Faro!');
  //  }
 // });
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
  if (!iconContainer) return;
  
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
  if (!avatarsContainer) return;
  
  const countEl = avatarsContainer.querySelector('.avatar-count');
  const resultEl = document.getElementById('result');

  function initialUpdateResult() {
    const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
    const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    resultEl.textContent = visibleCount + hiddenCount;
  }

  initialUpdateResult();
});

const favoriteTrigger = document.getElementById('favoriteTrigger');
if (favoriteTrigger) {
  favoriteTrigger.addEventListener('click', function(event) {
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
}

// Initial update - wrap in DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
  const avatarsContainer = document.querySelector('.avatars-explosion');
  if (!avatarsContainer) return;
  
  const countEl = avatarsContainer.querySelector('.avatar-count');
  const resultEl = document.getElementById('result');

  function initialUpdateResult() {
    const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
    const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
    resultEl.textContent = visibleCount + hiddenCount;
  }

  initialUpdateResult();
});

(function(){
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
  setInterval(function(){
    if (!elem) return;
    fadeOut(elem, 400, function(){
      // after fadeOut
      i = (i+1) % words.length;
      elem.textContent = words[i];
      fadeIn(elem, 400);
    });
  }, 4000);
})();



'use strict';

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
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo esc_html($event_title); ?>',
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
  const months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
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
// Initialize sliders
document.addEventListener('DOMContentLoaded', function() {
    // Promo gallery slider
    initPromoSlider();
   
    // Local images slider
    initLocalSlider();
   
    // Map initialization
    initEventMap();
    // Initialize
updateDateDisplay();
  });
})();