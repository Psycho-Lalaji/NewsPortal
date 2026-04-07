<?php
// Saved News Page – displays articles the user has bookmarked (stored in localStorage)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Saved Articles – EkataNews</title>
<link rel="stylesheet" href="home.css"/>
<style>
  /* ── SAVED PAGE SPECIFIC ── */
  .saved-hero{background:var(--blue);padding:36px 32px 28px;border-bottom:4px solid var(--accent);}
  .saved-hero h1{font-family:'Playfair Display',serif;font-size:2rem;color:var(--white);margin-bottom:6px;}
  .saved-hero p{font-size:.9rem;color:var(--bright);}
  .saved-controls{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:28px;}
  .saved-count{font-size:.85rem;font-weight:700;color:var(--muted);}
  .btn-clear{background:none;border:2px solid var(--border);color:var(--muted);border-radius:var(--radius);padding:7px 16px;font-size:.8rem;font-weight:700;cursor:pointer;transition:all .2s;}
  .btn-clear:hover{border-color:var(--danger);color:var(--danger);}

  /* Grid layout for saved articles */
  .saved-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;}
  .saved-card{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 14px rgba(10,22,40,.09);display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s;}
  .saved-card:hover{transform:translateY(-3px);box-shadow:var(--shadow);}
  .saved-card .card-img{position:relative;height:180px;overflow:hidden;}
  .saved-card .card-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
  .saved-card:hover .card-img img{transform:scale(1.04);}
  .saved-card .card-cat{position:absolute;top:12px;left:12px;background:var(--accent);color:#fff;font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:3px 10px;border-radius:3px;}
  .saved-card .card-body{padding:18px;display:flex;flex-direction:column;flex:1;gap:10px;}
  .saved-card h3{font-family:'Playfair Display',serif;font-size:1rem;line-height:1.35;color:var(--navy);flex:1;cursor:pointer;}
  .saved-card h3:hover{color:var(--accent);}
  .saved-card .card-meta{font-size:.72rem;color:var(--muted);display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
  .saved-card .card-actions{display:flex;gap:8px;align-items:center;border-top:1px solid var(--border);padding-top:12px;margin-top:4px;}
  .btn-read{flex:1;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);padding:8px 14px;font-size:.82rem;font-weight:700;cursor:pointer;transition:background .2s;}
  .btn-read:hover{background:var(--bright);}
  .btn-remove{background:none;border:2px solid var(--border);color:var(--muted);border-radius:var(--radius);padding:8px 12px;font-size:.8rem;font-weight:700;cursor:pointer;transition:all .2s;white-space:nowrap;}
  .btn-remove:hover{border-color:var(--danger);color:var(--danger);}

  /* Empty state */
  .empty-state{text-align:center;padding:80px 20px;}
  .empty-state .empty-icon{font-size:3.5rem;margin-bottom:18px;}
  .empty-state h2{font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--navy);margin-bottom:10px;}
  .empty-state p{color:var(--muted);font-size:.9rem;max-width:420px;margin:0 auto 28px;}
  .btn-home{display:inline-block;background:var(--accent);color:#fff;border-radius:var(--radius);padding:12px 28px;font-size:.9rem;font-weight:700;text-decoration:none;transition:background .2s;}
  .btn-home:hover{background:var(--bright);}

  @media(max-width:560px){
    .saved-hero{padding:24px 16px 20px;}
    .saved-hero h1{font-size:1.4rem;}
    .saved-grid{grid-template-columns:1fr;}
  }
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="ticker">
    <div class="ticker-inner" id="ticker-inner">
      <span>Your saved articles – read them any time</span>
      <span>Your saved articles – read them any time</span>
    </div>
  </div>
  <div class="meta">
    <span id="live-date"></span>
    <span>🌐 Kathmandu, Nepal</span>
  </div>
</div>

<!-- HEADER -->
<header>
  <a href="home.php" class="logo"><span class="logo-dot"></span>Ekata<span>News</span></a>
  <div class="header-actions">
    <a href="home.php" class="btn btn-outline" style="text-decoration:none;">← Back to Home</a>
  </div>
</header>

<!-- NAV -->
<nav>
  <a href="home.php">All <span class="live-badge">LIVE</span></a>
  <a href="home.php">World</a>
  <a href="home.php">Politics</a>
  <a href="home.php">Technology</a>
  <a href="home.php">Sports</a>
  <a href="home.php">Business</a>
  <a href="home.php">Science</a>
</nav>

<!-- PAGE HERO -->
<div class="saved-hero">
  <h1>🔖 Saved Articles</h1>
  <p>Your personal reading list – all in one place.</p>
</div>

<!-- MAIN -->
<div class="container">
  <div class="saved-controls">
    <div class="saved-count" id="saved-count"></div>
    <button class="btn-clear" id="btn-clear-all" onclick="clearAll()" style="display:none;">Clear All Saved</button>
  </div>
  <div id="saved-content"></div>
</div>

<!-- FOOTER -->
<footer>© 2025 <span>PressCore</span> — Powered by News API &nbsp;|&nbsp; All rights reserved</footer>

<!-- ═══════ ARTICLE READ MODAL ═══════ -->
<div class="modal-overlay" id="article-modal">
  <div class="modal" id="article-modal-inner" style="max-width:760px;">
    <div class="modal-hdr">
      <h2 id="am-cat">Loading…</h2>
      <button class="modal-close" onclick="closeModal('article-modal')">×</button>
    </div>
    <div class="modal-body">
      <img id="am-img" class="art-img" src="" alt=""/>
      <div id="am-category" class="art-cat"></div>
      <h2 id="am-title"></h2>
      <div class="art-meta" id="am-meta"></div>
      <div class="art-body" id="am-body"></div>
      <div class="vote-row">
        <button onclick="voteArticle('up')">👍 <span id="am-up">0</span> Upvote</button>
        <button onclick="voteArticle('dn')">👎 <span id="am-dn">0</span> Downvote</button>
        <button class="bm-btn" onclick="removeCurrentAndClose()">🗑️ Remove from Saved</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast-el"></div>

<script>
// ══════════════════════════
//  SHARED ARTICLE DATA (mirrors home.php MOCK)
// ══════════════════════════
const MOCK = [
  {id:0,title:'Nepal Signs Historic Climate Agreement with 12 Nations',description:'The government reached a landmark environmental deal covering Himalayan glacier protection, renewable energy targets, and regional biodiversity.',category:'World',urlToImage:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=900&q=80',publishedAt:'2025-04-04T06:00:00Z',source:{name:'Kathmandu Post'},upvotes:87,downvotes:4},
  {id:1,title:'New AI Chip Breaks Speed Records in Benchmark Tests',description:'A startup unveiled a processor that outperforms current GPUs by 3x on large language model inference tasks, drawing major investment interest.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1518770660439-4636190af475?w=900&q=80',publishedAt:'2025-04-03T14:30:00Z',source:{name:'TechCrunch'},upvotes:120,downvotes:9},
  {id:2,title:'National Elections Set for October — Campaign Rules Announced',description:'The election commission finalized the schedule and issued strict campaign finance guidelines amid opposition demands for transparency.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=900&q=80',publishedAt:'2025-04-03T10:00:00Z',source:{name:'Republic World'},upvotes:55,downvotes:20},
  {id:3,title:'National Football Team Qualifies for Asian Cup 2026',description:'A stunning 2–1 victory secured Nepal\'s historic first-ever qualification for the continental championship, sparking nationwide celebrations.',category:'Sports',urlToImage:'https://images.unsplash.com/photo-1551958219-acbc630e2f4d?w=900&q=80',publishedAt:'2025-04-02T18:45:00Z',source:{name:'Sports Nepal'},upvotes:245,downvotes:2},
  {id:4,title:'Central Bank Raises Repo Rate to Combat Inflation',description:'Monetary policy tightening aims to cool a 7.2% annual inflation rate while balancing growth targets amid global supply chain pressures.',category:'Business',urlToImage:'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=900&q=80',publishedAt:'2025-04-02T09:00:00Z',source:{name:'Financial Times NP'},upvotes:33,downvotes:11},
  {id:5,title:'Researchers Discover New High-Altitude Orchid Species in Annapurna',description:'Botanists from Tribhuvan University identified a previously unknown orchid growing at 4,200m elevation, sparking biodiversity excitement.',category:'Science',urlToImage:'https://images.unsplash.com/photo-1487530811015-780f238e6a0a?w=900&q=80',publishedAt:'2025-04-01T12:00:00Z',source:{name:'Science Daily'},upvotes:68,downvotes:1},
  {id:6,title:'Pokhara International Airport Sees Record Monthly Passengers',description:'Over 180,000 international arrivals were recorded in March 2025, marking a new high and validating the infrastructure investment.',category:'World',urlToImage:'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=900&q=80',publishedAt:'2025-03-31T08:00:00Z',source:{name:'Aviation Weekly'},upvotes:41,downvotes:3},
  {id:7,title:'Budget 2025-26 Allocates 40% More to Education Sector',description:'The new fiscal budget prioritizes teacher training, digital infrastructure, and scholarship programs for students in remote districts.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=900&q=80',publishedAt:'2025-03-30T11:00:00Z',source:{name:'Kantipur Daily'},upvotes:92,downvotes:7},
  {id:8,title:'Electric Vehicle Sales Surge 300% as Subsidies Kick In',description:'Government incentive programs are driving adoption of two and four wheelers on the road, with sales tripling year-over-year.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1593941707882-a56bbc8df21d?w=900&q=80',publishedAt:'2025-03-29T15:00:00Z',source:{name:'EV Times'},upvotes:74,downvotes:5},
  {id:9,title:'Global Summit on Water Security Opens in Geneva',description:'Representatives from 78 countries converged to address fresh water scarcity projections for 2040 amid accelerating climate change.',category:'World',urlToImage:'https://images.unsplash.com/photo-1470076190571-4f4e0a10bfb4?w=900&q=80',publishedAt:'2025-03-28T07:30:00Z',source:{name:'UN News'},upvotes:49,downvotes:6},
];

let bookmarks = JSON.parse(localStorage.getItem('pc_bookmarks') || '[]');
let currentArticle = null;
let votes = {};

// ── HELPERS ──
function img(url){
  return url || 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80';
}
function timeAgo(dateStr){
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if(mins < 60) return mins + 'm ago';
  const hrs = Math.floor(mins / 60);
  if(hrs < 24) return hrs + 'h ago';
  return Math.floor(hrs / 24) + 'd ago';
}
function saveBookmarks(){
  localStorage.setItem('pc_bookmarks', JSON.stringify(bookmarks));
}

// ── RENDER SAVED ARTICLES ──
function renderSaved(){
  const container = document.getElementById('saved-content');
  const countEl   = document.getElementById('saved-count');
  const clearBtn  = document.getElementById('btn-clear-all');

  // Build full article objects for saved IDs
  const savedArticles = bookmarks
    .map(b => MOCK.find(a => a.id === b.id))
    .filter(Boolean);

  countEl.textContent = savedArticles.length
    ? savedArticles.length + ' saved article' + (savedArticles.length !== 1 ? 's' : '')
    : '';
  clearBtn.style.display = savedArticles.length ? 'inline-block' : 'none';

  if(!savedArticles.length){
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">🔖</div>
        <h2>No saved articles yet</h2>
        <p>Browse the news feed and click <strong>Save Article</strong> on any story to add it here.</p>
        <a class="btn-home" href="home.php">Browse News</a>
      </div>`;
    return;
  }

  container.innerHTML = `<div class="saved-grid">${savedArticles.map(a => articleCard(a)).join('')}</div>`;
}

function articleCard(a){
  return `
    <div class="saved-card" id="card-${a.id}">
      <div class="card-img" onclick="openArticle(${a.id})" style="cursor:pointer;">
        <img src="${img(a.urlToImage)}" alt="${escHtml(a.title)}" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80'"/>
        <span class="card-cat">${escHtml(a.category)}</span>
      </div>
      <div class="card-body">
        <h3 onclick="openArticle(${a.id})">${escHtml(a.title)}</h3>
        <div class="card-meta">
          <span>📰 ${escHtml(a.source?.name || 'PressCore')}</span>
          <span>🕐 ${timeAgo(a.publishedAt)}</span>
          <span>👍 ${a.upvotes}</span>
        </div>
        <div class="card-actions">
          <button class="btn-read" onclick="openArticle(${a.id})">Read Article</button>
          <button class="btn-remove" onclick="removeArticle(${a.id})">✕ Remove</button>
        </div>
      </div>
    </div>`;
}

function escHtml(str){
  if(!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── REMOVE ──
function removeArticle(id){
  bookmarks = bookmarks.filter(b => b.id !== id);
  saveBookmarks();
  const card = document.getElementById('card-' + id);
  if(card){
    card.style.transition = 'opacity .3s,transform .3s';
    card.style.opacity = '0';
    card.style.transform = 'scale(.95)';
    setTimeout(renderSaved, 320);
  }
  toast('Removed from saved.');
}

function clearAll(){
  if(!confirm('Remove all saved articles?')) return;
  bookmarks = [];
  saveBookmarks();
  renderSaved();
  toast('All saved articles cleared.');
}

// ── ARTICLE MODAL ──
function openArticle(id){
  const a = MOCK.find(x => x.id === id);
  if(!a) return;
  currentArticle = a;
  document.getElementById('am-img').src = img(a.urlToImage);
  document.getElementById('am-category').textContent = a.category;
  document.getElementById('am-title').textContent = a.title;
  document.getElementById('am-cat').textContent = a.category + ' · ' + (a.source?.name || 'PressCore');
  document.getElementById('am-meta').innerHTML =
    `<span>📰 ${escHtml(a.source?.name || 'News Portal')}</span>` +
    `<span>🕐 ${timeAgo(a.publishedAt)}</span>` +
    `<span>👍 ${a.upvotes}</span>` +
    `<span>👎 ${a.downvotes}</span>`;
  document.getElementById('am-up').textContent = a.upvotes;
  document.getElementById('am-dn').textContent = a.downvotes;
  document.getElementById('am-body').innerHTML =
    `<p>${escHtml(a.description || 'Full article content would appear here.')}</p>` +
    `<p style="margin-top:14px;color:var(--muted);font-size:.85rem;">Continue reading on the source website for the full story.</p>`;
  openModal('article-modal');
}

function removeCurrentAndClose(){
  if(!currentArticle) return;
  removeArticle(currentArticle.id);
  closeModal('article-modal');
}

function voteArticle(dir){
  if(!currentArticle) return;
  const key = currentArticle.id + '_' + dir;
  if(votes[key]){ toast('Already voted!'); return; }
  votes[key] = true;
  if(dir === 'up') currentArticle.upvotes++; else currentArticle.downvotes++;
  document.getElementById('am-up').textContent = currentArticle.upvotes;
  document.getElementById('am-dn').textContent = currentArticle.downvotes;
  toast(dir === 'up' ? 'Upvoted! 👍' : 'Downvoted 👎');
}

// ── MODALS ──
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target === o) o.classList.remove('open'); });
});

// ── TOAST ──
function toast(msg){
  const el = document.getElementById('toast-el');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2800);
}

// ── DATE ──
function updateDate(){
  const d = new Date();
  document.getElementById('live-date').textContent =
    d.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'});
}

// ── INIT ──
document.addEventListener('DOMContentLoaded', () => {
  updateDate();
  setInterval(updateDate, 60000);
  renderSaved();
});
</script>
</body>
</html>
