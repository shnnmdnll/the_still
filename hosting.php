<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hosting — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="frontend/css/hosting.css">
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
    <a href="discover.php">
      <svg class="icon-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z"/></svg>
      <svg class="icon-fill" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z" fill="var(--cream)"/></svg>
      Discover</a>
    <a href="hosting.php" class="active">
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

<section class="host-hero">
  <h1>Unlock your property's value.<br>Host with us.</h1>
  <p>Join our curated community and share your unique stay with the world.</p>
</section>

<section class="host-cards">
  <div class="host-card">
    <div class="icon-circle">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6M9 9h1"/></svg>
    </div>
    <h3>List and Showcase.</h3>
    <p>Create beautiful listings with stunning photos and unique features.</p>
    <a href="#" class="btn-start">Start Listing</a>
  </div>
  <div class="host-card">
    <div class="icon-circle">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V14M4 10V3M12 21v-8M12 9V3M20 21v-5M20 12V3"/><path d="M1 14h6M9 9h6M17 12h6"/></svg>
    </div>
    <h3>Manage Smart.</h3>
    <p>Streamlined booking, calendar, and pricing tools.</p>
  </div>
  <div class="host-card">
    <div class="icon-circle">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
    </div>
    <h3>Host Support.</h3>
    <p>Expert guidance, tools, and a dedicated host network.</p>
  </div>
</section>

<h2 class="steps-head">Get Started in 3 Simple Steps</h2>
<section class="steps-grid">
  <div class="step-card">
    <div class="icon-circle">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 3v2h6V3M9 11h6M9 15h4"/></svg>
    </div>
    <div>
      <h4>Create Your Listing.</h4>
      <p>Describe your space and add photos.</p>
      <a href="#" class="btn-apply">Apply Now</a>
    </div>
  </div>
  <div class="step-card">
    <div class="icon-circle">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    </div>
    <div>
      <h4>Set Your Terms.</h4>
      <p>Define price, availability, and guest rules.</p>
    </div>
  </div>
  <div class="step-card">
    <div class="icon-circle">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/></svg>
    </div>
    <div>
      <h4>Welcome Guests.</h4>
      <p>Start hosting and growing your income seamlessly.</p>
    </div>
  </div>
</section>

<footer id="footer">
  © 2026 Pahingahan. Rest deep, wander far.
</footer>

<div class="toast" id="toast"></div>

<script src="frontend/js/hosting.js"></script>
</body>
</html>