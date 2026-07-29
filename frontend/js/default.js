// frontend/js/default.js
// Ginagamit sa default.php (landing page bago mag-login).
// Ang dbListings at userFavorites ay ini-inject via inline <script> sa default.php.

(function () {
    const listingTrack = document.getElementById('listingTrack');
    const emptyState = document.getElementById('emptyState');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    // ===== I-render ang mga featured listings =====
    function renderListings(list) {
        if (!listingTrack) return;

        if (!list || list.length === 0) {
            listingTrack.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        listingTrack.innerHTML = list.map(unit => {
            const price = Number(unit.price || 0).toLocaleString();
            const rating = unit.average_rating ? Number(unit.average_rating).toFixed(2) : null;
            return `
                <div class="listing-card" data-id="${unit.id}">
                    <div class="listing-img">
                        <img src="${unit.image_url || 'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=800&q=80'}" alt="${unit.name}">
                        <button type="button" class="fav-btn" data-id="${unit.id}" aria-label="Save to favorites">
                            <svg viewBox="0 0 24 24"><path d="M12.1 21.35l-1.1-1.02C6.14 15.99 3 13.1 3 9.5 3 6.57 5.42 4 8.5 4c1.74 0 3.41.81 4.5 2.09C14.09 4.81 15.76 4 17.5 4 20.58 4 23 6.57 23 9.5c0 3.6-3.14 6.49-7.99 10.83l-1.1 1.02z"/></svg>
                        </button>
                    </div>
                    <div class="listing-body">
                        <h4>${unit.name}</h4>
                        <div class="listing-meta">
                            <span class="listing-price">₱${price} <span style="font-weight:400;color:var(--ink-soft, #8a8266);">/ night</span></span>
                            ${rating ? `<span class="listing-rating"><span class="stars">★</span> ${rating}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Kapag na-click ang isang card o ang favorite heart, i-buksan ang login modal
        // (kailangan munang mag-login/mag-register bago makapag-book o maka-favorite).
        listingTrack.querySelectorAll('.listing-card, .fav-btn').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const openLoginBtn = document.getElementById('openLoginModalBtn');
                if (openLoginBtn) openLoginBtn.click();
            });
        });
    }

    renderListings(typeof dbListings !== 'undefined' ? dbListings : []);

    // ===== Carousel prev/next (i-scroll ang listing track nang pahalang) =====
    function scrollTrack(direction) {
        if (!listingTrack) return;
        const cardWidth = 270 + 24; // katumbas ng .listing-card width + gap
        listingTrack.scrollBy({ left: direction * cardWidth * 2, behavior: 'smooth' });
    }
    if (prevBtn) prevBtn.addEventListener('click', () => scrollTrack(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => scrollTrack(1));

    // ===== Search bar: kailangan munang mag-login/mag-register para makapag-search ng totoong booking =====
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const openLoginBtn = document.getElementById('openLoginModalBtn');
            if (openLoginBtn) openLoginBtn.click();
        });
    }

    // ===== Mobile nav toggle (hamburger menu) =====
    const burgerBtn = document.getElementById('burgerBtn');
    const nav = document.getElementById('nav');
    if (burgerBtn && nav) {
        burgerBtn.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }
})();