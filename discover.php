<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Discover — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="frontend/css/discover.css">
</head>
<body>

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
    <a href="homepage.php#top">
      <svg class="icon-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
      <svg class="icon-fill" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.1L1 12h3v9h6v-6h4v6h6v-9h3L12 2.1z"/></svg>
      Home</a>
    <a href="discover.php" class="active">
      <svg class="icon-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z"/></svg>
      <svg class="icon-fill" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z" fill="var(--cream)"/></svg>
      Discover</a>
    <a href="hosting.php">
      <svg class="icon-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M13 10l2 2"/></svg>
      <svg class="icon-fill" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2l8.5-8.5a1 1 0 0 1 1.4 0l2.1 2.1a1 1 0 0 1 0 1.4l-1.6 1.6-2.1-2.1-1.6 1.6 2.1 2.1-1.3 1.3z"/></svg>
      Hosting</a>
    <a href="contact.php">
      <svg class="icon-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
      <svg class="icon-fill" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18a1 1 0 0 1 1 1v.3l-10 6.7L2 6.3V6a1 1 0 0 1 1-1z"/><path d="M2 8.5V18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V8.5l-10 6.7z"/></svg>
      Contact</a>
    <a href="logout.php" class="btn-logout-mobile" style="display:none; color:var(--brown); font-weight:600;">Logout (<?php echo htmlspecialchars($currentUserName); ?>)</a>
  </nav>
  <div class="user-menu">
    <span class="user-greet">Hi, <?php echo htmlspecialchars($currentUserName); ?></span>
    <button class="btn-book" onclick="location.href='homepage.php#top'">Book Your Escape</button>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
  <button class="burger" id="burgerBtn">☰</button>
</header>

<section class="section" id="featured">
  <div class="discover-hero">
    <div class="discover-main">
      <img src="https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=1000&q=80" alt="The Sun Atrium Cabin living room" id="discoverMainImg">
    </div>
    <div class="discover-thumbs" id="discoverThumbs">
      <img src="https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=300&q=80" alt="Deck view">
      <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=300&q=80" alt="Bathroom">
      <img src="https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=300&q=80" alt="Bedroom">
    </div>
    <div class="discover-content">
      <h2>Discover more<br>in The Still.</h2>
      <p>Where architectural unique meets serene solitude. Curated. Exclusive. Unforgettable.</p>
      <p class="discover-feature"><strong>Featured Destination: The Sun Atrium Cabin.</strong></p>
      <p class="discover-feature loc">Location: Sierra Madre Mountain Range.<br>Price range from ₱8,800 / night.</p>
      <a href="#curatedStays" class="discover-cta">Explore Details</a>
    </div>
    <div class="discover-dots" id="discoverDots">
      <button class="arrow" id="discoverPrev" aria-label="Previous">‹</button>
      <button class="active" data-i="0"></button>
      <button data-i="1"></button>
      <button data-i="2"></button>
      <button data-i="3"></button>
      <button class="arrow" id="discoverNext" aria-label="Next">›</button>
    </div>
  </div>
</section>

<section class="section" id="curatedStays">
  <div class="featured-head">
    <h2>Discover More Curated Stays</h2>
    <div class="carousel-controls">
      <button class="carousel-btn prev" id="prevBtn">‹</button>
      <button class="carousel-btn next" id="nextBtn">›</button>
    </div>
  </div>
  <div class="listing-track" id="listingTrack"></div>
</section>

<footer id="footer">
  © 2026 Pahingahan. Rest deep, wander far.
</footer>

<div class="toast" id="toast"></div>

<script src="frontend/js/discover.js"></script>
</body>
</html>