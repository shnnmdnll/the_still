/* ---------------- Data ---------------- */
const categories = [
  {
    key:'forest', title:'Lush Forest Hideaways',
    desc:'Secluded domes tucked deep within the vibrant rainforest canopy.',
    img:'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=500&q=80'
  },
  {
    key:'riverside', title:'Riverside Nooks',
    desc:'Peaceful retreats by the water. Listening to the gentle flow of a mountain stream.',
    img:'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=500&q=80'
  },
  {
    key:'mountain', title:'Cozy Mountain Cabins',
    desc:'Traditional and modern cabins offering comfort and warmth.',
    img:'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=500&q=80'
  },
  {
    key:'unique', title:'Unique Stays',
    desc:'One-of-a-kind designs. Geometric and geodesic domes for unforgettable stays.',
    img:'https://images.unsplash.com/photo-1587061949409-02df41d5e562?auto=format&fit=crop&w=500&q=80'
  }
];

const listings = [
  {id:1, name:'Misty Mountain Bamboo Villa', location:'Rizal', category:'mountain', price:100, rating:4.8, capacity:4,
    img:'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=500&q=80'},
  {id:2, name:'Calm Lakefront A-Frame', location:'Laguna', category:'riverside', price:130, rating:4.9, capacity:6,
    img:'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=500&q=80'},
  {id:3, name:'Cozy Mountain Cabin', location:'Laguna', category:'mountain', price:110, rating:4.7, capacity:4,
    img:'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=500&q=80'},
  {id:4, name:'Unique Mountain Cottage', location:'Philippines', category:'unique', price:120, rating:4.7, capacity:3,
    img:'https://images.unsplash.com/photo-1518602164578-cd0074062767?auto=format&fit=crop&w=500&q=80'},
  {id:5, name:'Lush Forest Dome Retreat', location:'Palawan', category:'forest', price:140, rating:4.9, capacity:2,
    img:'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=500&q=80'},
  {id:6, name:'Riverside Bamboo Nook', location:'Bukidnon', category:'riverside', price:95, rating:4.6, capacity:2,
    img:'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=500&q=80'},
  {id:7, name:'Geometric Forest Dome', location:'Tagaytay', category:'unique', price:160, rating:4.8, capacity:2,
    img:'https://images.unsplash.com/photo-1587061949409-02df41d5e562?auto=format&fit=crop&w=500&q=80'},
  {id:8, name:'Traditional Nipa Cabin', location:'Baguio', category:'mountain', price:105, rating:4.5, capacity:5,
    img:'https://images.unsplash.com/photo-1587061949409-02df41d5e562?auto=format&fit=crop&w=500&q=80'}
];

const favorites = new Set();
let activeCategory = null;
let activeQuery = { where:'', guests:0, checkIn:null, checkOut:null };

/* ================= SEARCH SUGGESTIONS - FROM LISTINGS ================= */
// Generate search suggestions dynamically from the listings data
// This way, suggestions are always based on the actual properties
/* ================= SEARCH SUGGESTIONS - FROM LISTINGS ================= */
// Generate search suggestions dynamically from the listings data
// This searches through ALL properties in the listings array
function getSearchSuggestions(query) {
  if (!query || query.length < 1) return [];
  const q = query.toLowerCase();
  
  // Search through ALL listings by name, location, and category
  const results = listings.filter(listing => 
    listing.name.toLowerCase().includes(q) || 
    listing.location.toLowerCase().includes(q) ||
    listing.category.toLowerCase().includes(q)
  );
  
  // Return unique suggestions based on property names
  // Limit to 6 suggestions max
  return results.slice(0, 6).map(listing => ({
    name: listing.name,
    sub: listing.location + ' · ' + listing.category,
    icon: '🏠',
    id: listing.id
  }));
}

function highlightMatch(text, query) {
  const q = query.toLowerCase();
  const idx = text.toLowerCase().indexOf(q);
  if (idx === -1) return text;
  return text.substring(0, idx) + 
         '<span class="suggestion-highlight">' + 
         text.substring(idx, idx + query.length) + 
         '</span>' + 
         text.substring(idx + query.length);
}

