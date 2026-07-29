<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/controllers/listings_controller.php';
require_once __DIR__ . '/backend/includes/db.php';
// $properties is now available (array of rows from the units table)
$propertiesJson = json_encode($properties);

// I-kuha ang mga na-favorite na unit IDs ng naka-login na guest
$favUserId = $_SESSION['user_id'] ?? 0;
$favStmt = $pdo->prepare("SELECT unit_id FROM favorites WHERE user_id = :user_id");
$favStmt->execute([':user_id' => $favUserId]);
$userFavoriteIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
$userFavoritesJson = json_encode($userFavoriteIds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pahingahan — Discover</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="frontend/css/homepage.css?v=<?php echo filemtime(__DIR__ . '/frontend/css/homepage.css'); ?>">
<link rel="stylesheet" href="frontend/css/unit_modal.css?v=<?php echo filemtime(__DIR__ . '/frontend/css/unit_modal.css'); ?>">
<style>
  /* ===== Discover-specific: full grid ng lahat ng units (hindi horizontal scroll track) ===== */
  .discover-head { padding: 36px 48px 8px; }
  .discover-head h1 { font-family:'Poppins',sans-serif; font-size:1.8rem; color:#2c2a1f; margin-bottom:6px; }
  .discover-head p { color:#5c5a4d; font-size:.95rem; }
  .discover-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
    padding: 36px 48px 56px;
  }
  .discover-soon {
    text-align: center;
    padding: 100px 20px;
    max-width: 560px;
    margin: 0 auto;
  }
  .discover-soon .icon { font-size: 3rem; margin-bottom: 18px; }
  .discover-soon h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    color: #2c2a1f;
    margin-bottom: 10px;
  }
  .discover-soon p {
    color: #5c5a4d;
    font-size: .95rem;
    line-height: 1.6;
  }
  .discover-grid .listing-card { width: 100%; }
  @media (max-width: 640px) {
    .discover-head { padding: 24px 20px 6px; }
    .discover-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; padding: 16px 20px 40px; }
  }
</style>
</head>
<body>
  <div class="unit-modal-overlay" id="unitModalOverlay">
    <div class="unit-modal-box">
        <button type="button" class="unit-modal-close" id="unitModalCloseBtn">&times;</button>
        <div class="unit-modal-body" id="unitModalBody"></div>
    </div>
