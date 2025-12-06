<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
<title>$NAME EVENT - Apollo::rio</title>
<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">

<link href="https://assets.apollo.rio.br/uni.css" rel="stylesheet">   
  
</head>
<body>
    <div class="mobile-container">
        <!-- Hero Media -->
        <div class="hero-media">
             <div class="video-cover">
 <iframe
      src="https://www.youtube.com/watch?v=0sMFewm5vuc?autoplay=1&mute=1&loop=1&playlist=opH2ms1Fj7M&controls=0&showinfo=0&modestbranding=1"
      allow="autoplay; fullscreen"
      allowfullscreen
      frameborder="0"
    ></iframe>
  </div>
         
            <div class="hero-overlay"></div>
            <div class="hero-content">
             <section id="listing_types_tags_category"> <!-- START TAGS with ICON for speciall example:
<span class="event-tag-pill"><i class="ri-fire-fill"></i> icon to "Novidade"</span>
<span class="event-tag-pill"><i class="ri-award-fill"></i> icon to "Apollo recomenda"</span>
<span class="event-tag-pill"><i class="ri-verified-badge-fill"></i> icon to "Destaque"</span>
<i class="ri-brain-ai-3-fill"></i> for listed category 
 <i class="ri-price-tag-3-line"></i> for listed tag -->
               <span class="event-tag-pill"><i class="ri-brain-ai-3-fill"></i> $category </span>  
               <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag0 </span>  
               <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag1 </span>  
               <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag2 </span>  
               <span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> $tag3 </span>  
               <span class="event-tag-pill"><i class="ri-landscape-ai-fill"></i> $type </span>
              </section> <!-- END TAGS -->
                <h1 class="hero-title">$NAME EVENT</h1>
                <div class="hero-meta">
                    <div class="hero-meta-item">
                        <i class="ri-calendar-line"></i> 
                        <span> <!-- EXAMPLE: " 25 Out '25 " --> $Day_event $Month_event 'YY</span>
                    </div>
                    <div class="hero-meta-item">
                        <i class="ri-time-line"></i> 
                        <span id="Hora">$Event_hora_inicio — $EVent_hora_fim</span><font style="opacity:.7;font-weight:300; font-size:.81rem; vertical-hei; vertical-align: bottom;">(GMT-03h00)</font>
                    </div>
                    <div class="hero-meta-item">
                        <i class="ri-map-pin-line"></i> 
                        <span> $Event_local_Name </span> <span style="opacity:0.5">($local_regiao)</span>
                     </div>
                </div>
            </div>
        </div>

        <!-- Event Body -->
        <div class="event-body">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="#route_TICKETS" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="ri-ticket-2-line"></i>
                    </div>
                    <span class="quick-action-label">TICKETS</span>
                </a>
                <a href="#route_LINE" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="ri-draft-line"></i>
                    </div>
                    <span class="quick-action-label">Line-up</span>
                </a>
                <a href="#route_ROUTE" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="ri-treasure-map-line"></i>
                    </div>
                    <span class="quick-action-label">ROUTE</span>
                </a>
                <a href="#" class="quick-action" id="favoriteTrigger">
                    <div class="quick-action-icon">
                        <i class="ri-rocket-line"></i>
                    </div>
                    <span class="quick-action-label">Interesse</span> <!-- IMPORTANT: onclick here then RUN THE EVENT AS FAVORITE FOR USER, OR REGISTERED ON HIS BOOKMARK EVENTS!!! -->
                </a>
            </div>

          
        <!-- RSVP Row with Avatar Explosion = EXAMPLE, E X A M P L E ! ! ! >> TO REPLACE WITH USER AVATAR -->
<div class="rsvp-row">
    <div class="avatars-explosion">
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/1.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/2.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/3.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/4.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/5.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/6.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/7.jpg')"></div>
        <div class="avatar" style="background-image: url('https://media.licdn.com/dms/image/v2/D4D03AQGzWYcqE-3_-g/profile-displayphoto-scale_400_400/B4DZnPDzn2HwAo-/0/1760115506685?e=2147483647&v=beta&t=c7G7ZKFojPnnYYUu0VB7AkWzf582ydzKs6UyEvc_yXc')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/men/8.jpg')"></div>
        <div class="avatar" style="background-image: url('https://randomuser.me/api/portraits/women/8.jpg')"></div>
        <div class="avatar-count">+35</div>
        <p class="interested-text" style="margin: 0 8px 0px 20px;">
            <i class="ri-bar-chart-2-fill"></i> <span id="result"><!-- 10 avatar + number of users not displayed TOTAL here --> </span>
        </p>
    </div>
