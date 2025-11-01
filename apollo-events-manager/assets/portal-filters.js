/**
 * Apollo Portal Filters - Date picker, category filters, search
 * Strict mode - no errors allowed
 */
(function() {
    'use strict';
    
    if (window.__APOLLO_PORTAL_INIT__) return;
    window.__APOLLO_PORTAL_INIT__ = true;

    // Date picker
    function initDatePicker() {
        const display = document.getElementById('dateDisplay');
        const prevBtn = document.getElementById('datePrev');
        const nextBtn = document.getElementById('dateNext');
        
        if (!display || !prevBtn || !nextBtn) return;
        
        let currentDate = new Date();
        const monthsAbbr = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        const monthsFull = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        function updateDisplay() {
            const monthIdx = currentDate.getMonth();
            const year = currentDate.getFullYear().toString().substr(2);
            display.textContent = monthsFull[monthIdx] + " '" + year;
            
            // Filter events
            filterByMonth(monthsAbbr[monthIdx]);
        }
        
        function filterByMonth(monthStr) {
            const cards = document.querySelectorAll('.event_listing');
            cards.forEach(card => {
                const cardMonth = card.dataset.monthStr;
                if (cardMonth === monthStr) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        prevBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateDisplay();
        });
        
        nextBtn.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateDisplay();
        });
        
        updateDisplay();
    }

    // Category filters
    function initCategoryFilters() {
        const filterBtns = document.querySelectorAll('.event-category');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active from all
                filterBtns.forEach(b => b.classList.remove('active'));
                // Add active to clicked
                this.classList.add('active');
                
                const slug = this.dataset.slug;
                const cards = document.querySelectorAll('.event_listing');
                
                cards.forEach(card => {
                    if (slug === 'all') {
                        card.style.display = '';
                    } else {
                        const cardCategory = card.dataset.category;
                        if (cardCategory === slug) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    }

    // Search
    function initSearch() {
        const searchInput = document.getElementById('eventSearchInput');
        if (!searchInput) return;
        
        // Typewriter effect
        const words = [
            "Qual a boa do FDS?!",
            "The best Party near me in Rio",
            "Tem algo hoje aqui no Rildy?!",
            "All day, all night.. La gente es muy loca!!"
        ];
        
        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        
        function typeWriter() {
            const currentWord = words[wordIndex];
            
            if (isDeleting) {
                searchInput.placeholder = currentWord.substring(0, charIndex);
                charIndex--;
                
                if (charIndex === 0) {
                    isDeleting = false;
                    wordIndex = (wordIndex + 1) % words.length;
                    setTimeout(typeWriter, 500);
                } else {
                    setTimeout(typeWriter, 50);
                }
            } else {
                searchInput.placeholder = currentWord.substring(0, charIndex);
                charIndex++;
                
                if (charIndex === currentWord.length) {
                    isDeleting = true;
                    setTimeout(typeWriter, 2000);
                } else {
                    setTimeout(typeWriter, 100);
                }
            }
        }
        
        typeWriter();
        
        // Search functionality
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.event_listing');
            
            cards.forEach(card => {
                const title = card.querySelector('.event-li-title')?.textContent.toLowerCase() || '';
                const dj = card.querySelector('.of-dj span')?.textContent.toLowerCase() || '';
                const location = card.querySelector('.of-location span')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || dj.includes(searchTerm) || location.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Layout toggle
    window.toggleLayout = function(element) {
        const icon = element.querySelector('i');
        if (icon.classList.contains('ri-list-check-2')) {
            icon.classList.remove('ri-list-check-2');
            icon.classList.add('ri-grid-view');
            element.title = 'Events Grid View';
        } else {
            icon.classList.remove('ri-grid-view');
            icon.classList.add('ri-list-check-2');
            element.title = 'Events List View';
        }
    };

    // Share functionality
    function initShareButtons() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('#bottomShareBtn')) {
                e.preventDefault();
                const eventTitle = document.querySelector('.event-hero-title')?.textContent || document.title;
                const eventUrl = window.location.href;
                
                if (navigator.share) {
                    navigator.share({
                        title: eventTitle,
                        url: eventUrl
                    });
                } else {
                    // Fallback: copy to clipboard
                    navigator.clipboard.writeText(eventUrl).then(function() {
                        alert('Link copiado para a área de transferência!');
                    });
                }
            }
        });
    }

    // Initialize all
    document.addEventListener('DOMContentLoaded', function() {
        initDatePicker();
        initCategoryFilters();
        initSearch();
        initShareButtons();
        updateClock();
        setInterval(updateClock, 60000);
    });

})();

