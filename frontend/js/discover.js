// frontend/js/discover.js
// Ginagamit sa discover.php -- nagre-render ng grid ng LAHAT ng available units.
// Ang dbListings at userFavorites ay ini-inject via inline <script> sa discover.php.

(function () {
    const grid = document.getElementById('discoverGrid');
    const emptyState = document.getElementById('emptyState');
    const favoriteIds = new Set(Array.isArray(userFavorites) ? userFavorites : []);

    function renderGrid(list) {
        if (!grid) return;

        if (!list || list.length === 0) {
            grid.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        grid.innerHTML = list.map(unit => {
            const price = Number(unit.price || 0).toLocaleString();
            const rating = unit.average_rating ? Number(unit.average_rating).toFixed(2) : null;
            const isFav = favoriteIds.has(unit.id) || favoriteIds.has(String(unit.id));
            return `
                <div class="listing-card" data-id="${unit.id}">
                    <div class="listing-img">
                        <img src="${unit.image_url || 'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=800&q=80'}" alt="${unit.name}">
                        <button type="button" class="fav-btn${isFav ? ' active' : ''}" data-id="${unit.id}" aria-label="Save to favorites">
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

        // I-click ang isang card -> buksan ang unit detail/booking modal
        grid.querySelectorAll('.listing-card').forEach(card => {
            card.addEventListener('click', function (e) {
                if (e.target.closest('.fav-btn')) return; // hiwalay na click ang favorite button
                const unitId = card.dataset.id;
                if (typeof window.openUnitModal === 'function') {
                    window.openUnitModal(unitId);
                }
            });
        });

        // I-click ang favorite heart -> i-toggle ang favorite status
        grid.querySelectorAll('.fav-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const unitId = btn.dataset.id;

                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ unit_id: unitId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.classList.toggle('active', data.favorited);
                        if (data.favorited) favoriteIds.add(unitId);
                        else favoriteIds.delete(unitId);
                    }
                })
                .catch(() => { /* tahimik lang mabigo -- hindi kritikal */ });
            });
        });
    }

    // NOTE: Blangko muna ang Discover page sa ngayon -- hindi pa ito
    // na-a-auto-render. Tatawagin na lang ito (renderGrid) kapag ready na
    // idagdag ang laman ng page na ito.
    // renderGrid(typeof dbListings !== 'undefined' ? dbListings : []);

    // ===== Mobile nav toggle (hamburger menu) =====
    const burgerBtn = document.getElementById('burgerBtn');
    const nav = document.getElementById('nav');
    if (burgerBtn && nav) {
        burgerBtn.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }
})();