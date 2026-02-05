/* ========= Mobile menu toggle ========= */
(function () {
  const burger = document.getElementById('burger');
  const mobileMenu = document.getElementById('mobileMenu');
  if (!burger || !mobileMenu) return;

  const toggle = () => {
    const open = mobileMenu.classList.toggle('open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.documentElement.style.overflow = open ? 'hidden' : '';
  };

  burger.addEventListener('click', toggle);

  mobileMenu.addEventListener('click', (e) => {
    if (e.target.closest('a')) {
      mobileMenu.classList.remove('open');
      burger.setAttribute('aria-expanded','false');
      document.documentElement.style.overflow = '';
    }
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
      mobileMenu.classList.remove('open');
      burger.setAttribute('aria-expanded','false');
      document.documentElement.style.overflow = '';
    }
  });
})();

/* ========= Sticky navbar on scroll ========= */
(function () {
  const nav = document.querySelector('.nav');
  if (!nav) return;
  const THRESHOLD = 10;
  function onScroll() {
    if (window.scrollY > THRESHOLD) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('load', onScroll);
  onScroll();
})();

/* ========= Footer year ========= */
(function () {
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();
})();

/* ========= Highlight active nav link (safe if header already injected) ========= */
(function(){
  function highlightActive(){
    const path = location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.links a, .mobile a').forEach(a=>{
      if (a.getAttribute('href') === path) a.classList.add('active');
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', highlightActive, { once:true });
  } else {
    highlightActive();
  }
})();

/* ========= Better anchor offset under fixed header ========= */
(function () {
  function headerHeight() {
    const top = document.querySelector('.topbar');
    const nav = document.querySelector('.nav');
    return ((top?.offsetHeight)||0) + ((nav?.offsetHeight)||0);
  }
  function offsetHash() {
    if (!location.hash) return;
    const el = document.querySelector(location.hash);
    if (!el) return;
    const y = el.getBoundingClientRect().top + window.pageYOffset - headerHeight() - 8;
    window.scrollTo({ top: y, behavior: 'auto' });
  }
  window.addEventListener('hashchange', offsetHash);
  window.addEventListener('load', offsetHash);
})();

/* ========= Stats Counter + Reveal (also triggers on load) ========= */
(function(){
  const counters = document.querySelectorAll('.counter');
  const stats = document.querySelectorAll('.stat');
  let started = false;

  function animateCounters() {
    if (started || counters.length === 0) return;
    started = true;

    counters.forEach(counter => {
      const target = +counter.getAttribute('data-target');
      const increment = Math.max(1, target / 100);
      let count = 0;

      const update = () => {
        count += increment;
        if (count < target){
          counter.textContent = Math.ceil(count);
          requestAnimationFrame(update);
        } else {
          counter.textContent = target + "+";
        }
      };
      update();
    });
  }

  function onScroll() {
    const trigger = document.querySelector('.stats');
    if(!trigger) return;
    const rect = trigger.getBoundingClientRect();
    if(rect.top < window.innerHeight - 100){
      animateCounters();
      stats.forEach(s => s.classList.add('visible'));
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('load', onScroll);
    }
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('load', onScroll);
  onScroll();
})();

/* ========= Reveal steps on scroll ========= */
(function(){
  const items = document.querySelectorAll('.apply-steps .step');
  if (!items.length) return;

  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if (entry.isIntersecting){
        entry.target.classList.add('in-view');
        io.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });

  items.forEach(el => io.observe(el));
})();

/* ========= Partners logo marquee (guard double-clone) ========= */
(function(){
  const track = document.getElementById('logoTrack');
  if (!track || track.dataset.cloned === '1') return;

  const fragment = document.createDocumentFragment();
  Array.from(track.children).forEach(node => fragment.appendChild(node.cloneNode(true)));
  track.appendChild(fragment);
  track.dataset.cloned = '1';

  const totalItems = track.children.length;
  const seconds = Math.max(30, Math.min(90, totalItems * 2));
  track.style.animationDuration = seconds + 's';
})();

/* ========= News: helpers ========= */
async function loadJSON(path){
  try{
    const res = await fetch(path, {cache: "no-store"});
    if(!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return await res.json();
  }catch(err){
    console.warn("JSON load failed:", path, err);
    return null;
  }
}

/* Demo fallback data */
const DEMO_UPDATES = [
  { id:"upd-1", title:"Study in Diploma Program with Paid Internship", summary:"Students can earn money through paid internships while studying for their diploma.", image:"assets/article-1.jpg", url:"post.html?id=upd-1", pinned:true, date:"2025-08-27", content:"<p>Hands-on industry experience with paid internships.</p>" },
  { id:"upd-2", title:"Apply for CSC Scholarship 2026", summary:"2026 CSC scholarship application already started.", image:"assets/hero.jpg", url:"post.html?id=upd-2", pinned:true, date:"2025-08-22", content:"<p>Prepare transcripts, passport, references, study plan.</p>" },
  { id:"upd-3", title:"March 2026 Intake Opens", summary:"March intake application opens from January 2026.", image:"assets/hero.jpg", url:"post.html?id=upd-3", pinned:true, date:"2025-08-18", content:"<p>Submit early for better placement.</p>" }
];

const DEMO_ARTICLES = [
  { id:"art-1", title:"Prepare Your Document", summary:"Quick checklist to prepare your documents.", image:"assets/article-1.jpg", url:"post.html?id=art-1", pinned:true, date:"2025-08-26", content:"<p>Passport, transcripts, NCR, medical, bank statement, study plan.</p>" },
  { id:"art-2", title:"How to Apply for Non-Criminal Record", summary:"Step-by-step guideline for the non-criminal record application.", image:"assets/hero.jpg", url:"post.html?id=art-2", pinned:true, date:"2025-08-21", content:"<p>Contact local police; collect certificate; notarize.</p>" },
  { id:"art-3", title:"Visa Application Guide", summary:"Simple step-by-step guide for student visa.", image:"assets/article-1.jpg", url:"post.html?id=art-3", pinned:true, date:"2025-08-20", content:"<p>Book appointment after Admission Notice & JW; processing 3–7 days.</p>" }
];

function pickData(loaded, demo){
  if(!Array.isArray(loaded) || loaded.length === 0) return demo;
  return loaded;
}

/* Render cards */
function renderList(el, items, { limit = 3, onlyPinned = true } = {}){
  if(!el) return;

  let list = Array.isArray(items) ? items.slice() : [];
  if (onlyPinned) list = list.filter(x => x.pinned === true);
  list.sort((a,b)=> new Date(b.date) - new Date(a.date));
  if (limit && limit > 0) list = list.slice(0, limit);

  el.innerHTML = list.map(item => `
    <a class="news-card" href="${item.url}" style="text-decoration:none; color:inherit">
      <div class="thumb">
        <img src="${item.image || "assets/article-1.jpg"}" alt="">
      </div>
      <div class="text">
        <h4>${item.title}</h4>
        <p class="summary">${item.summary || ""}</p>
        <p class="author">
          <span class="author-icon">👤</span>
          ${item.author || "DemoticEdu Team"}
        </p>
      </div>
    </a>
  `).join('');
}

/* ===== Programs ===== */
async function loadPrograms() {
  const urls = [
    '../api/programs.php?limit=50',
    '/api/programs.php?limit=50',
    '/api/programs.php?limit=50',
  ];

  for (const url of urls) {
    try {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) continue;

      const json = await res.json();
      const items = Array.isArray(json) ? json : (json.items || []);

      return items.map(p => ({
        ...p,
        id: String(p.id),
        title: p.title || p.name || '',
        level: p.level || '',
        duration: p.duration || '',
        location: p.location || p.campus || '',
        fee: p.fee || p.tuition || '',
        icon: p.icon || '🎓',
        pinned: !!p.pinned,
        content: p.content || p.description || '',
        image: p.image || ''
      }));
    } catch (e) {}
  }

  console.warn('Programs load failed: API not reachable in any known path.');
  return [];
}

/* ✅ FIX: Missing function (this caused: selectPrograms is not defined) */
function selectPrograms(items, { onlyPinned = true, minCount = 6 } = {}) {
  const all = Array.isArray(items) ? items.slice() : [];
  const pinned = all.filter(p => p.pinned);

  if (!onlyPinned) return all;

  if (pinned.length >= minCount) return pinned.slice(0, minCount);

  const rest = all.filter(p => !p.pinned);
  return [...pinned, ...rest].slice(0, minCount);
}

/* ✅ FIX: Ensure programs render into #programs-list */
function renderPrograms(container, items, opts = {}) {
  if (!container) return;
  const openInNewTab = opts.openInNewTab ?? false;

  container.innerHTML = (items || []).map(p => {
    const href = `program.html?id=${encodeURIComponent(p.id)}`;
    const target = openInNewTab ? ' target="_blank" rel="noopener"' : '';

    return `
      <a class="prog-card" href="${href}"${target}>
        <div class="prog-top">
          <div class="prog-icon">${p.icon || '🎓'}</div>
          <div>
            <h3 class="prog-title">${p.title || ''}</h3>
            <p class="prog-level">${p.level || ''}</p>
          </div>
        </div>
        <div class="prog-meta">
          <span>${p.duration || ''}</span>
          <span>${p.location || ''}</span>
        </div>
        <div class="prog-actions">
          <span class="prog-fee">${p.fee || ''}</span>
          <span class="prog-cta">Apply</span>
        </div>
      </a>
    `;
  }).join('');
}

/* ===== Testimonials (Trust) ===== */
async function loadTestimonials() {
  const urls = [
    '/api/testimonials.php?limit=50',
    'api/testimonials.php?limit=50',
    './api/testimonials.php?limit=50',
  ];

  for (const url of urls) {
    try {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) continue;

      const json = await res.json();
      const items = Array.isArray(json) ? json : (json.items || []);

      return items.map(t => ({
        ...t,
        id: String(t.id),
        photo: t.photo_url || t.photo || 'assets/default-user.png',
        quote: t.message || t.quote || '',
        story: t.message || t.story || '',
        date: t.created_at || t.date || new Date().toISOString(),
        scholarship: t.scholarship || ''
      }));
    } catch (e) {}
  }

  console.warn('Testimonials load failed: API not reachable in any known path.');
  return [];
}


