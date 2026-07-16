<?php
require_once __DIR__ . '/backend/includes/session_init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pahingahan — Find Your Peace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="frontend/css/default.css">
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
    <a href="#top" class="active"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>Home</a>
    <a href="#featured"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><circle cx="12" cy="12" r="9"/><path d="M14.5 9.5l-1.8 5-5 1.8 1.8-5z"/></svg>Discover</a>
    <a href="#categories"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M13 10l2 2"/></svg>Hosting</a>
    <a href="#footer"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px; margin-right:6px;"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>Contact</a>
  </nav>
  <div class="user-menu">
    <a href="login.php" class="btn-login-header"><i class="fas fa-sign-in-alt"></i> Book Your Escape</a>
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

<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h3 id="modalTitle">Login Required</h3>
    <p id="modalSub">Please login or create an account to book this stay.</p>
    <div class="modal-actions">
      <button type="button" class="modal-cancel" id="modalCancel">Close</button>
      <button type="button" class="modal-confirm" onclick="window.location.href='default.php'">Login / Register</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="frontend/js/default.js"></script>
</body>
</html>