function renderSuggestions(query) {
  const container = document.getElementById('searchSuggestions');
  const suggestions = getSearchSuggestions(query);
  
  if (suggestions.length === 0) {
    container.innerHTML = `<div class="no-results">No properties found. Try a different search.</div>`;
    container.classList.add('active');
    return;
  }
  
  let html = '';
  suggestions.forEach(d => {
    const highlightedName = highlightMatch(d.name, query);
    const highlightedSub = highlightMatch(d.sub, query);
    html += `
      <div class="suggestion-item" data-value="${d.name}" data-id="${d.id}">
        <div class="suggestion-icon" style="background:#e6f2e2; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
          <span style="font-size:1.1rem;">${d.icon}</span>
        </div>
        <div class="suggestion-text">
          <strong>${highlightedName}</strong>
          <span>${highlightedSub}</span>
        </div>
      </div>
    `;
  });
  
  container.innerHTML = html;
  container.classList.add('active');
  
  // Add click handlers to suggestions
  container.querySelectorAll('.suggestion-item').forEach(item => {
    item.addEventListener('click', function() {
      const value = this.dataset.value;
      const id = this.dataset.id;
      document.getElementById('whereInput').value = value;
      container.classList.remove('active');
      // Filter listings by the selected property name
      activeQuery.where = value;
      renderListings();
      document.getElementById('featured').scrollIntoView({behavior:'smooth'});
    });
  });
}

// Search input handler
const whereInput = document.getElementById('whereInput');
const suggestionsContainer = document.getElementById('searchSuggestions');

whereInput.addEventListener('input', function() {
  const query = this.value.trim();
  if (query.length > 0) {
    renderSuggestions(query);
  } else {
    suggestionsContainer.classList.remove('active');
  }
});

whereInput.addEventListener('focus', function() {
  const query = this.value.trim();
  if (query.length > 0) {
    renderSuggestions(query);
  }
});

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('#whereField')) {
    suggestionsContainer.classList.remove('active');
  }
});

// Close suggestions on escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    suggestionsContainer.classList.remove('active');
  }
});

/* ================= WHERE dropdown ================= */
const destinations = [
  { type:'nearby',   name:'Nearby',                       sub:'Find what\'s around you' },
  { type:'mountain', name:'Baguio, Philippines',           sub:'Great for a weekend getaway' },
  { type:'house',    name:'Tagaytay, Philippines',         sub:'For nature-lovers' },
  { type:'beach',    name:'San Juan Beach, Philippines',   sub:'For a trip abroad' },
  { type:'beach',    name:'Cebu City, Philippines',        sub:'For sights like Magellan\'s Cross' },
  { type:'city',     name:'Laguna, Philippines',           sub:'Near you' },
  { type:'city',     name:'Taguig City, Philippines',      sub:'Popular with travelers' }
];
const destIcons = {
  nearby:   { bg:'#e3edfa', color:'#3b7cff', path:'M4 20l7-16 7 16-7-4-7 4z' },
  mountain: { bg:'#e7ecf5', color:'#2c3e6b', path:'M3 19l6-9 4 5 3-4 5 8H3z' },
  house:    { bg:'#f7ecd8', color:'#a9762f', path:'M4 11l8-6 8 6v8a1 1 0 01-1 1h-4v-6H9v6H5a1 1 0 01-1-1v-8z' },
  beach:    { bg:'#fbe6e6', color:'#c65b4a', path:'M3 20c3-2 6-2 9 0s6 2 9 0M12 3v10M8 8c2-1 6-1 8 0' },
  city:     { bg:'#e6f2e2', color:'#3c6b41', path:'M3 21V9l5-3 5 3v12M13 21V5l5 3v13M3 21h18' }
};
function destIconSvg(type){
  const d = destIcons[type] || destIcons.city;
  return `<div class="dest-icon" style="background:${d.bg}"><svg viewBox="0 0 24 24" fill="none" stroke="${d.color}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="${d.path}"/></svg></div>`;
}
const destList = document.getElementById('destList');
destinations.forEach(d=>{
  const row = document.createElement('div');
  row.className = 'dest-row';
  row.innerHTML = `${destIconSvg(d.type)}<div class="dest-text"><strong>${d.name}</strong><span>${d.sub}</span></div>`;
  row.addEventListener('click', ()=>{
    document.getElementById('whereInput').value = d.type === 'nearby' ? '' : d.name;
    closeAllDropdowns();
    suggestionsContainer.classList.remove('active');
  });
  destList.appendChild(row);
});

/* ================= Dropdown open/close plumbing ================= */
const searchFields = ['whereField','whenField','guestsField'].map(id=>document.getElementById(id));
function closeAllDropdowns(){
  searchFields.forEach(f=>f.classList.remove('open'));
  suggestionsContainer.classList.remove('active');
}
searchFields.forEach(field=>{
  field.addEventListener('click', e=>{
    e.stopPropagation();
    closeAllDropdowns();
    field.classList.add('open');
  });
  field.querySelector('.dropdown-panel')?.addEventListener('click', e=> e.stopPropagation());
});
document.addEventListener('click', closeAllDropdowns);