function selectTestimonials(items){
  const arr = Array.isArray(items) ? items.slice() : [];
  arr.sort((a,b)=>{
    const ap = a.pinned ? 1 : 0, bp = b.pinned ? 1 : 0;
    if (bp - ap !== 0) return bp - ap;
    return new Date(b.date) - new Date(a.date);
  });
  return arr;
}

function badgeClass(s){
  const v = (s||'').toLowerCase();
  if (v === 'full') return 'sch-full';
  if (v === 'partial') return 'sch-partial';
  return 'sch-none';
}

function renderTestimonialsCarousel(trackEl, dotsEl, items){
  if(!trackEl) return;

  trackEl.innerHTML = items
    .map(t => {
      const comment = t.quote || t.message || t.comment || t.story || "";
      const short = comment.length > 220 ? comment.slice(0, 220) + "…" : comment;

      const photo = t.photo || t.photo_url || "assets/default-user.png";
      const program = t.program || "";
      const country = t.country || "";
      const university = t.university || "";

      const metaLine = [program, country].filter(Boolean).join(" · ");

      return `
        <div class="t-card">
          <div class="t-top">
            <div class="t-photo">
              <img src="${photo}" alt="${t.name || "Student"} photo">
            </div>
            <div class="t-meta">
              <h3 class="t-name">${t.name || ""}</h3>
              <p class="t-uni">${university}</p>
              ${metaLine ? `<p class="t-sub">${metaLine}</p>` : ``}
            </div>
          </div>
          <p class="t-quote">${short}</p>
        </div>
      `;
    })
    .join("");

  const carousel = trackEl.closest('.t-carousel');
  if (!carousel) return;

  const viewport = carousel.querySelector('.t-viewport');
  const prevBtn = carousel.querySelector('.t-nav.prev');
  const nextBtn = carousel.querySelector('.t-nav.next');

  const cards = Array.from(trackEl.children);
  if (cards.length === 0) {
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    if (dotsEl)  dotsEl.innerHTML = '';
    return;
  }

  function perView(){
    const w = viewport.clientWidth;
    if (w <= 640) return 1;
    if (w <= 980) return 2;
    return 3;
  }

  let index = 0;
  function maxIndex(){ return Math.max(0, cards.length - perView()); }

  function goTo(i){
    index = Math.max(0, Math.min(i, maxIndex()));
    const cardWidth = cards[0].getBoundingClientRect().width + 18;
    trackEl.style.transform = `translateX(${-index * cardWidth}px)`;
    updateDots();
  }

  function buildDots(){
    if(!dotsEl) return;
    const pages = maxIndex() + 1;
    dotsEl.innerHTML = Array.from({length: pages}, (_,i)=>`<button class="t-dot" data-i="${i}"></button>`).join('');
    dotsEl.addEventListener('click', (e)=>{
      const btn = e.target.closest('.t-dot');
      if(!btn) return;
      goTo(+btn.dataset.i);
      stopAuto();
    });
  }

  function updateDots(){
    if(!dotsEl) return;
    const pages = dotsEl.querySelectorAll('.t-dot');
    pages.forEach((d,i)=>d.classList.toggle('active', i===index));
  }

  if (prevBtn) prevBtn.addEventListener('click', ()=>{ goTo(index-1); stopAuto(); });
  if (nextBtn) nextBtn.addEventListener('click', ()=>{ goTo(index+1); stopAuto(); });

  let timer = null;
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function startAuto(){
    if (prefersReduced) return;
    timer = setInterval(()=>{
      if (index >= maxIndex()) goTo(0);
      else goTo(index+1);
    }, 4000);
  }
  function stopAuto(){ if(timer){ clearInterval(timer); timer=null; } }

  const shell = carousel;
  shell.addEventListener('mouseenter', stopAuto);
  shell.addEventListener('mouseleave', startAuto);
  document.addEventListener('visibilitychange', ()=>{ document.hidden ? stopAuto() : startAuto(); });

  buildDots();
  goTo(0);
  startAuto();

  window.addEventListener('resize', ()=>{
    trackEl.style.transition = 'none';
    goTo(index);
    requestAnimationFrame(()=>{ trackEl.style.transition = ''; });
  }, {passive:true});
}

