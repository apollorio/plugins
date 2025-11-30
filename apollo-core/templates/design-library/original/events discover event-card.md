<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <!-- Viewport meta tag is essential for responsive design -->
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="format-detection" content="telephone=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  
  
    <title>Discover Events - Apollo::rio</title>

    <!-- External Assets -->
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
  <link href="https://assets.apollo.rio.br/uni.css" rel="stylesheet"> 
  
</head>
<body>

    <!-- ======================= FIXED HEADER ======================= -->
    <header class="site-header">
        <div class="menu-h-apollo-blur"></div>
        <a href="https://apollo.rio.br/"  class="menu-apollo-logo">
        </a>
        <nav class="main-nav">
            <a class="a-hover off"><span id="agoraH">--:--</span> RJ</a>
           <a href="#" class="ario-eve" title="Portal de Eventos">Eventos<i class="ri-arrow-right-up-line"></i></a>
            
            <!-- User Menu Dropdown -->
            <div class="menu-h-lista">
                <button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
                <div class="list">
                    <div class="item ok"><i class="ri-global-line"></i> Explorer</div>
                    <hr>
                    <div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
                    <div class="item ok"><i class="ri-logout-box-r-line"></i> Logout</div>
                </div>
            </div>
        </nav>
    </header>
    <!-- ===================== END HEADER ===================== -->

    <main class="main-container">
        <!-- SHORTCODE [discover-events-now] OUTPUT START -->
        <div class="event-manager-shortcode-wrapper discover-events-now-shortcode">

            <section class="hero-section">
                <h1 class="title-page">Experience Tomorrow's Events</h1>
                <p class="subtitle-page">Um novo <mark>&nbsp;hub digital que conecta cultura,&nbsp;</mark> tecnologia e experiências em tempo real... <mark>&nbsp;O futuro da cultura carioca começa aqui!&nbsp;</mark></p>
            </section>

            <!-- ======================= FILTERS & SEARCH (REFACTORED) ======================= -->
            <div class="filters-and-search">
                <div class="menutags event_types">
                    <button class="menutag event-category active" data-slug="all" ><span id="xxall" class="xxall" style="opacity: 1;">Todos</span></button>
                    <button class="menutag event-category" data-slug="music">House</button>
                    <button class="menutag event-category" data-slug="art-culture">PsyTrance</button>
                    <button class="menutag event-category" data-slug="mainstream">Techno</button>
                    <button class="menutag event-category" data-slug="workshops">D-Edge club</button>
                  
   
  <!-- DATA:  < Out > -->
  <div class="date-chip" id="eventDatePicker">
    <button type="button" class="date-arrow" id="datePrev" aria-label="Mês anterior">‹</button>
    <span class="date-display" id="dateDisplay" aria-live="polite">Out</span>
    <button type="button" class="date-arrow" id="dateNext" aria-label="Próximo mês">›</button>
  </div>

             
        <!-- LAYOUT:  =  (lista/box) -->
  <button
    type="button"
    class="layout-toggle"
    id="wpem-event-toggle-layout"
    title="Events List View"
    aria-pressed="true"
    onclick="toggleLayout(this)"
  >
    <i class="ri-list-view" aria-hidden="true"></i>
    <span class="visually-hidden">Alternar layout</span>
  </button>           
                  
                  
<!-- Ending of Row of Tags--></div>
                
                    <!-- New container for search + date -->

              <!-- ===================== CONTROLS BAR :: HTML (colocar no template PHP) ===================== -->
<!-- Requer Remix Icon carregado em algum lugar do tema: <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet"> -->

<div class="controls-bar" id="apollo-controls-bar">
  <!-- BUSCA -->
  <form class="box-search" role="search" id="eventSearchForm">
    <label for="eventSearchInput" class="visually-hidden">Procurar</label>
    <i class="ri-search-line" aria-hidden="true"></i>
    <input
      type="text"
      name="search_keywords"
      id="eventSearchInput"
      placeholder=""
      inputmode="search"
      autocomplete="off"
    >
    <input type="hidden" name="post_type" value="event_listing">
  </form>
</div>
      <!-- ===================== END FILTERS & SEARCH ===================== -->
<p style="margin-bottom:15px;"></p>

            <!-- ======================= EVENT LISTING GRID ======================= -->
            <div class="event_listings">
                
                <!-- 
                  PHP EVENT LOOP START - The following 'event_listing' block would be inside a PHP loop.
                  I've added `data-category` and `data-month-str` attributes to make the filters work.
                -->
                
                <!-- Card 1 -->
                <a href="#" class="event_listing" data-event-id="1" data-category="music" data-month-str="out">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day"><!--<?php