</div>
          
             <!-- foguetinho  nasa -->


          
          

            <!-- Info Section -->
            <section class="section">
                <h2 class="section-title">
                    <i class="ri-brain-ai-3-fill"></i> Info
                </h2>
                <div class="info-card">
                  <p class="info-text"><!-- EVent Description -->  $about-event-DEscription </p>
                </div>
                <div class="music-tags-marquee">
                    <div class="music-tags-track">
                       <!-- FIXED SPAN CLASS TAGS SOUNDS, TO INFINITE LOOP, IF 1 SOUND, WE REPLICATE IN THIS TILL LAST ONE -->

<!-- Infinite span mandatory 1 --> <span class="music-tag">$Selected event_sounds</span>
                       <!-- Infinite span mandatory 2 --> <span class="music-tag"> $Selected event_sounds</span>
                     <!-- Infinite span mandatory 3 -->   <span class="music-tag">$Selected event_sounds</span>
                      <!-- Infinite span mandatory 4 -->  <span class="music-tag">$Selected event_sounds</span>
                    <!-- Infinite span mandatory 5 -->    <span class="music-tag">$Selected event_sounds</span>
                     <!-- Infinite span mandatory 6 -->   <span class="music-tag">$Selected event_sounds</span>
                   <!-- Infinite span mandatory 7 -->     <span class="music-tag">$Selected event_sounds</span>
                 <!-- Infinite span mandatory 8 -->       <span class="music-tag">$Selected event_sounds</span>
                    </div>
                </div>
            </section>

<!-- Promo Gallery (max 5 Images) -->
<div class="promo-gallery-slider" >
<div class="promo-track" id="promoTrack">
                        
<!-- IMAGE 01 -->
<div class="promo-slide" style="border-radius:12px"> <img  src=" $ EVENT_ IMG 1 _ GALLERY "></div>  
<!-- IMAGE 02 -->                        
<div class="promo-slide" style="border-radius:12px"><img  src=" $ EVENT_ IMG 2_ GALLERY "></div>  
<!-- IMAGE 03 -->  
<div class="promo-slide" style="border-radius:12px"><img  src=" $ EVENT_ IMG 3_ GALLERY "></div>  
<!-- IMAGE 04 -->  
<div class="promo-slide" style="border-radius:12px"><img src=" $ EVENT_ IMG 4_ GALLERY "></div>  
<!-- IMAGE 05 -->  
<div class="promo-slide" style="border-radius:12px"><img src=" $ EVENT_ IMG 5_ GALLERY "></div>          
</div>
                 
                  
<div class="promo-controls">
     <button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
     <button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
</div>
</div>
</section>

  <!-- DJ Lineup (5 DJs: photo, initials, photo, initials, photo) -->
            <section class="section" id="route_LINE"><h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2><div class="lineup-list">
<!-- DJ X - WITH PHOTO -->
<div class="lineup-card">
<img src="$D1_img1" alt="[$DJ1_NAME]" class="lineup-avatar-img"><div class="lineup-info"><h3 class="lineup-name"><a href="./dj/[$DJ1_POST_CPT-dj]/" target="_blank" class="dj-link">$DJ1_name</a></h3><div class="lineup-time"><i class="ri-time-line"></i><span>$DJ1_timeStart - $DJ1_timeFinish</span>                            </div>
</div>
</div>
 <!-- DJ X - WITH PHOTO -->
<div class="lineup-card">
<img src="$DJ_img2" alt="[$DJ2_NAME]" class="lineup-avatar-img"><div class="lineup-info"><h3 class="lineup-name"><a href="./dj/[$DJ2_POST_CPT-dj]/" target="_blank" class="dj-link">$DJ2_name</a></h3><div class="lineup-time"><i class="ri-time-line"></i><span>$DJ2_timeStart - $DJ2_timeFinish</span>                            </div>
</div>
</div>
 <!-- DJ ... till finish list -->
          
</div>
</section>

<!-- Venue Section with 5 Images Infinite Carousel -->
            <section class="section" id="route_ROUTE">
                <h2 class="section-title">
                    <i class="ri-map-pin-2-line"></i> $ Event_local title
                </h2>
                <p class="local-endereco"> $ event_local address </p>
                