/* ===== Initializers that work even when DOMContentLoaded already fired ===== */
async function initTestimonialsSection(){
  const track = document.getElementById('t-track');
  if (!track) return;
  const dots = document.getElementById('t-dots');
  const data = await loadTestimonials();
  const items = selectTestimonials(data);
  renderTestimonialsCarousel(track, dots, items);
}

async function initProgramsHome(){
  const progBox = document.getElementById('programs-list');
  if (!progBox) return;

  const programs = await loadPrograms();
  const featured = selectPrograms(programs, { onlyPinned: true, minCount: 6 });
  renderPrograms(progBox, featured, { openInNewTab: false });
}

async function initNewsEverywhere(){
  const latestBox   = document.getElementById('latest-list');
  const usefulBox   = document.getElementById('useful-list');
  const updatesAll  = document.getElementById('updates-all');
  const articlesAll = document.getElementById('articles-all');
  const postEl      = document.getElementById('post');

  const needUpdates  = !!(latestBox || updatesAll || postEl);
  const needArticles = !!(usefulBox || articlesAll || postEl);

  const [updatesRaw, articlesRaw] = await Promise.all([
    needUpdates  ? loadJSON('data/latest-updates.json')   : Promise.resolve(null),
    needArticles ? loadJSON('data/useful-articles.json') : Promise.resolve(null)
  ]);

  const updates  = pickData(updatesRaw,  DEMO_UPDATES);
  const articles = pickData(articlesRaw, DEMO_ARTICLES);

  if (latestBox)  renderList(latestBox,  updates,  { limit: 4, onlyPinned: true });
  if (usefulBox)  renderList(usefulBox,  articles, { limit: 4, onlyPinned: true });

  if (updatesAll)  renderList(updatesAll, updates, { limit: 0, onlyPinned: false });
  if (articlesAll) renderList(articlesAll, articles, { limit: 0, onlyPinned: false });

  if (postEl){
    const params = new URLSearchParams(location.search);
    const id = params.get('id');
    const all = [...updates, ...articles];
    const post = all.find(p => p.id === id);

    if(!post){
      postEl.innerHTML = `<h2 class="post-title">Post not found</h2>`;
      return;
    }

    postEl.innerHTML = `
      <h1 class="post-title">${post.title}</h1>
      ${post.image ? `<img src="${post.image}" class="post-img" alt="">` : ""}
      <div class="post-body">${post.content}</div>
    `;

    const copyBtn = document.getElementById('copyLink');
    if (copyBtn) {
      copyBtn.onclick = () => {
        navigator.clipboard.writeText(location.href);
        alert('Link copied!');
      };
    }

    const moreBox = document.getElementById('post-more');
    if (moreBox){
      const others = articles.filter(a => a.id !== id).slice(0,5);
      moreBox.innerHTML = others.map(a => `
        <a class="mini-post" href="${a.url}">
          <img src="${a.image || 'assets/article-1.jpg'}" alt="">
          <div>
            <strong>${a.title}</strong>
            <p>${new Date(a.date).toLocaleDateString()}</p>
          </div>
        </a>
      `).join('');
    }
  }
}

