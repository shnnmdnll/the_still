<?php
require_once __DIR__ . '/backend/includes/session_init.php';
require_once __DIR__ . '/backend/controllers/listings_controller.php';
// $properties is now available (array of rows from the units table)
$propertiesJson = json_encode($properties);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pahingahan — Find Your Peace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="frontend/css/default.css">
<style>
  /* ===== Intro loader (Pahingahan hammock animation) ===== */
  :root{
    --loader-cream:#F4EEDC;
    --loader-green-dark:#2E4A2B;
    --loader-green:#436B3E;
    --loader-green-mid:#4F7942;
    --loader-green-light:#8FB379;
    --loader-green-soft:#8A9179;
  }
  #loader{
    position:fixed;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--loader-cream);
    z-index:9999;
    transition:opacity 0.8s ease, visibility 0.8s ease;
  }
  #loader.hide{ opacity:0; visibility:hidden; pointer-events:none; }

  .scene{
    position:relative;
    width:360px;
    display:flex;
    flex-direction:column;
    align-items:center;
  }

  .wind{
    position:absolute;
    top:38px;
    left:-10px;
    width:340px;
    height:60px;
    pointer-events:none;
  }
  .wind path{
    fill:none;
    stroke:var(--loader-green-soft);
    stroke-width:2.5;
    stroke-linecap:round;
    opacity:0;
    stroke-dasharray:70;
    stroke-dashoffset:70;
  }
  .wind path.w1{ animation:gust 0.75s ease-out 0s forwards; }
  .wind path.w2{ animation:gust 0.75s ease-out 0.1s forwards; }
  .wind path.w3{ animation:gust 0.75s ease-out 0.2s forwards; }
  @keyframes gust{
    0%{ opacity:0; stroke-dashoffset:70; }
    25%{ opacity:0.8; }
    75%{ opacity:0.5; stroke-dashoffset:0; }
    100%{ opacity:0; stroke-dashoffset:-70; }
  }

  .leaves{
    position:absolute;
    top:70px;
    left:50%;
    width:1px;
    height:1px;
  }
  .leaf{
    position:absolute;
    width:26px;
    height:28px;
    top:-14px;
    left:-13px;
    opacity:0;
    animation:leafFly 1.3s cubic-bezier(.3,.4,.3,1) both;
    animation-delay:var(--d);
  }
  .leaf path{ fill:var(--lc); }
  .leaf line{ stroke:var(--loader-green-dark); stroke-width:1.2; opacity:0.5; }
  @keyframes leafFly{
    0%{   transform:translate(var(--sx),var(--sy)) rotate(var(--sr)) scale(0.6); opacity:0; }
    18%{  opacity:1; }
    68%{  transform:translate(calc(var(--sx)*0.12),calc(var(--sy)*0.12)) rotate(calc(var(--sr)*0.25)) scale(0.85); opacity:1; }
    88%{  transform:translate(0,0) rotate(0deg) scale(0.45); opacity:0.9; }
    100%{ transform:translate(0,0) rotate(0deg) scale(0.15); opacity:0; }
  }

  .rig{
    width:280px;
    height:150px;
    position:relative;
  }
  .rig-inner{
    width:100%;
    height:100%;
    opacity:0;
    transform:scale(0.2) rotate(-6deg);
    transform-origin:50% 45%;
    animation:formPop 0.65s cubic-bezier(.34,1.56,.64,1) 1.45s forwards;
  }
  @keyframes formPop{
    0%{   opacity:0; transform:scale(0.2) rotate(-6deg); }
    55%{  opacity:1; transform:scale(1.12) rotate(2deg); }
    75%{  transform:scale(0.96) rotate(-1deg); }
    100%{ opacity:1; transform:scale(1) rotate(0deg); }
  }
  .hammock-svg{ width:100%; height:100%; overflow:visible; }
  .post-path{ fill:none; stroke:var(--loader-green-dark); stroke-width:7; stroke-linecap:round; }
  .swing-group{
    transform-box:fill-box;
    transform-origin:50% 0%;
    animation:microSettle 0.8s ease-out 2.1s both;
  }
  @keyframes microSettle{
    0%{ transform:rotate(0deg); }
    30%{ transform:rotate(4deg); }
    60%{ transform:rotate(-2deg); }
    100%{ transform:rotate(0deg); }
  }
  .layer-light{ fill:var(--loader-green-light); stroke:var(--loader-green-dark); stroke-width:7; stroke-linejoin:round; }
  .layer-mid{ fill:var(--loader-green-mid); stroke:var(--loader-green-dark); stroke-width:5; stroke-linejoin:round; }
  .layer-dark{ fill:var(--loader-green-dark); }

  .loader-word{
    margin-top:20px;
    display:flex;
    font-family:'Baloo 2',sans-serif;
    font-weight:700;
    font-size:44px;
    color:var(--loader-green);
    line-height:1;
  }
  .loader-word span{
    display:inline-block;
    opacity:0;
    transform:translateY(14px);
    animation:letterRest 0.5s cubic-bezier(.2,.9,.3,1.3) forwards;
  }
  @keyframes letterRest{ to{ opacity:1; transform:translateY(0); } }

  .loader-tagline{
    margin-top:2px;
    align-self:flex-end;
    font-style:italic;
    font-weight:400;
    font-size:14px;
    color:var(--loader-green-soft);
    opacity:0;
    animation:fadeUp 0.6s ease forwards;
    animation-delay:3.35s;
  }
  @keyframes fadeUp{ from{ opacity:0; transform:translateY(6px); } to{ opacity:1; transform:translateY(0); } }

  #site{
    opacity:0;
    transition:opacity 0.6s ease 0.3s;
  }
  #site.show{ opacity:1; }

  @media (prefers-reduced-motion: reduce){
    .wind path,.leaf,.rig-inner,.swing-group,.loader-word span,.loader-tagline{
      animation:none !important;
      opacity:1 !important;
      transform:none !important;
    }
  }

  /* ===== Login/Register popup modal ===== */
  .login-modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(248,242,221,0.35);
    backdrop-filter:blur(6px);
    -webkit-backdrop-filter:blur(6px);
    z-index:900;
    display:none;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .login-modal-overlay.open{ display:flex; }
  .login-modal-box{
    position:relative;
    width:100%;
    max-width:500px;
    height:min(90vh, 780px);
    background:transparent;
    border-radius:2.5rem;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(0,0,0,0.35);
  }
  .login-modal-iframe{
    width:100%;
    height:100%;
    border:none;
    display:block;
    background:#f8f2dd;
  }
  .login-modal-close{
    position:absolute;
    top:14px;
    right:14px;
    width:38px;
    height:38px;
    border-radius:50%;
    background:rgba(255,255,255,0.9);
    border:none;
    font-size:22px;
    line-height:1;
    color:#2f2a20;
    cursor:pointer;
    z-index:5;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .login-modal-close:hover{ background:#fff; }
  @media (max-width:600px){
    .login-modal-box{ height:92vh; border-radius:1.4rem; }
  }
</style>
</head>
<body>

<div id="loader">
  <div class="scene">

    <svg class="wind" viewBox="0 0 340 60">
      <path class="w1" d="M 10 15 Q 90 5 170 15 T 330 12"/>
      <path class="w2" d="M 0 32 Q 80 24 160 34 T 320 30"/>
      <path class="w3" d="M 20 48 Q 100 42 180 50 T 335 46"/>
    </svg>

    <div class="leaves">
      <svg class="leaf" style="--sx:-260px; --sy:-30px; --sr:-160deg; --d:0.15s; --lc:var(--loader-green-light)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
      <svg class="leaf" style="--sx:250px; --sy:-60px; --sr:150deg; --d:0.3s; --lc:var(--loader-green-mid)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
      <svg class="leaf" style="--sx:-220px; --sy:110px; --sr:110deg; --d:0.4s; --lc:var(--loader-green-dark)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
      <svg class="leaf" style="--sx:270px; --sy:120px; --sr:-120deg; --d:0.22s; --lc:var(--loader-green-light)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
      <svg class="leaf" style="--sx:-60px; --sy:-140px; --sr:60deg; --d:0.35s; --lc:var(--loader-green-mid)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
      <svg class="leaf" style="--sx:70px; --sy:150px; --sr:-70deg; --d:0.48s; --lc:var(--loader-green-dark)" viewBox="-16 -18 32 34">
        <path d="M0,-14 C9,-14 13,-4 13,4 C13,13 6,17 0,17 C-6,17 -13,13 -13,4 C-13,-4 -9,-14 0,-14 Z"/>
        <line x1="0" y1="-12" x2="0" y2="15"/>
      </svg>
    </div>

    <div class="rig">
      <div class="rig-inner">
        <svg class="hammock-svg" viewBox="0 0 520 260" preserveAspectRatio="xMidYMid meet">
          <path class="post-path" d="M 90 130 C 82 110 76 88 74 62"/>
          <path class="post-path" d="M 74 62 C 68 46 58 34 42 26"/>
          <path class="post-path" d="M 74 62 C 78 44 86 30 100 20"/>
          <path class="post-path" d="M 430 108 C 442 84 452 58 456 30"/>
          <path class="post-path" d="M 456 30 C 448 16 436 6 420 0"/>
          <path class="post-path" d="M 456 30 C 464 14 476 4 492 0"/>

          <g class="swing-group">
            <path class="layer-light" d="M 90 128
                     C 100 205 190 238 285 238
                     C 375 238 435 195 430 108
                     C 380 165 300 195 220 190
                     C 165 186 120 165 90 128 Z"/>
            <path class="layer-mid" d="M 90 128
                     C 120 165 165 186 220 190
                     C 260 192 300 186 335 172
                     C 260 168 175 150 120 100
                     C 106 108 96 117 90 128 Z"/>
            <path class="layer-dark" d="M 90 128
                     C 96 117 106 108 120 100
                     C 108 88 100 74 96 60
                     C 82 78 82 105 90 128 Z"/>
          </g>
        </svg>
      </div>
    </div>

    <div class="loader-word" id="loaderWord"></div>
    <div class="loader-tagline">by The Still</div>
  </div>
</div>

<div id="site">

<header>
  <div class="logo">
    <svg width="34" height="34" viewBox="0 0 512 512">
      <path fill="#5c8a3a" stroke="#3c6b41" stroke-width="14" d="M104 20v112M408 20v112M40 236c0 0 96 0 96-0 26 0 26 40 0 40-13 0-96 0-96 0-22 0-22-40 0-40zM376 236c0 0 96 0 96 0 22 0 22 40 0 40-13 0-96 0-96 0-26 0-26-40 0-40z"/>
      <path fill="#f7f0d8" stroke="#3c6b41" stroke-width="14" d="M104 132l64 104H40l64-104zM408 132l64 104H344l64-104z"/>
      <path fill="#5c8a3a" stroke="#3c6b41" stroke-width="14" d="M40 276c0 110 96 200 216 200s216-90 216-200H40z"/>
      <path fill="none" stroke="#3c6b41" stroke-width="10" stroke-linecap="round" d="M70 290c40 90 130 150 220 150M70 320c30 80 110 130 190 130M70 350c20 70 90 110 150 110"/>
    </svg>
    <span style="display:flex; flex-direction:column; line-height:1;">
      pahingahan
      <span style="align-self:flex-end; font-size:.32em; font-weight:600; letter-spacing:.02em; margin-top:2px;">by The Still</span>
    </span>
  </div>
  <nav class="nav" id="nav">
    <a href="#top" class="active"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>Home</a>
    <a href="#featured"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><circle cx="12" cy="12" r="9"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z"/></svg>Discover</a>
    <a href="#categories"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M13 10l2 2"/></svg>Hosting</a>
    <a href="#footer"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>Contact</a>
  </nav>
  <div class="user-menu">
    <button type="button" class="btn-login-header" id="openLoginModalBtn"><i class="fas fa-sign-in-alt"></i> Book Your Escape</button>
  </div>
  <button class="burger" id="burgerBtn">☰</button>
</header>

<div id="top"></div>
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <h1>Find Your Peace...</h1>
    <form class="search-bar" id="searchForm">
      <div class="search-field" id="whereField">
        <label>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
          Where
        </label>
        <input type="text" id="whereInput" placeholder="Search destinations" autocomplete="off">
        <!-- Search Suggestions Dropdown -->
        <div class="search-suggestions" id="searchSuggestions"></div>
        <div class="dropdown-panel" id="whereDropdown">
          <div class="dropdown-heading">Suggested destinations</div>
          <div id="destList"></div>
        </div>
      </div>
      <div class="search-field" id="whenField">
        <label>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
          When
        </label>
        <input type="text" id="whenInput" placeholder="Add dates" autocomplete="off" readonly>
        <div class="dropdown-panel" id="whenDropdown">
          <div class="cal-tabs">
            <button type="button" class="active" id="tabDates">Dates</button>
            <button type="button" id="tabFlexible">Flexible</button>
          </div>
          <div id="datesPane">
            <div class="cal-nav">
              <button type="button" id="calPrev">‹</button>
              <span style="font-size:.85rem;color:var(--ink-soft);">Select check-in and check-out</span>
              <button type="button" id="calNext">›</button>
            </div>
            <div class="cal-months" id="calMonths"></div>
            <div class="cal-quick" id="calQuick">
              <button type="button" class="active" data-days="0">Exact dates</button>
              <button type="button" data-days="1">± 1 day</button>
              <button type="button" data-days="2">± 2 days</button>
              <button type="button" data-days="3">± 3 days</button>
              <button type="button" data-days="7">± 7 days</button>
              <button type="button" data-days="14">± 14 days</button>
            </div>
          </div>
          <div id="flexiblePane" style="display:none;">
            <div class="cal-flex-msg">Pick how long you'd like to stay — exact dates work best for now, so try the Dates tab for full flexibility.</div>
          </div>
        </div>
      </div>
      <div class="search-field" id="guestsField">
        <label>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>
          Guests
        </label>
        <input type="text" id="guestsInput" placeholder="Add guests" autocomplete="off" readonly>
        <div class="dropdown-panel" id="guestsDropdown">
          <div class="guest-row">
            <div class="guest-row-text"><strong>Adults</strong><span>Ages 13 or above</span></div>
            <div class="stepper">
              <button type="button" data-type="adults" data-op="dec">−</button>
              <span id="countAdults">0</span>
              <button type="button" data-type="adults" data-op="inc">+</button>
            </div>
          </div>
          <div class="guest-row">
            <div class="guest-row-text"><strong>Children</strong><span>Ages 2 – 12</span></div>
            <div class="stepper">
              <button type="button" data-type="children" data-op="dec">−</button>
              <span id="countChildren">0</span>
              <button type="button" data-type="children" data-op="inc">+</button>
            </div>
          </div>
          <div class="guest-row">
            <div class="guest-row-text"><strong>Infants</strong><span>Under 2</span></div>
            <div class="stepper">
              <button type="button" data-type="infants" data-op="dec">−</button>
              <span id="countInfants">0</span>
              <button type="button" data-type="infants" data-op="inc">+</button>
            </div>
          </div>
          <div class="guest-row">
            <div class="guest-row-text"><strong>Pets</strong><span><a href="#" onclick="return false;">Bringing a service animal?</a></span></div>
            <div class="stepper">
              <button type="button" data-type="pets" data-op="dec">−</button>
              <span id="countPets">0</span>
              <button type="button" data-type="pets" data-op="inc">+</button>
            </div>
          </div>
        </div>
      </div>
      <button type="submit" class="search-submit">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
        Search
      </button>
    </form>
  </div>
</section>

<section class="section" id="categories">
  <div class="cat-grid" id="catGrid"></div>
</section>

<section class="section" id="featured">
  <div class="featured-head">
    <h2>Featured Staycations</h2>
    <div class="carousel-controls">
      <button class="carousel-btn prev" id="prevBtn">‹</button>
      <button class="carousel-btn next" id="nextBtn">›</button>
    </div>
  </div>

  <div class="filter-note" id="filterNote" style="display:none;">
    <span id="filterText"></span>
    <button id="clearFilterBtn">Clear filter ✕</button>
  </div>

  <div class="listing-track" id="listingTrack"></div>
  <div class="empty-state" id="emptyState" style="display:none;">
    No stays match that search yet. Try a different place, date, or fewer guests.
  </div>
</section>

<footer id="footer">
  © 2026 Pahingahan. Rest deep, wander far.
</footer>

<div class="toast" id="toast"></div>

<!-- Login/Register popup modal -->
<div class="login-modal-overlay" id="loginModalOverlay">
  <div class="login-modal-box">
    <button type="button" class="login-modal-close" id="loginModalCloseBtn" aria-label="Close">&times;</button>
    <iframe id="loginModalIframe" class="login-modal-iframe" title="Login and Register"></iframe>
  </div>
</div>

</div><!-- /#site -->

<script>
  // Type out "pahingahan" letter by letter for the loader
  var loaderText = "pahingahan";
  var loaderWordEl = document.getElementById('loaderWord');
  var baseDelay = 2.3;
  loaderText.split('').forEach(function(ch, i){
    var span = document.createElement('span');
    span.textContent = ch;
    span.style.animationDelay = (baseDelay + i * 0.055) + 's';
    loaderWordEl.appendChild(span);
  });

  var LOADER_TOTAL_TIME = 4600;

  window.addEventListener('load', function(){
    setTimeout(function(){
      document.getElementById('loader').classList.add('hide');
      document.getElementById('site').classList.add('show');
    }, LOADER_TOTAL_TIME);
  });

  // ===== Login/Register popup modal =====
  (function(){
    var overlay = document.getElementById('loginModalOverlay');
    var iframe = document.getElementById('loginModalIframe');
    var openBtn = document.getElementById('openLoginModalBtn');
    var closeBtn = document.getElementById('loginModalCloseBtn');

    function openLoginModal(){
      // I-lazy-load lang ang iframe sa unang pagkakataon na buksan ito,
      // para hindi na mag-request ng login.php kung hindi naman ito gagamitin.
      if (!iframe.src) {
        iframe.src = 'login.php?embed=1';
      }
      overlay.classList.add('open');
    }
    function closeLoginModal(){
      overlay.classList.remove('open');
    }

    openBtn.addEventListener('click', openLoginModal);
    closeBtn.addEventListener('click', closeLoginModal);
    overlay.addEventListener('click', function(e){
      if (e.target === overlay) closeLoginModal();
    });
    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && overlay.classList.contains('open')) closeLoginModal();
    });
  })();
</script>
<script>
    // Data injected by backend/controllers/listings_controller.php
    const dbListings = <?php echo $propertiesJson; ?>;
    const userFavorites = []; // walang naka-login na guest sa default.php, kaya blangko lang
</script>
<script src="frontend/js/default.js"></script>
</body>
</html>