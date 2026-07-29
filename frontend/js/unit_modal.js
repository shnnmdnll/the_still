(function () {
    let currentUnit = null;
    let bookingData = { check_in: null, check_out: null, guest_count: 1, nights: 0, total_price: 0 };
    let currentImages = [];
    let currentImageIndex = 0;
    let sideGuestCounts = { adults: 1, children: 0, infants: 0, pets: 0 };
    let bookedDateSet = new Set();
    let calendarViewDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1);

    const overlay = document.getElementById('unitModalOverlay');
    const body = document.getElementById('unitModalBody');

    function openModal() {
        overlay.classList.add('open');
    }
    function closeModal() {
        overlay.classList.remove('open');
        currentUnit = null;
        bookingData = { check_in: null, check_out: null, guest_count: 1, nights: 0, total_price: 0 };
        sideGuestCounts = { adults: 1, children: 0, infants: 0, pets: 0 };
        pendingCheckIn = null;
        pendingCheckOut = null;
    }

    document.getElementById('unitModalCloseBtn').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

    // Isang delegated listener lang para i-close ang guests/calendar dropdown pag na-click sa labas nito
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('umGuestsDropdown');
        const summaryRow = document.getElementById('umGuestsSummaryRow');
        if (dropdown && dropdown.style.display !== 'none' && !dropdown.contains(e.target) && (!summaryRow || !summaryRow.contains(e.target))) {
            dropdown.style.display = 'none';
        }

        const calDropdown = document.getElementById('umCalendarDropdown');
        const dateField = document.getElementById('umDateSummaryField');
        if (calDropdown && calDropdown.style.display !== 'none' && !calDropdown.contains(e.target) && (!dateField || !dateField.contains(e.target))) {
            calDropdown.style.display = 'none';
        }
    });

    // ===== Amenity icon mapping (best-effort match by keyword) =====
    const amenityIconMap = {
        'wifi': '📶', 'kitchen': '🍳', 'pool': '🏊', 'parking': '🅿️',
        'air conditioning': '❄️', 'aircon': '❄️', 'tv': '📺', 'smart tv': '📺',
        'workspace': '💻', 'dedicated workspace': '💻', 'washer': '🧺', 'washing machine': '🧺',
        'heater': '🚿', 'shower': '🚿', 'bidet': '🚽', 'toiletries': '🧴', 'complimentary toiletries': '🧴',
        'self check-in': '🔑', 'gym': '🏋️', 'elevator': '🛗', 'balcony': '🌇',
    };
    function getAmenityIcon(label) {
        const key = label.toLowerCase();
        for (const k in amenityIconMap) {
            if (key.includes(k)) return amenityIconMap[k];
        }
        return '✅';
    }

    // ===== STEP 1: Unit Detail View (Airbnb-style) =====
    function renderDetailStep() {
        const u = currentUnit;
        const amenities = (u.amenities || '').split(',').map(a => a.trim()).filter(Boolean);
        document.querySelector('.unit-modal-box').classList.add('um-wide');

        currentImages = (Array.isArray(u.images) && u.images.length)
            ? u.images
            : [u.image_url || 'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=800&q=80'];
        currentImages = currentImages.slice(0, 5); // 5 lang para maayos at consistent ang grid

        // Kunin ang mga booked dates ng unit na 'to para ma-block sa calendar
        bookedDateSet = new Set();
        calendarViewDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        fetch(`api/get_booked_dates.php?unit_id=${u.id}`, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.booked_ranges)) {
                    data.booked_ranges.forEach(r => {
                        let d = new Date(r.check_in + 'T00:00:00');
                        const end = new Date(r.check_out + 'T00:00:00');
                        while (d < end) {
                            bookedDateSet.add(toYMD(d));
                            d.setDate(d.getDate() + 1);
                        }
                    });
                }
                // I-refresh ang calendar kung bukas na ito habang hinihintay ang fetch
                const dropdown = document.getElementById('umCalendarDropdown');
                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.innerHTML = buildCalendarHTML();
                    attachCalendarHandlers();
                }
            })
            .catch(() => { /* tahimik lang mabigo -- hindi mag-block ng dates kung nag-fail ang fetch */ });
        currentImageIndex = 0;

        const ratingBadge = u.average_rating
            ? `<span class="um-rating-badge">🏆 Guest favorite</span> <span class="um-rating-score">★ ${u.average_rating.toFixed(2)} · ${u.review_count} review${u.review_count === 1 ? '' : 's'}</span>`
            : `<span class="um-rating-score">New listing</span>`;

        // Photo grid: main photo + up to 4 thumbnails (laging maayos dahil naka-cap na sa 5)
        const thumbs = currentImages.slice(1, 5);

        body.innerHTML = `
            <div class="um-airbnb-wrapper">
                <h2 class="um-ab-title">${u.name}</h2>

                <div class="um-photo-grid-full">
                    <div class="um-photo-cell um-photo-main-cell" data-index="0">
                        <img src="${currentImages[0]}" alt="">
                    </div>
                    ${thumbs.length ? `
                        <div class="um-photo-thumbs-wrap">
                            ${thumbs.map((src, i) => `
                                <div class="um-photo-cell" data-index="${i + 1}">
                                    <img src="${src}" alt="">
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>

                <div class="um-airbnb-layout">
                    <div class="um-content-col">
                        <p class="um-ab-subtitle">Entire rental unit in ${u.location}</p>
                        <div class="um-meta-row">
                            <span>👥 ${u.max_guests} guests</span>
                            <span>🛏 ${u.bedrooms || 0} bedroom${u.bedrooms == 1 ? '' : 's'}</span>
                            <span>🚿 ${u.bathrooms || 0} bath${u.bathrooms == 1 ? '' : 's'}</span>
                        </div>

                        <div class="um-rating-row">${ratingBadge}</div>

                        ${u.host_name ? `<div class="um-host-row">Hosted by <strong>${u.host_name}</strong></div>` : ''}

                        <hr class="um-divider">

                        ${amenities.length ? `
                            <h3 class="um-section-head">What this place offers</h3>
                            <div class="um-amenities-grid">
                                ${amenities.map(a => `<div class="um-amenity-item"><span>${getAmenityIcon(a)}</span> ${a}</div>`).join('')}
                            </div>
                            <hr class="um-divider">
                        ` : ''}

                        ${u.description ? `
                            <h3 class="um-section-head">About this place</h3>
                            <p class="um-desc-full" id="umDescText">${u.description}</p>
                        ` : ''}
                    </div>

                    <div class="um-sidebar-col">
                        <div class="um-sidebar-box" id="umSidebarBox">
                            <div id="umSidebarHeader">
                                <h3 class="um-sidebar-title">Add dates for prices</h3>
                        </div>

                        <div class="um-date-guest-box">
                            <div class="um-date-row" id="umDateRow">
                                <div class="um-date-field um-date-summary-field" id="umDateSummaryField">
                                    <label>DATES</label>
                                    <div id="umDateSummary">${pendingCheckIn && pendingCheckOut ? formatDateRange(pendingCheckIn, pendingCheckOut) : 'Add dates'}</div>
                                </div>
                            </div>
                            <div class="um-calendar-dropdown" id="umCalendarDropdown" style="display:none;"></div>
                            <div class="um-guests-field" id="umGuestsField">
                                <label>GUESTS</label>
                                <div class="um-guests-summary-row" id="umGuestsSummaryRow">
                                    <span id="umGuestsSummary">1 guest</span>
                                    <span class="um-guests-caret">▾</span>
                                </div>
                                <div class="um-guests-dropdown" id="umGuestsDropdown" style="display:none;">
                                    ${['adults', 'children', 'infants', 'pets'].map(type => {
                                        const labels = {
                                            adults: ['Adults', 'Age 13+'],
                                            children: ['Children', 'Ages 2–12'],
                                            infants: ['Infants', 'Under 2'],
                                            pets: ['Pets', 'Not allowed for this unit'],
                                        };
                                        return `
                                        <div class="um-guest-row">
                                            <div class="um-guest-row-text"><strong>${labels[type][0]}</strong><span>${labels[type][1]}</span></div>
                                            <div class="um-stepper">
                                                <button type="button" class="um-step-btn" data-type="${type}" data-op="dec">−</button>
                                                <span id="umCount_${type}">${sideGuestCounts[type]}</span>
                                                <button type="button" class="um-step-btn" data-type="${type}" data-op="inc" ${type === 'pets' ? 'disabled' : ''}>+</button>
                                            </div>
                                        </div>`;
                                    }).join('')}
                                    <div class="um-guests-note">This place has a maximum of ${u.max_guests} guests, not including infants. Pets aren't allowed.</div>
                                </div>
                            </div>
                        </div>

                        <div id="umAvailabilityArea">
                            <button class="um-btn-primary" id="umCheckAvailBtn">Check availability</button>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        `;
        document.getElementById('umCheckAvailBtn').addEventListener('click', handleCheckAvailability);

        // Calendar dropdown toggle
        const dateSummaryField = document.getElementById('umDateSummaryField');
        const calendarDropdown = document.getElementById('umCalendarDropdown');
        dateSummaryField.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = calendarDropdown.style.display === 'block';
            if (isOpen) {
                calendarDropdown.style.display = 'none';
            } else {
                const rect = dateSummaryField.getBoundingClientRect();
                calendarDropdown.style.position = 'fixed';
                calendarDropdown.style.top = (rect.bottom + 6) + 'px';
                calendarDropdown.style.left = rect.left + 'px';
                calendarDropdown.innerHTML = buildCalendarHTML();
                calendarDropdown.style.display = 'block';
                attachCalendarHandlers();
            }
        });

        // Guests dropdown toggle (outside-click-to-close is handled by one delegated listener set up once, below)
        const guestsSummaryRow = document.getElementById('umGuestsSummaryRow');
        const guestsDropdown = document.getElementById('umGuestsDropdown');
        guestsSummaryRow.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = guestsDropdown.style.display === 'block';
            if (isOpen) {
                guestsDropdown.style.display = 'none';
            } else {
                // I-fixed-position ang dropdown gamit ang eksaktong coordinates
                // para hindi ito ma-clip ng sticky/overflow na parent container.
                const rect = guestsSummaryRow.getBoundingClientRect();
                guestsDropdown.style.position = 'fixed';
                guestsDropdown.style.top = (rect.bottom + 6) + 'px';
                guestsDropdown.style.left = rect.left + 'px';
                guestsDropdown.style.right = 'auto';
                guestsDropdown.style.width = rect.width + 'px';
                guestsDropdown.style.display = 'block';
            }
        });

        // Guest steppers
        document.querySelectorAll('.um-step-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                const op = btn.dataset.op;
                const maxTotal = u.max_guests;
                const currentTotal = sideGuestCounts.adults + sideGuestCounts.children;

                if (op === 'inc') {
                    if (type === 'adults' || type === 'children') {
                        if (currentTotal >= maxTotal) return;
                    }
                    if (type === 'adults') sideGuestCounts.adults++;
                    else if (type === 'children') sideGuestCounts.children++;
                    else if (type === 'infants') sideGuestCounts.infants++;
                } else {
                    if (type === 'adults' && sideGuestCounts.adults <= 1) return;
                    if (sideGuestCounts[type] > 0) sideGuestCounts[type]--;
                }
                document.getElementById('umCount_' + type).textContent = sideGuestCounts[type];
                updateGuestsSummary();
            });
        });

        // I-click ang kahit anong photo para buksan ang lightbox (buong larawan, walang crop)
        document.querySelectorAll('.um-photo-cell').forEach(el => {
            el.addEventListener('click', () => {
                const idx = parseInt(el.dataset.index, 10);
                openLightbox(idx);
            });
        });
    }

    // ===== Lightbox: buong larawan, walang crop, may prev/next =====
    function openLightbox(startIndex) {
        let idx = startIndex;
        const lightbox = document.createElement('div');
        lightbox.className = 'um-lightbox';
        lightbox.innerHTML = `
            <button type="button" class="um-lightbox-close" id="umLightboxClose">&times;</button>
            <button type="button" class="um-lightbox-nav um-lightbox-prev" id="umLightboxPrev">&#10094;</button>
            <img src="${currentImages[idx]}" class="um-lightbox-img" id="umLightboxImg" alt="">
            <button type="button" class="um-lightbox-nav um-lightbox-next" id="umLightboxNext">&#10095;</button>
            <div class="um-lightbox-counter" id="umLightboxCounter">${idx + 1} / ${currentImages.length}</div>
        `;
        document.body.appendChild(lightbox);

        function show(i) {
            idx = ((i % currentImages.length) + currentImages.length) % currentImages.length;
            document.getElementById('umLightboxImg').src = currentImages[idx];
            document.getElementById('umLightboxCounter').textContent = `${idx + 1} / ${currentImages.length}`;
        }
        function closeLightbox() {
            lightbox.remove();
            document.removeEventListener('keydown', onKeyDown);
        }
        function onKeyDown(e) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') show(idx - 1);
            if (e.key === 'ArrowRight') show(idx + 1);
        }

        document.getElementById('umLightboxClose').addEventListener('click', closeLightbox);
        document.getElementById('umLightboxPrev').addEventListener('click', () => show(idx - 1));
        document.getElementById('umLightboxNext').addEventListener('click', () => show(idx + 1));
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
        document.addEventListener('keydown', onKeyDown);
    }

    // ===== I-update ang buod ng "Guests" field sa sidebar =====
    function updateGuestsSummary() {
        const total = sideGuestCounts.adults + sideGuestCounts.children;
        const parts = [`${total} guest${total === 1 ? '' : 's'}`];
        if (sideGuestCounts.infants > 0) parts.push(`${sideGuestCounts.infants} infant${sideGuestCounts.infants === 1 ? '' : 's'}`);
        document.getElementById('umGuestsSummary').textContent = parts.join(', ');
    }

    let pendingCheckIn = null;
    let pendingCheckOut = null;

    // ===== Calendar helpers =====
    function toYMD(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }
    function formatDateRange(checkIn, checkOut) {
        const opts = { month: 'short', day: 'numeric' };
        const inDate = new Date(checkIn + 'T00:00:00').toLocaleDateString('en-US', opts);
        const outDate = new Date(checkOut + 'T00:00:00').toLocaleDateString('en-US', opts);
        return `${inDate} – ${outDate}`;
    }
    const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    // I-build ang HTML ng isang buwan ng calendar, may legend, blocked/booked dates, at prev/next nav
    function buildCalendarHTML() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const year = calendarViewDate.getFullYear();
        const month = calendarViewDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const startWeekday = firstDay.getDay();
        const isCurrentMonth = (year === today.getFullYear() && month === today.getMonth());

        let cells = '';
        for (let i = 0; i < startWeekday; i++) {
            cells += `<div class="um-cal-day um-cal-empty"></div>`;
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj = new Date(year, month, d);
            const ymd = toYMD(dateObj);
            const isPast = dateObj < today;
            const isBooked = bookedDateSet.has(ymd);
            const isSelectedStart = pendingCheckIn === ymd;
            const isSelectedEnd = pendingCheckOut === ymd;
            const isInRange = pendingCheckIn && pendingCheckOut && ymd > pendingCheckIn && ymd < pendingCheckOut;

            let classes = 'um-cal-day';
            if (isPast || isBooked) classes += ' um-cal-disabled';
            if (isBooked) classes += ' um-cal-booked';
            if (isSelectedStart || isSelectedEnd) classes += ' um-cal-selected';
            if (isInRange) classes += ' um-cal-in-range';

            cells += `<div class="${classes}" data-date="${ymd}">${d}</div>`;
        }

        return `
            <div class="um-cal-nav">
                <button type="button" class="um-cal-nav-btn" id="umCalPrev" ${isCurrentMonth ? 'disabled' : ''}>‹</button>
                <span class="um-cal-month-title">${MONTH_NAMES[month]} ${year}</span>
                <button type="button" class="um-cal-nav-btn" id="umCalNext">›</button>
            </div>
            <div class="um-cal-dow-row">
                ${['S','M','T','W','T','F','S'].map(d => `<div class="um-cal-dow">${d}</div>`).join('')}
            </div>
            <div class="um-cal-grid">${cells}</div>
            <div class="um-cal-legend">
                <span><i class="um-cal-legend-swatch um-cal-legend-booked"></i> Booked</span>
                <span><i class="um-cal-legend-swatch um-cal-legend-selected"></i> Selected</span>
            </div>
            ${pendingCheckIn ? `<div class="um-cal-hint">${pendingCheckOut ? 'Click a date to start a new selection.' : 'Now pick your check-out date.'}</div>` : `<div class="um-cal-hint">Select your check-in date.</div>`}
        `;
    }

    // I-attach ang click handlers sa prev/next buttons at sa bawat clickable day
    function attachCalendarHandlers() {
        const prevBtn = document.getElementById('umCalPrev');
        const nextBtn = document.getElementById('umCalNext');
        if (prevBtn) prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            calendarViewDate.setMonth(calendarViewDate.getMonth() - 1);
            refreshCalendarDropdown();
        });
        if (nextBtn) nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            calendarViewDate.setMonth(calendarViewDate.getMonth() + 1);
            refreshCalendarDropdown();
        });

        document.querySelectorAll('.um-cal-day:not(.um-cal-empty):not(.um-cal-disabled)').forEach(cell => {
            cell.addEventListener('click', (e) => {
                e.stopPropagation();
                const ymd = cell.dataset.date;

                if (!pendingCheckIn || (pendingCheckIn && pendingCheckOut)) {
                    // Simula ng bagong selection
                    pendingCheckIn = ymd;
                    pendingCheckOut = null;
                } else if (ymd <= pendingCheckIn) {
                    // Pumili ng mas maagang date bilang bagong check-in
                    pendingCheckIn = ymd;
                    pendingCheckOut = null;
                } else {
                    // I-check muna kung may booked date sa pagitan ng check-in at itong bagong check-out
                    let hasBlockedBetween = false;
                    let cursor = new Date(pendingCheckIn + 'T00:00:00');
                    const endCursor = new Date(ymd + 'T00:00:00');
                    cursor.setDate(cursor.getDate() + 1);
                    while (cursor < endCursor) {
                        if (bookedDateSet.has(toYMD(cursor))) { hasBlockedBetween = true; break; }
                        cursor.setDate(cursor.getDate() + 1);
                    }
                    if (hasBlockedBetween) {
                        pendingCheckIn = ymd;
                        pendingCheckOut = null;
                    } else {
                        pendingCheckOut = ymd;
                    }
                }

                refreshCalendarDropdown();
                const summary = document.getElementById('umDateSummary');
                if (summary) {
                    summary.textContent = (pendingCheckIn && pendingCheckOut)
                        ? formatDateRange(pendingCheckIn, pendingCheckOut)
                        : (pendingCheckIn ? `${formatDateRange(pendingCheckIn, pendingCheckIn).split(' – ')[0]} – Add check-out` : 'Add dates');
                }

                // I-auto-close ang calendar kapag kumpleto na ang selection
                if (pendingCheckIn && pendingCheckOut) {
                    setTimeout(() => {
                        const dropdown = document.getElementById('umCalendarDropdown');
                        if (dropdown) dropdown.style.display = 'none';
                    }, 350);
                }
            });
        });
    }

    function refreshCalendarDropdown() {
        const dropdown = document.getElementById('umCalendarDropdown');
        if (!dropdown) return;
        dropdown.innerHTML = buildCalendarHTML();
        attachCalendarHandlers();
    }

    // ===== Pagkatapos i-click ang "Check availability": i-check muna ang ID status =====
    function handleCheckAvailability() {
        const checkIn = pendingCheckIn;
        const checkOut = pendingCheckOut;
        const availArea = document.getElementById('umAvailabilityArea');

        if (!checkIn || !checkOut) {
            availArea.innerHTML = `<div class="um-error-box show">Please select both check-in and check-out dates.</div><button class="um-btn-primary" id="umCheckAvailBtn">Check availability</button>`;
            document.getElementById('umCheckAvailBtn').addEventListener('click', handleCheckAvailability);
            return;
        }

        availArea.innerHTML = `<div class="um-spinner">Checking requirements...</div>`;

        fetch('api/check_id_status.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('You need to log in first.');
                    closeModal();
                    return;
                }
                if (data.status === 'not_submitted' || data.status === 'rejected' || !data.status) {
                    renderUploadIdStep(data.status === 'rejected', true);
                } else {
                    runAvailabilityCheck(checkIn, checkOut);
                }
            })
            .catch(() => {
                availArea.innerHTML = `<div class="um-error-box show">Something went wrong. Please try again.</div><button class="um-btn-primary" id="umCheckAvailBtn">Check availability</button>`;
                document.getElementById('umCheckAvailBtn').addEventListener('click', handleCheckAvailability);
            });
    }

    // ===== Aktwal na tawag sa check_availability.php, tapos ipapakita ang Rates + Reserve =====
    function runAvailabilityCheck(checkIn, checkOut) {
        const availArea = document.getElementById('umAvailabilityArea');
        const guestCount = sideGuestCounts.adults + sideGuestCounts.children;

        availArea.innerHTML = `<div class="um-spinner">Checking availability...</div>`;

        fetch('api/check_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ unit_id: currentUnit.id, check_in: checkIn, check_out: checkOut, guest_count: guestCount })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bookingData = { check_in: checkIn, check_out: checkOut, guest_count: guestCount, nights: data.nights, total_price: data.total_price };
                renderRatesAndReserve();
            } else {
                availArea.innerHTML = `<div class="um-error-box show">${data.error || 'This unit is not available for the selected dates.'}</div><button class="um-btn-primary" id="umCheckAvailBtn">Check availability</button>`;
                document.getElementById('umCheckAvailBtn').addEventListener('click', handleCheckAvailability);
            }
        })
        .catch(() => {
            availArea.innerHTML = `<div class="um-error-box show">Something went wrong. Please try again.</div><button class="um-btn-primary" id="umCheckAvailBtn">Check availability</button>`;
            document.getElementById('umCheckAvailBtn').addEventListener('click', handleCheckAvailability);
        });
    }

    // ===== Ipakita ang Rates (Non-refundable / Refundable) + Reserve button =====
    function renderRatesAndReserve() {
        const availArea = document.getElementById('umAvailabilityArea');
        const nonRefundable = bookingData.total_price;
        const refundable = Math.round(bookingData.total_price * 1.12); // placeholder buffer -- walang real backend distinction pa

        // I-update ang header papuntang presyo, sa halip na "Add dates for prices"
        document.getElementById('umSidebarHeader').innerHTML = `
            <div class="um-price-summary">₱${Number(nonRefundable).toLocaleString()} <span>for ${bookingData.nights} night${bookingData.nights === 1 ? '' : 's'}</span></div>
        `;

        availArea.innerHTML = `
            <div class="um-rates-label">RATES</div>
            <div class="um-rate-option selected" data-rate="nonrefundable">
                <div class="um-rate-text">
                    <strong>Non-refundable · ₱${Number(nonRefundable).toLocaleString()} total</strong>
                    <span>Free cancellation for 24 hours. After that, the reservation is non-refundable.</span>
                </div>
                <div class="um-rate-radio selected"></div>
            </div>
            <div class="um-rate-option" data-rate="refundable">
                <div class="um-rate-text">
                    <strong>Refundable · ₱${Number(refundable).toLocaleString()} total</strong>
                    <span>Free cancellation before ${new Date(bookingData.check_in).toLocaleDateString('en-US', { month: 'long', day: 'numeric' })}. After that, the reservation is non-refundable.</span>
                </div>
                <div class="um-rate-radio"></div>
            </div>
            <button class="um-btn-primary" id="umReserveBtn">Reserve</button>
            <p class="um-reserve-note">You won't be charged yet</p>
        `;

        document.querySelectorAll('.um-rate-option').forEach(opt => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.um-rate-option').forEach(o => {
                    o.classList.remove('selected');
                    o.querySelector('.um-rate-radio').classList.remove('selected');
                });
                opt.classList.add('selected');
                opt.querySelector('.um-rate-radio').classList.add('selected');
            });
        });

        document.getElementById('umReserveBtn').addEventListener('click', handleReserveClick);
    }

    // ===== Reserve: gawin na ang booking =====
    function handleReserveClick() {
        const btn = document.getElementById('umReserveBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        fetch('api/save_booking_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                unit_id: currentUnit.id,
                check_in: bookingData.check_in,
                check_out: bookingData.check_out,
                guest_count: bookingData.guest_count,
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.error || 'Something went wrong.');
                btn.disabled = false;
                btn.textContent = 'Reserve';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Reserve';
        });
    }

    // ===== STEP: Upload ID (if none uploaded yet, or the previous one was rejected) =====
    function renderUploadIdStep(wasRejected = false, resumeAvailabilityCheck = false) {
        document.querySelector('.unit-modal-box').classList.remove('um-wide');
        body.innerHTML = `
            <div class="um-content">
                <button class="um-back-link" id="umBackToDetail">← Back</button>
                <h2 class="um-title">🪪 You need to upload a Valid ID first</h2>
                ${wasRejected ? `
                    <div class="um-error-box show" style="margin-bottom:16px;">
                        ❌ Your last uploaded ID was rejected — the name on it doesn't match your registered account. Please make sure the photo is clear and that it's your own ID before uploading again.
                    </div>
                ` : ''}
                <p class="um-desc">Before you can book, we need to verify your identity.</p>

                <div class="um-form-row">
                    <label>Type of Valid ID</label>
                    <select id="umIdType" required>
                        <option value="">-- Select ID type --</option>
                        <option value="Philippine Passport">Philippine Passport</option>
                        <option value="Driver's License">Driver's License</option>
                        <option value="UMID">UMID</option>
                        <option value="PhilSys National ID">PhilSys National ID</option>
                        <option value="Postal ID">Postal ID</option>
                        <option value="Voter's ID/Certificate">Voter's ID/Certificate</option>
                        <option value="PRC ID">PRC ID</option>
                        <option value="SSS ID">SSS ID</option>
                        <option value="GSIS ID">GSIS ID</option>
                    </select>
                </div>

                <div class="um-upload-area" id="umUploadArea">
                    <div class="um-upload-icon">📄</div>
                    <div class="um-upload-text" id="umUploadText">Click to select a valid ID (JPG/PNG, max 5MB)</div>
                    <input type="file" id="umIdInput" accept="image/jpeg,image/png">
                </div>

                <div class="um-error-box" id="umIdError"></div>
                <button class="um-btn-primary" id="umUploadSubmitBtn">Upload ID</button>
            </div>
        `;

        document.getElementById('umBackToDetail').addEventListener('click', renderDetailStep);

        const uploadArea = document.getElementById('umUploadArea');
        const idInput = document.getElementById('umIdInput');
        uploadArea.addEventListener('click', () => idInput.click());
        idInput.addEventListener('change', () => {
            if (idInput.files.length > 0) {
                document.getElementById('umUploadText').textContent = idInput.files[0].name;
                uploadArea.classList.add('has-file');
            }
        });

        document.getElementById('umUploadSubmitBtn').addEventListener('click', () => {
            const idType = document.getElementById('umIdType').value;
            const errorBox = document.getElementById('umIdError');
            errorBox.classList.remove('show');

            if (!idType) { errorBox.textContent = 'Please select an ID type first.'; errorBox.classList.add('show'); return; }
            if (!idInput.files[0]) { errorBox.textContent = 'Please select an ID photo first.'; errorBox.classList.add('show'); return; }

            const btn = document.getElementById('umUploadSubmitBtn');
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            const formData = new FormData();
            formData.append('id_photo', idInput.files[0]);
            formData.append('id_type', idType);

            fetch('api/upload_id.php', { method: 'POST', credentials: 'same-origin', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.status === 'rejected') {
                        // Malinaw na hindi tugma ang ID -- huwag payagang tumuloy sa booking
                        errorBox.textContent = data.message || 'Your ID could not be verified — it does not match your registered account. Please upload the correct ID.';
                        errorBox.classList.add('show');
                        btn.disabled = false;
                        btn.textContent = 'Upload ID';
                    } else if (data.success) {
                        if (resumeAvailabilityCheck) {
                            renderDetailStep();
                            setTimeout(() => {
                                if (pendingCheckIn && pendingCheckOut) runAvailabilityCheck(pendingCheckIn, pendingCheckOut);
                            }, 0);
                        } else {
                            renderDatesStep();
                        }
                    } else {
                        errorBox.textContent = data.error || 'Something went wrong.';
                        errorBox.classList.add('show');
                        btn.disabled = false;
                        btn.textContent = 'Upload ID';
                    }
                })
                .catch(() => {
                    errorBox.textContent = 'Something went wrong. Please try again.';
                    errorBox.classList.add('show');
                    btn.disabled = false;
                    btn.textContent = 'Upload ID';
                });
        });
    }

    // ===== STEP 2: Pumili ng Dates + Guests =====
    function renderDatesStep() {
        document.querySelector('.unit-modal-box').classList.remove('um-wide');
        const today = new Date().toISOString().slice(0, 10);
        body.innerHTML = `
            <div class="um-content">
                <button class="um-back-link" id="umBackToDetail">← Back</button>
                <h2 class="um-title">🗓 Select Dates</h2>

                <div class="um-field-row">
                    <div class="um-form-row">
                        <label>Check-in</label>
                        <input type="date" id="umCheckIn" min="${today}" required>
                    </div>
                    <div class="um-form-row">
                        <label>Check-out</label>
                        <input type="date" id="umCheckOut" required>
                    </div>
                </div>
                <div class="um-form-row">
                    <label>Guests</label>
                    <input type="number" id="umGuestCount" value="1" min="1" max="${currentUnit.max_guests}" required>
                </div>

                <div class="um-error-box" id="umDatesError"></div>
                <button class="um-btn-primary" id="umCheckAvailBtn">Check Availability</button>
            </div>
        `;
        document.getElementById('umBackToDetail').addEventListener('click', renderDetailStep);

        document.getElementById('umCheckAvailBtn').addEventListener('click', () => {
            const checkIn = document.getElementById('umCheckIn').value;
            const checkOut = document.getElementById('umCheckOut').value;
            const guestCount = parseInt(document.getElementById('umGuestCount').value || 1);
            const errorBox = document.getElementById('umDatesError');
            errorBox.classList.remove('show');

            if (!checkIn || !checkOut) {
                errorBox.textContent = 'Please select check-in and check-out dates.';
                errorBox.classList.add('show');
                return;
            }

            const btn = document.getElementById('umCheckAvailBtn');
            btn.disabled = true;
            btn.textContent = 'Checking...';

            fetch('api/check_availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ unit_id: currentUnit.id, check_in: checkIn, check_out: checkOut, guest_count: guestCount })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    bookingData = { check_in: checkIn, check_out: checkOut, guest_count: guestCount, nights: data.nights, total_price: data.total_price };
                    renderConfirmStep();
                } else {
                    errorBox.textContent = data.error || 'Something went wrong.';
                    errorBox.classList.add('show');
                    btn.disabled = false;
                    btn.textContent = 'Check Availability';
                }
            })
            .catch(() => {
                errorBox.textContent = 'Something went wrong. Please try again.';
                errorBox.classList.add('show');
                btn.disabled = false;
                btn.textContent = 'Check Availability';
            });
        });
    }

    // ===== STEP 3: Confirm Booking =====
    function renderConfirmStep() {
        document.querySelector('.unit-modal-box').classList.remove('um-wide');
        body.innerHTML = `
            <div class="um-content">
                <button class="um-back-link" id="umBackToDates">← Back</button>
                <h2 class="um-title">✅ Confirm Booking</h2>

                <div class="um-summary-row"><span>Check-in</span><strong>${bookingData.check_in}</strong></div>
                <div class="um-summary-row"><span>Check-out</span><strong>${bookingData.check_out}</strong></div>
                <div class="um-summary-row"><span>Nights</span><strong>${bookingData.nights}</strong></div>
                <div class="um-summary-row"><span>Guests</span><strong>${bookingData.guest_count}</strong></div>
                <div class="um-total-row"><span>Total</span><span>₱${bookingData.total_price.toLocaleString()}</span></div>

                <div class="um-error-box" id="umConfirmError"></div>
                <button class="um-btn-primary" id="umConfirmBtn" style="margin-top:20px;">Confirm Booking (20% Downpayment)</button>
            </div>
        `;
        document.getElementById('umBackToDates').addEventListener('click', renderDatesStep);

        document.getElementById('umConfirmBtn').addEventListener('click', () => {
            const btn = document.getElementById('umConfirmBtn');
            const errorBox = document.getElementById('umConfirmError');
            errorBox.classList.remove('show');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('api/save_booking_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    unit_id: currentUnit.id,
                    check_in: bookingData.check_in,
                    check_out: bookingData.check_out,
                    guest_count: bookingData.guest_count,
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url; // papunta sa payment — kailangang umalis sa page dito
                } else {
                    errorBox.textContent = data.error || 'Something went wrong.';
                    errorBox.classList.add('show');
                    btn.disabled = false;
                    btn.textContent = 'Confirm Booking (20% Downpayment)';
                }
            })
            .catch(() => {
                errorBox.textContent = 'Something went wrong. Please try again.';
                errorBox.classList.add('show');
                btn.disabled = false;
                btn.textContent = 'Confirm Booking (20% Downpayment)';
            });
        });
    }

    // ===== Entry point: buksan ang modal para sa isang unit =====
    window.openUnitModal = function (unitId) {
        body.innerHTML = `<div class="um-spinner">Loading unit details...</div>`;
        openModal();

        fetch('api/get_property.php?id=' + unitId, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentUnit = data.property;
                    renderDetailStep();
                } else {
                    body.innerHTML = `<div class="um-content"><p>This unit could not be found.</p></div>`;
                }
            })
            .catch(() => {
                body.innerHTML = `<div class="um-content"><p>There was a problem loading this. Please try again.</p></div>`;
            });
    };
})();