/* Run initializers NOW if DOM is ready; otherwise, wait for DOMContentLoaded */
function runWhenReady(fn){
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn, { once:true });
  } else {
    fn();
  }
}

runWhenReady(initNewsEverywhere);
runWhenReady(initProgramsHome);
runWhenReady(initTestimonialsSection);

/* =========================
   UNIVERSITIES: LIST + DETAIL
   ========================= */

// Load Universities from API (once)
let __UNIS_CACHE = null;
async function loadUniversities(){
  if (__UNIS_CACHE) return __UNIS_CACHE;

  const API = '/api/universities.php';
  console.log('Universities API URL:', API);

  const toList = s => (s||'')
    .replace(/^For\s*Bachelor:\s*/i,'')
    .split(/[,;|]/).map(x=>x.trim()).filter(Boolean);

  const res = await fetch(API, { cache: 'no-store' });
  if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);

  const rows = await res.json();
  console.log('API universities rows:', rows.length, rows);

  const imgBase = '/uploads/universities/';

  __UNIS_CACHE = rows.map(r => ({
    id: String(r.id),
    name: r.name,
    country: r.country || '',
    province: r.province || '',
    city: r.city || '',
    website: r.website || '',
    programs: toList(r.program),
    subjects: toList(r.subject),
    scholarship: r.scholarship || '',
    cover: r.hero_url ? imgBase + r.hero_url
          : r.logo_url ? imgBase + r.logo_url
          : '',
    about: '',
    gallery: [],
    videos: []
  }));
  return __UNIS_CACHE;
}