</div>

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
    <div class="notif-menu" style="position:relative; flex-shrink:0;">
      <button type="button" class="notif-btn" id="notifBtn" title="Notifications" aria-label="Notifications" style="position:relative; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; background:#fff; border:1.5px solid #5c8a3a; color:#3c6b41; cursor:pointer;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6z"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0"/></svg>
        <span id="notifBadge" style="display:none; position:absolute; top:-3px; right:-3px; min-width:16px; height:16px; padding:0 3px; border-radius:8px; background:#c0392b; color:#fff; font-size:.62rem; line-height:16px; text-align:center; font-weight:700;">0</span>
      </button>
      <div class="notif-dropdown" id="notifDropdown" style="display:none; position:absolute; right:0; top:50px; background:#fff; border:1px solid #e2ddc9; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.12); width:320px; max-height:380px; overflow-y:auto; z-index:60;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #f0ece0;">
          <strong style="font-size:.9rem; color:#2f2a20;">Booking Notifications</strong>
          <a href="#" id="notifMarkAllRead" style="font-size:.78rem; color:#5c8a3a; text-decoration:none; font-weight:600;">Mark all read</a>
        </div>
        <div id="notifList" style="padding:24px 16px; text-align:center; color:#8a8368; font-size:.85rem;">Loading…</div>
        <div id="notifEmpty" style="display:none; padding:24px 16px; text-align:center; color:#8a8368; font-size:.85rem;">No booking notifications yet.</div>
      </div>
    </div>
    <span class="user-greet">Hi, <?php echo htmlspecialchars($currentUserName); ?></span>
    <button class="btn-book" onclick="location.href='homepage.php#top'">Book Your Escape</button>
    <div class="profile-menu" style="position:relative; flex-shrink:0;">
      <button type="button" class="profile-menu-btn" title="Account" aria-label="Account menu" style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; background:#fff; border:1.5px solid #5c8a3a; color:#3c6b41; cursor:pointer;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
      </button>
      <div class="profile-dropdown" style="display:none; position:absolute; right:0; top:50px; background:#fff; border:1px solid #e2ddc9; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.12); min-width:180px; overflow:hidden; z-index:50;">
        <a href="profile.php" style="display:block; padding:12px 16px; color:#2f2a20; text-decoration:none; font-size:.9rem; font-weight:500;">Account</a>
        <a href="my_bookings.php" style="display:block; padding:12px 16px; color:#2f2a20; text-decoration:none; font-size:.9rem; font-weight:500; border-top:1px solid #f0ece0;">My Bookings</a>
        <a href="favorites.php" style="display:block; padding:12px 16px; color:#2f2a20; text-decoration:none; font-size:.9rem; font-weight:500; border-top:1px solid #f0ece0;">Favorites</a>
        <a href="stay_history.php" style="display:block; padding:12px 16px; color:#2f2a20; text-decoration:none; font-size:.9rem; font-weight:500; border-top:1px solid #f0ece0;">Stay History</a>
        <a href="logout.php" style="display:block; padding:12px 16px; color:#c0392b; text-decoration:none; font-size:.9rem; font-weight:500; border-top:1px solid #f0ece0;">Logout</a>
      </div>
    </div>
  </div>
  <button class="burger" id="burgerBtn">☰</button>
</header>

<div class="discover-soon">
  <h2>Discover is coming soon</h2>
  <p>We're building something special here — a curated way to explore every kind of stay Pahingahan has to offer, from cozy hideaways to full staycation retreats.</p>
  <p style="margin-top:14px;">Soon, you'll be able to browse by location, price, and the amenities that matter most to you, all in one place. In the meantime, feel free to check out our <a href="homepage.php#featured" style="color:#5c8a3a; font-weight:600; text-decoration:none;">Featured Staycations</a> on the homepage.</p>
  <p style="margin-top:14px;">Thank you for your patience — we can't wait to show you what's coming.</p>
</div>
<div class="discover-grid" id="discoverGrid"></div>
<div class="empty-state" id="emptyState" style="display:none; text-align:center; padding:60px 20px; color:#5c5a4d;">
  No stays available right now. Please check back later.
</div>

<footer id="footer">
  © 2026 Pahingahan. Rest deep, wander far.
</footer>

<div class="toast" id="toast"></div>

<script>
    // Data injected by backend/controllers/listings_controller.php
    const dbListings = <?php echo $propertiesJson; ?>;
    const userFavorites = <?php echo $userFavoritesJson; ?>;
</script>
<script src="frontend/js/discover.js?v=<?php echo filemtime(__DIR__ . '/frontend/js/discover.js'); ?>"></script>
<script src="frontend/js/unit_modal.js?v=<?php echo filemtime(__DIR__ . '/frontend/js/unit_modal.js'); ?>"></script>
<script>
(function(){
  document.querySelectorAll(".profile-menu-btn").forEach(function(btn){
    var dropdown = btn.nextElementSibling;
    btn.addEventListener("click", function(e){
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });
  });
  document.addEventListener("click", function(){
    document.querySelectorAll(".profile-dropdown").forEach(function(d){ d.style.display = "none"; });
  });
})();