<!-- Images Slider (max 5) -->
<div class="local-images-slider" style="min-height:450px;">
<div class="local-images-track" id="localTrack" style="min-height:500px;">
<!-- SLIDER IMAGE X of max 5 -->  
<div class="local-image" style="min-height:450px;">
<img src=" $ _event_local IMAGE "></div>
<!-- SLIDER IMAGE X of max 5 -->  
<div class="local-image" style="min-height:400px;">
<img src=" $ _event_local IMAGE "></div>
<!-- SLIDER IMAGE X of max 5 -->  
<div class="local-image" style="min-height:450px;" >
<img src=" $ _event_local IMAGE "></div>
<!-- SLIDER IMAGE X of max 5 -->  
<div class="local-image" style="min-height:400px;">
<img src=" $ _event_local IMAGE "></div>
<!-- SLIDER IMAGE X of max 5 -->  
<div class="local-image" style="min-height:400px;">
<img src=" $ _event_local IMAGE "></div>
<!-- END SLIDER IMAGES -->                    </div>
<div class="slider-nav" id="localDots"></div>
</div>
              
              

         <!-- REPLACE DIV BELOW OR ADAPT IT FOR OPEN STREET MAP BY EVENT LOCAL ADDRESS LAT LONG GENERATED with no controls and no pollution on map -->
<div class="map-view" style="margin:00px auto 0px auto; z-index:0; background:green;width:100%; height:285px;border-radius:12px;background-image:url('https://img.freepik.com/premium-vector/city-map-scheme-background-flat-style-vector-illustration_833641-2300.jpg'); background-size: cover;background-repeat: no-repeat;background-position: center center; ">  </div>
           
<!-- Route Input (Apollo Style - EXACT MATCH) -->
<div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
 <div class="route-input">
 <i class="ri-map-pin-line"></i>
<input type="text" id="origin-input" placeholder="Seu endereço de partida">
</div>
<!-- CHECK IF THERES CHANGE ON JS ON https://assets.apollo.rio.br/event-page.js for route placeholders or meta to match route to events place -->
<button id="route-btn" class="route-button"><i class="ri-send-plane-line"></i></button>
</div>
</section>

<!-- Tickets Section - NO PRICES - REDIRECTING TO EXTERNAL TICKET-STORES -->
<section class="section" id="route_TICKETS">
<h2 class="section-title">
<i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos</h2>
                
<!--  Ticket Cards - NO PRICES, only external link direction sender -->
<div class="tickets-grid">
<a href="[$EXTERNAL_URL]?ref=apollo.rio.br" class="ticket-card" target="_blank"><!-- IF No URL href="" => card is class="..."&"disabled" -->
<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
<div class="ticket-info">
<h3 class="ticket-name"><span id="changingword" style="opacity: 1;">Biglietti</span></h3><span class="ticket-cta">Seguir para  Bilheteria Digital →</span></div>
</a>
<!-- Ticket Card Ends --> 

  <!-- Apollo Coupon Detail -->
<div class="apollo-coupon-detail">
<i class="ri-coupon-3-line"></i>
<span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
<button class="copy-code-mini" onclick="copyPromoCode()">
<i class="ri-file-copy-fill"></i>
</button>
</div>
  <!-- COupon Detail finish-->
  
  
<!-- Apollo Other Accesses -->
<a href=""  target="_blank"><div class="ticket-card disabled">
<!-- No URL href="" => card is "disabled" -->
<div class="ticket-icon">
<i class="ri-list-check"></i>
</div>
<div class="ticket-info">
<h3 class="ticket-name">Acessos Diversos</h3>
<span class="ticket-cta">Seguir para Acessos Diversos →</span>
</div>
  </div></a>
<!-- END ALTERNATIVE ACCESSES as List / ""RVSP"" -->
</div>
</section>

<!-- Final Event Image -->
<section class="section">
<div class="secondary-image" style="margin-botom:3rem;">
<img src="https://galeria.dismantle.com.br/foto/bonyinc/_MG_1691.jpg" alt="Event Final Photo">
</div>
</section>
</div>
  
  <!-- Protection -->
  <section class="section">
<div class="respaldo_eve">
  *A organização e execução deste evento cabem integralmente aos seus idealizadores.
  </div>
    </section>  
  
<!-- Bottom Bar -->
<div class="bottom-bar">
<a href="#route_TICKETS" class="bottom-btn primary 1" id="bottomTicketBtn">
<i class="ri-ticket-fill"></i>
<span id="changingword">Tickets</span>
</a>


<button class="bottom-btn secondary 2" id="bottomShareBtn">
<i class="ri-share-forward-line"></i>
</button>
</div>
</div>
  <script url="https://assets.apollo.rio.br/event-page.js"></script>
</body>
</html>