async function loadUniversityById(id){
  const API_ONE = '/api/universities.php?id=' + encodeURIComponent(id);

  try{
    const res = await fetch(API_ONE, { cache: "no-store" });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    const r = await res.json();

    const imgBase = '/uploads/universities/';

    return {
      id: String(r.id),
      name: r.name,
      country: r.country || "",
      province: r.province || "",
      city: r.city || "",
      website: r.website || "",
      programs: r.program ? r.program.split(/[,;|]/).map(s=>s.trim()).filter(Boolean) : [],
      subjects: r.subject ? r.subject.split(/[,;|]/).map(s=>s.trim()).filter(Boolean) : [],
      scholarship: r.scholarship || "",
      cover: r.hero_url ? imgBase + r.hero_url
            : r.logo_url ? imgBase + r.logo_url
            : "",
      about: r.description || "",
      gallery: [],
      videos: [],
      world_rank: r.world_rank || r.rank || ""
    };
  }catch(e){
    console.warn("University detail API failed:", e);
    return null;
  }
}

function uniqueSorted(list){ return Array.from(new Set(list)).sort((a,b)=> (''+a).localeCompare(b)); }

async function initUniversitiesList(){
  const grid = document.getElementById('uni-grid');
  if (!grid) return;

  const pager     = document.getElementById('uni-pager');
  const selProg   = document.getElementById('f-program');
  const selSub    = document.getElementById('f-subject');
  const selProv   = document.getElementById('f-province');
  const txtSearch = document.getElementById('f-search');

  const data = await loadUniversities();

  const subjects = uniqueSorted(data.flatMap(u => u.subjects || []));
  selSub.innerHTML =
    `<option value="">Subject (All)</option>` +
    subjects.map(s => `<option>${s}</option>`).join('');

  const provinces = uniqueSorted(data.map(u => u.province).filter(Boolean));
  selProv.innerHTML =
    `<option value="">Province (All)</option>` +
    provinces.map(p => `<option>${p}</option>`).join('');

  let state = {
    program: '',
    subject: '',
    province: '',
    q: '',
    page: 1,
    perPage: 18
  };

  function applyFilter(){
    const q = state.q.toLowerCase().trim();
    return data.filter(u => {
      if (state.program && !(u.programs || []).includes(state.program)) return false;
      if (state.subject && !(u.subjects || []).includes(state.subject)) return false;
      if (state.province && u.province !== state.province) return false;

      if (q){
        const hay = [u.name, u.city, u.province, u.country].filter(Boolean).join(' ').toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });
  }

  function render(){
    const items = applyFilter();
    const totalPages = Math.max(1, Math.ceil(items.length / state.perPage));
    state.page = Math.min(state.page, totalPages);

    const start = (state.page - 1) * state.perPage;
    const pageItems = items.slice(start, start + state.perPage);

    grid.innerHTML = pageItems.map(u => {
      const location = [u.city, u.province, u.country].filter(Boolean).join(', ');
      return `
        <a class="u-card" href="university.html?id=${encodeURIComponent(u.id)}" target="_blank" rel="noopener">
          <div class="u-logo">
            <img src="${u.cover || ''}" alt="${u.name}">
          </div>
          <h3 class="u-name">${u.name}</h3>
          <div class="u-meta">${location || 'Location coming soon'}</div>
        </a>
      `;
    }).join('');

    let html = '';
    if (totalPages > 1){
      const go = (i) => `<button data-i="${i}" class="${i === state.page ? 'active' : ''}">${i}</button>`;
      html += `<button data-i="${Math.max(1, state.page - 1)}">&laquo;</button>`;
      const win  = 3;
      const from = Math.max(1, state.page - win);
      const to   = Math.min(totalPages, state.page + win);
      for (let i = from; i <= to; i++) html += go(i);
      html += `<button data-i="${Math.min(totalPages, state.page + 1)}">&raquo;</button>`;
    }
    pager.innerHTML = html || '';

    pager.onclick = (e) => {
      const b = e.target.closest('button');
      if (!b) return;
      const i = +b.dataset.i;
      if (i && i !== state.page){
        state.page = i;
        render();
      }
    };
  }

  selProg.onchange   = () => { state.program  = selProg.value;  state.page = 1; render(); };
  selSub.onchange    = () => { state.subject  = selSub.value;   state.page = 1; render(); };
  selProv.onchange   = () => { state.province = selProv.value;  state.page = 1; render(); };
  txtSearch.oninput  = () => { state.q        = txtSearch.value; state.page = 1; render(); };

  render();
}

async function initUniversityDetail(){
  const nameEl = document.getElementById('u-name');
  if (!nameEl) return;

  const params = new URLSearchParams(location.search);
  const id = params.get('id');

  const listData = await loadUniversities();
  let u = listData.find(x => x.id === id);

  const fullData = await loadUniversityById(id);
  if (fullData) u = { ...(u || {}), ...fullData };

  if (!u){
    const t = document.getElementById('u-title');
    if (t) t.textContent = 'University Not Found';
    return;
  }

  document.title = `${u.name} — DemoticEdu`;
  const titleEl = document.getElementById('u-title');
  if (titleEl) titleEl.textContent = u.name;

  const subEl = document.getElementById('u-sub');
  if (subEl) subEl.textContent = [u.city, u.province, u.country].filter(Boolean).join(', ');

  const heroEl = document.querySelector('.uni-hero');
  if (heroEl) {
    const heroBg = u.cover || 'assets/hero.jpg';
    heroEl.style.backgroundImage =
      `linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.35)), url('${heroBg}')`;
    heroEl.style.backgroundSize = 'cover';
    heroEl.style.backgroundPosition = 'center';
  }

  nameEl.textContent = u.name;

  const logoEl = document.getElementById('u-logo');
  if (logoEl) logoEl.src = u.cover || '';

  const cityEl = document.getElementById('u-city');
  if (cityEl) cityEl.textContent = u.city || '';

  const provEl = document.getElementById('u-prov');
  if (provEl) provEl.textContent = u.province || '';

  const countryEl = document.getElementById('u-country');
  if (countryEl) countryEl.textContent = u.country ? ` • ${u.country}` : '';

  const rankBox   = document.querySelector('.uni-header-facts');
  const rankValue = document.getElementById('u-rank');
  const rankTag   = document.getElementById('u-rank-tag');

  if (rankBox && rankValue && rankTag) {
    const rawRank = (u.world_rank || u.rank || "").toString().trim();
    const rankNum = parseInt(rawRank, 10);

    if (!rawRank || isNaN(rankNum)) {
      rankBox.style.display = 'none';
    } else {
      rankValue.textContent = `#${rankNum.toLocaleString('en-US')}`;
      let tagText = '';
      if (rankNum <= 200)       tagText = 'Top 200 worldwide';
      else if (rankNum <= 500)  tagText = 'Top 500 worldwide';
      else if (rankNum <= 1000) tagText = 'Top 1000 worldwide';
      else                      tagText = 'Globally ranked';

      rankTag.textContent = tagText;
      rankTag.style.display = 'inline-flex';
    }
  }

  const badgesEl = document.getElementById('u-badges');
  if (badgesEl) badgesEl.innerHTML = (u.programs||[]).map(p => `<span class="badge">${p}</span>`).join('');

  const aboutPanel = document.getElementById('panel-about');
  if (aboutPanel){
    const aboutHtml  = u.about || '<p>—</p>';
    const shouldCollapse = !!u.about && u.about.length > 500;

    aboutPanel.classList.remove('about-collapsed','about-expanded');

    aboutPanel.innerHTML = `
      <div class="panel-section-title">About University</div>
      <div class="about-inner">${aboutHtml}</div>
      ${shouldCollapse ? '<button type="button" class="panel-toggle about-toggle">Read more</button>' : ''}
    `;

    if (shouldCollapse){
      aboutPanel.classList.add('about-collapsed');
      const btn = aboutPanel.querySelector('.about-toggle');
      btn.addEventListener('click', () => {
        const isCollapsed = aboutPanel.classList.contains('about-collapsed');
        aboutPanel.classList.toggle('about-collapsed', !isCollapsed);
        aboutPanel.classList.toggle('about-expanded', isCollapsed);
        btn.textContent = isCollapsed ? 'Show less' : 'Read more';
      });
    }
  }
}

/* ---- Universities bootstrap ---- */
function __startUniversities() {
  try { initUniversitiesList(); } catch (e) { console.warn('initUniversitiesList error:', e); }
  try { initUniversityDetail(); } catch (e) { console.warn('initUniversityDetail error:', e); }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', __startUniversities, { once: true });
} else {
  __startUniversities();
}