// ---- Notification bell: real booking notifications from api/get_notifications.php ----
(function(){
  var notifBtn = document.getElementById("notifBtn");
  var notifDropdown = document.getElementById("notifDropdown");
  var notifBadge = document.getElementById("notifBadge");
  var notifList = document.getElementById("notifList");
  var notifEmpty = document.getElementById("notifEmpty");
  var markAllRead = document.getElementById("notifMarkAllRead");
  var SEEN_KEY = "pahingahan_seen_notif_ids";

  var notifications = [];

  function getSeenIds(){
    try {
      return JSON.parse(localStorage.getItem(SEEN_KEY) || "[]");
    } catch(e){ return []; }
  }

  function markAllSeen(){
    var ids = notifications.map(function(n){ return n.id; });
    localStorage.setItem(SEEN_KEY, JSON.stringify(ids));
    notifications.forEach(function(n){ n.read = true; });
    render();
  }

  function fetchNotifications(onDone){
    fetch("api/get_notifications.php", { credentials: "same-origin" })
      .then(function(res){ return res.json(); })
      .then(function(data){
        if(!data.success){
          notifList.innerHTML = "";
          notifEmpty.style.display = "block";
          notifEmpty.textContent = "Unable to load notifications right now.";
          notifBadge.style.display = "none";
          return;
        }
        var seen = getSeenIds();
        notifications = (data.notifications || []).map(function(n){
          n.read = seen.indexOf(n.id) !== -1;
          return n;
        });
        render();
        if (typeof onDone === "function") onDone();
      })
      .catch(function(){
        notifList.innerHTML = "";
        notifEmpty.style.display = "block";
        notifEmpty.textContent = "Unable to load notifications right now.";
        notifBadge.style.display = "none";
      });
  }

  function statusDotColor(status){
    if(status === "confirmed") return "#5c8a3a";
    if(status === "declined") return "#c0392b";
    return "#c98a1f"; // pending / default
  }

  function render(){
    notifList.innerHTML = "";
    if(notifications.length === 0){
      notifEmpty.style.display = "block";
      notifEmpty.textContent = "No booking notifications yet.";
    } else {
      notifEmpty.style.display = "none";
      notifications.forEach(function(n){
        var item = document.createElement("a");
        item.href = (String(n.id).charAt(0) === "h") ? "booking_management.php" : "my_bookings.php";
        item.style.cssText = "display:block; padding:12px 16px; text-decoration:none; border-bottom:1px solid #f0ece0; background:" + (n.read ? "#fff" : "#f7f9f2") + ";";
        item.innerHTML =
          '<div style="display:flex; align-items:flex-start; gap:8px;">' +
            '<span style="margin-top:5px; width:7px; height:7px; border-radius:50%; background:' + statusDotColor(n.status) + '; flex-shrink:0; opacity:' + (n.read ? '0' : '1') + ';"></span>' +
            '<div>' +
              '<div style="font-size:.85rem; font-weight:600; color:#2f2a20;">' + n.title + '</div>' +
              '<div style="font-size:.8rem; color:#5c5646; margin-top:2px;">' + n.message + '</div>' +
            '</div>' +
          '</div>';
        notifList.appendChild(item);
      });
    }
    updateBadge();
  }

  function updateBadge(){
    var unread = notifications.filter(function(n){ return !n.read; }).length;
    if(unread > 0){
      notifBadge.style.display = "block";
      notifBadge.textContent = unread > 9 ? "9+" : String(unread);
    } else {
      notifBadge.style.display = "none";
    }
  }

  notifBtn.addEventListener("click", function(e){
    e.stopPropagation();
    var isOpen = notifDropdown.style.display === "block";
    document.querySelectorAll(".profile-dropdown").forEach(function(d){ d.style.display = "none"; });
    notifDropdown.style.display = isOpen ? "none" : "block";
    if(!isOpen){
      fetchNotifications(function(){
        markAllSeen();
      });
    }
  });

  markAllRead.addEventListener("click", function(e){
    e.preventDefault();
    e.stopPropagation();
    markAllSeen();
  });

  document.addEventListener("click", function(){
    notifDropdown.style.display = "none";
  });
  notifDropdown.addEventListener("click", function(e){ e.stopPropagation(); });

  fetchNotifications();
})();
</script>
</body>
</html>