/* ================= WHEN dropdown (calendar) ================= */
const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const today = new Date(); today.setHours(0,0,0,0);
let calBaseMonth = today.getMonth();
let calBaseYear = today.getFullYear();
let selectedFlex = 0;

function buildMonthGrid(year, month){
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month+1, 0).getDate();
  let cells = '';
  for(let i=0;i<firstDay;i++) cells += `<div class="cal-day empty"></div>`;
  for(let d=1; d<=daysInMonth; d++){
    const dateObj = new Date(year, month, d);
    const isPast = dateObj < today;
    const iso = dateObj.toISOString().slice(0,10);
    let cls = 'cal-day';
    if(isPast) cls += ' disabled';
    if(activeQuery.checkIn === iso || activeQuery.checkOut === iso) cls += ' selected';
    else if(activeQuery.checkIn && activeQuery.checkOut && iso > activeQuery.checkIn && iso < activeQuery.checkOut) cls += ' in-range';
    cells += `<div class="${cls}" data-date="${iso}">${d}</div>`;
  }
  return `
    <div class="cal-month-title">${MONTH_NAMES[month]} ${year}</div>
    <div class="cal-grid">
      <div class="cal-dow">S</div><div class="cal-dow">M</div><div class="cal-dow">T</div><div class="cal-dow">W</div><div class="cal-dow">T</div><div class="cal-dow">F</div><div class="cal-dow">S</div>
      ${cells}
    </div>`;
}
function renderCalendar(){
  const m1 = new Date(calBaseYear, calBaseMonth, 1);
  const m2 = new Date(calBaseYear, calBaseMonth+1, 1);
  document.getElementById('calMonths').innerHTML = `
    <div class="cal-month">${buildMonthGrid(m1.getFullYear(), m1.getMonth())}</div>
    <div class="cal-month">${buildMonthGrid(m2.getFullYear(), m2.getMonth())}</div>`;
  const canGoBack = !(calBaseYear === today.getFullYear() && calBaseMonth === today.getMonth());
  document.getElementById('calPrev').disabled = !canGoBack;

  document.querySelectorAll('.cal-day:not(.empty):not(.disabled)').forEach(cell=>{
    cell.addEventListener('click', ()=>{
      const iso = cell.dataset.date;
      if(!activeQuery.checkIn || (activeQuery.checkIn && activeQuery.checkOut)){
        activeQuery.checkIn = iso;
        activeQuery.checkOut = null;
      } else if(iso <= activeQuery.checkIn){
        activeQuery.checkIn = iso;
        activeQuery.checkOut = null;
      } else {
        activeQuery.checkOut = iso;
      }
      updateWhenInputText();
      renderCalendar();
    });
  });
}
function fmtShort(iso){
  const d = new Date(iso + 'T00:00:00');
  return `${MONTH_NAMES[d.getMonth()].slice(0,3)} ${d.getDate()}`;
}
function updateWhenInputText(){
  const input = document.getElementById('whenInput');
  if(activeQuery.checkIn && activeQuery.checkOut){
    let text = `${fmtShort(activeQuery.checkIn)} – ${fmtShort(activeQuery.checkOut)}`;
    if(selectedFlex > 0) text += ` (± ${selectedFlex} day${selectedFlex>1?'s':''})`;
    input.value = text;
  } else if(activeQuery.checkIn){
    input.value = `${fmtShort(activeQuery.checkIn)} – Add checkout`;
  } else {
    input.value = '';
  }
}
document.getElementById('calPrev').addEventListener('click', ()=>{
  calBaseMonth--; if(calBaseMonth<0){ calBaseMonth=11; calBaseYear--; }
  renderCalendar();
});
document.getElementById('calNext').addEventListener('click', ()=>{
  calBaseMonth++; if(calBaseMonth>11){ calBaseMonth=0; calBaseYear++; }
  renderCalendar();
});
document.getElementById('calQuick').addEventListener('click', e=>{
  const btn = e.target.closest('button');
  if(!btn) return;
  document.querySelectorAll('#calQuick button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  selectedFlex = Number(btn.dataset.days);
  updateWhenInputText();
});
document.getElementById('tabDates').addEventListener('click', ()=>{
  document.getElementById('tabDates').classList.add('active');
  document.getElementById('tabFlexible').classList.remove('active');
  document.getElementById('datesPane').style.display = 'block';
  document.getElementById('flexiblePane').style.display = 'none';
});
document.getElementById('tabFlexible').addEventListener('click', ()=>{
  document.getElementById('tabFlexible').classList.add('active');
  document.getElementById('tabDates').classList.remove('active');
  document.getElementById('flexiblePane').style.display = 'block';
  document.getElementById('datesPane').style.display = 'none';
});
renderCalendar();

/* ================= GUESTS dropdown ================= */
const guestCounts = { adults:0, children:0, infants:0, pets:0 };
function updateGuestsInputText(){
  const total = guestCounts.adults + guestCounts.children;
  const parts = [];
  if(total > 0) parts.push(`${total} guest${total>1?'s':''}`);
  if(guestCounts.infants > 0) parts.push(`${guestCounts.infants} infant${guestCounts.infants>1?'s':''}`);
  if(guestCounts.pets > 0) parts.push(`${guestCounts.pets} pet${guestCounts.pets>1?'s':''}`);
  document.getElementById('guestsInput').value = parts.join(', ');
  activeQuery.guests = total;
}
document.getElementById('guestsDropdown').addEventListener('click', e=>{
  const btn = e.target.closest('button[data-type]');
  if(!btn) return;
  const type = btn.dataset.type;
  const op = btn.dataset.op;
  if(op === 'inc') guestCounts[type]++;
  else guestCounts[type] = Math.max(0, guestCounts[type]-1);
  document.getElementById('count' + type.charAt(0).toUpperCase() + type.slice(1)).textContent = guestCounts[type];
  document.querySelectorAll(`button[data-type="${type}"][data-op="dec"]`).forEach(b=> b.disabled = guestCounts[type] === 0);
  updateGuestsInputText();
});
document.querySelectorAll('button[data-op="dec"]').forEach(b=> b.disabled = true);


/* ---------------- Render categories ---------------- */
const catGrid = document.getElementById('catGrid');
categories.forEach(cat=>{
  const card = document.createElement('div');
  card.className = 'cat-card';
  card.innerHTML = `
    <div class="img-wrap"><img src="${cat.img}" alt="${cat.title}"></div>
    <div class="cat-body">
      <h3>${cat.title}</h3>
      <p>${cat.desc}</p>
      <button class="explore-btn" data-key="${cat.key}">Explore</button>
    </div>`;
  catGrid.appendChild(card);
});

function resetSearchFields(){
  activeQuery = { where:'', guests:0, checkIn:null, checkOut:null };
  guestCounts.adults = 0; guestCounts.children = 0; guestCounts.infants = 0; guestCounts.pets = 0;
  selectedFlex = 0;
  document.getElementById('whereInput').value = '';
  document.getElementById('whenInput').value = '';
  document.getElementById('guestsInput').value = '';
  document.getElementById('countAdults').textContent = '0';
  document.getElementById('countChildren').textContent = '0';
  document.getElementById('countInfants').textContent = '0';
  document.getElementById('countPets').textContent = '0';
  document.querySelectorAll('button[data-op="dec"]').forEach(b=> b.disabled = true);
  document.querySelectorAll('#calQuick button').forEach(b=>b.classList.remove('active'));
  document.querySelector('#calQuick button[data-days="0"]')?.classList.add('active');
  renderCalendar();
}

catGrid.addEventListener('click', e=>{
  if(e.target.classList.contains('explore-btn')){
    activeCategory = e.target.dataset.key;
    resetSearchFields();
    renderListings();
    document.getElementById('featured').scrollIntoView({behavior:'smooth'});
  }
});

/* ---------------- Render listings ---------------- */
const track = document.getElementById('listingTrack');
const emptyState = document.getElementById('emptyState');
const filterNote = document.getElementById('filterNote');
const filterText = document.getElementById('filterText');

function starString(rating){
  const full = Math.round(rating);
  return '★'.repeat(full) + '☆'.repeat(5-full);
}

function getFiltered(){
  return listings.filter(l=>{
    if(activeCategory && l.category !== activeCategory) return false;
    if(activeQuery.where && !l.location.toLowerCase().includes(activeQuery.where.toLowerCase()) && !l.name.toLowerCase().includes(activeQuery.where.toLowerCase())) return false;
    if(activeQuery.guests && l.capacity < Number(activeQuery.guests)) return false;
    return true;
  });
}

function renderListings(){
  const data = getFiltered();
  track.innerHTML = '';

  const hasDates = activeQuery.checkIn && activeQuery.checkOut;
  if(activeCategory || activeQuery.where || activeQuery.guests || hasDates){
    filterNote.style.display = 'flex';
    let parts = [];
    if(activeCategory) parts.push(categories.find(c=>c.key===activeCategory).title);
    if(activeQuery.where) parts.push(`"${activeQuery.where}"`);
    if(hasDates) parts.push(`${fmtShort(activeQuery.checkIn)} – ${fmtShort(activeQuery.checkOut)}`);
    if(activeQuery.guests) parts.push(`${activeQuery.guests}+ guests`);
    filterText.textContent = `Showing results for ${parts.join(', ')} — ${data.length} found`;
  } else {
    filterNote.style.display = 'none';
  }

  if(data.length === 0){
    emptyState.style.display = 'block';
    track.style.display = 'none';
    return;
  }
  emptyState.style.display = 'none';
  track.style.display = 'flex';

  data.forEach(l=>{
    const card = document.createElement('div');
    card.className = 'listing-card';
    const isFav = favorites.has(l.id);
    card.innerHTML = `
      <div class="listing-img">
        <img src="${l.img}" alt="${l.name}">
        <button class="fav-btn ${isFav?'active':''}" data-id="${l.id}" aria-label="Save to favorites">
          <svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-10-9.2C0.3 8.4 2 4.5 6 4c2.2-0.3 4 1 6 3.4C14 5 15.8 3.7 18 4c4 0.5 5.7 4.4 4 7.8-2.5 4.6-10 9.2-10 9.2z"/></svg>
        </button>
        <div class="login-overlay">
          <div class="login-prompt" onclick="event.stopPropagation(); showLoginModal();">
            <a href="login.php" class="fas fa-lock"></i> Login to Book
          </div>
        </div>
      </div>
      <div class="listing-body">
        <span class="listing-tag">${categories.find(c=>c.key===l.category)?.title.split(' ')[0] || l.category}</span>
        <h4>${l.name} - ${l.location}</h4>
        <div class="listing-meta">
          <span class="listing-price">$${l.price} per night</span>
          <span class="listing-rating"><span class="stars">${starString(l.rating)}</span> ${l.rating}</span>
        </div>
      </div>`;
    track.appendChild(card);
  });
}
renderListings();

track.addEventListener('click', e=>{
  const btn = e.target.closest('.fav-btn');
  if(btn){
    const id = Number(btn.dataset.id);
    if(favorites.has(id)){
      favorites.delete(id);
      btn.classList.remove('active');
      showToast('Removed from favorites');
    } else {
      favorites.add(id);
      btn.classList.add('active');
      showToast('Saved to favorites ♥');
    }
    return;
  }
  
  const card = e.target.closest('.listing-card');
  if(card){
    showLoginModal();
  }
});

function showLoginModal() {
  document.getElementById('modalOverlay').classList.add('open');
}

document.getElementById('clearFilterBtn').addEventListener('click', ()=>{
  activeCategory = null;
  resetSearchFields();
  renderListings();
});

/* ---------------- Search form ---------------- */
document.getElementById('searchForm').addEventListener('submit', e=>{
  e.preventDefault();
  activeCategory = null;
  activeQuery.where = document.getElementById('whereInput').value.trim();
  closeAllDropdowns();
  suggestionsContainer.classList.remove('active');
  renderListings();
  document.getElementById('featured').scrollIntoView({behavior:'smooth'});
});

/* ---------------- Carousel ---------------- */
document.getElementById('nextBtn').addEventListener('click', ()=>{
  track.scrollBy({left: 300, behavior:'smooth'});
});
document.getElementById('prevBtn').addEventListener('click', ()=>{
  track.scrollBy({left: -300, behavior:'smooth'});
});

/* ---------------- Mobile nav ---------------- */
const nav = document.getElementById('nav');
document.getElementById('burgerBtn').addEventListener('click', ()=>{
  nav.classList.toggle('open');
});
nav.querySelectorAll('a').forEach(a=>{
  a.addEventListener('click', ()=>{
    nav.classList.remove('open');
    nav.querySelectorAll('a').forEach(x=>x.classList.remove('active'));
    a.classList.add('active');
  });
});

/* ---------------- Modal ---------------- */
const modalOverlay = document.getElementById('modalOverlay');
document.getElementById('modalCancel').addEventListener('click', ()=> modalOverlay.classList.remove('open'));
modalOverlay.addEventListener('click', e=>{ if(e.target === modalOverlay) modalOverlay.classList.remove('open'); });

/* ---------------- Toast ---------------- */
let toastTimer;
function showToast(msg){
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=> toast.classList.remove('show'), 2400);
}