$day = date('j', $timestamp); -->25</span>
                        <span class="date-month">out</span>
           </div>
                  <div class="picture">
                    <img src="https://galeria.dismantle.com.br/foto/bonyinc/_MG_0338.jpg" alt="Techno Event" loading="lazy">              
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>House</span>
                            <span>Disco</span>
                                                      <span>Techno</span>

                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Festa Dismantle #1</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                <i class="ri-sound-module-fill"></i>
                                <span>if (is_numeric($dj_id)) {
    $dj_name = get_post_meta($dj_id, '_dj_name', true);
    if (!$dj_name) {
        $dj_post = get_post($dj_id);
        $dj_name = $dj_post ? $dj_post->post_title : '';
    }
}</span>
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                              <span id="local_nome">// Local com região
$local_region = $local_city && $local_state ? "({$local_city}, {$local_state})" : 
               ($local_city ? "({$local_city})" : ($local_state ? "({$local_state})" : ''));</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- Card 2 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="2" data-category="art-culture" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">15</span>
                        <span class="date-month">nov</span>
                    </div>
                    
                  
   <div class="picture">
                        <img src="https://images.unsplash.com/photo-1517423568366-8b83523034fd?q=80&w=1935&auto=format&fit=crop" class="picture" loading="lazy">                    
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Art</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Galeria "Luzes da Cidade"</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                <span>Ana Clara, Coletivo Urbano</span>
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Museu do Amanhã</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- Card 3 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="3" data-category="mainstream" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">12</span>
                        <span class="date-month">abr</span>
                    </div>
                    
                    <div class="picture">
                        <img src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?q=80&w=2070&auto=format&fit=crop" alt="Techno Event" loading="lazy">
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Show</span>
                            <span>Pop</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Verão Pop Festival</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                Vários artistas
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Praia de Copacabana</span>
                            </p>
                        </div>
                    </div>
                </a>
                   <!-- Card 2 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="2" data-category="art-culture" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">15</span>
                        <span class="date-month">nov</span>
                    </div>
                    
                  
   <div class="picture">
                        <img src="https://images.unsplash.com/photo-1517423568366-8b83523034fd?q=80&w=1935&auto=format&fit=crop" class="picture" loading="lazy">                    
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Art</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Galeria "Luzes da Cidade"</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                <span>Ana Clara, Coletivo Urbano</span>
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Museu do Amanhã</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- Card 3 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="3" data-category="mainstream" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">12</span>
                        <span class="date-month">abr</span>
                    </div>
                    
                    <div class="picture">
                        <img src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?q=80&w=2070&auto=format&fit=crop" alt="Techno Event" loading="lazy">
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Show</span>
                            <span>Pop</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Verão Pop Festival</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                Vários artistas
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Praia de Copacabana</span>
                            </p>
                        </div>
                    </div>
                </a>
                   <!-- Card 2 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="2" data-category="art-culture" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">15</span>
                        <span class="date-month">nov</span>
                    </div>
                    
                  
   <div class="picture">
                        <img src="https://images.unsplash.com/photo-1517423568366-8b83523034fd?q=80&w=1935&auto=format&fit=crop" class="picture" loading="lazy">                    
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Art</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Galeria "Luzes da Cidade"</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                <span>Ana Clara, Coletivo Urbano</span>
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Museu do Amanhã</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- Card 3 (NOVEMBER) -->
                <a href="#" class="event_listing" data-event-id="3" data-category="mainstream" data-month-str="nov">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">12</span>
                        <span class="date-month">abr</span>
                    </div>
                    
                    <div class="picture">
                        <img src="https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?q=80&w=2070&auto=format&fit=crop" alt="Techno Event" loading="lazy">
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Show</span>
                            <span>Pop</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">Verão Pop Festival</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                Vários artistas
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Praia de Copacabana</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- Card 4 (DECEMBER) -->
                <a href="#" class="event_listing" data-event-id="4" data-category="music" data-month-str="dez">
                    <!-- Date box is now outside .picture -->
                    <div class="box-date-event">
                        <span class="date-day">05</span>
                        <span class="date-month">dez</span>
                    </div>
                    
                    <div class="picture">
                        <img src="https://galeria.dismantle.com.br/foto/bonyinc/_MG_1118.jpg" alt="Techno Event" loading="lazy">
                        
                        <!-- Date box removed from here -->
                        
                        <div class="event-card-tags">
                            <span>Techno</span>
                            <span>Underground</span>
                        </div>
                    </div>
                    <div class="event-line">
                        <div class="box-info-event">
                            <h2 class="event-li-title afasta-bmin">D-Edge Showcase</h2>
                            <p class="event-li-detail of-dj afasta-bmin">
                                
                                <i class="ri-sound-module-fill"></i>
                                <span>Renato Ratier, BLANCAh</span>
                            </p>
                            <p class="event-li-detail of-location afasta-bmin">
                                <i class="ri-map-pin-2-line"></i>
                                <span>D-Edge club Rio</span>
                            </p>
                        </div>
                    </div>
                </a>
                
                <!-- PHP EVENT LOOP END -->

            </div>
            <!-- ===================== END EVENT LISTING GRID ===================== -->
            
            <!-- ======================= HIGHLIGHT BANNER ======================= -->
            <section class="banner-ario-1-wrapper" style="margin-top: 80px;">
                <img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop" class="ban-ario-1-img" alt="Upcoming Festival">
                <div class="ban-ario-1-content">
                    <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
                    <h2 class="ban-ario-1-titl">Retrospectiva Clubbe::rio 2026</h2>
                    <p class="ban-ario-1-txt">
                        A Retrospectiva Clubber 2026 está chegando! E em breve vamos liberar as primeiras novidades... Fique ligado, porque essa publicação promete celebrar tudo o que fez o coração da pista bater mais forte! Spoilers?
                    </p>
                    <a href="#" class="ban-ario-1-btn">
                        Saiba Mais <i class="ri-arrow-right-long-line"></i>
                    </a>
                </div>
            </section>
            <!-- ===================== END HIGHLIGHT BANNER ===================== -->

        </div>
        <!-- SHORTCODE [discover-events-now] OUTPUT END -->
    </main>

    <!-- ======================= DARK MODE TOGGLE ======================= -->
    <div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Toggle dark mode">
        <i class="ri-sun-line"></i>
        <i class="ri-moon-line"></i>
    </div>

<script src="https://assets.apollo.rio.br/base.js"></script>
</body>
</html>
