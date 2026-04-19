<?php
// Add any server-side PHP logic here (e.g. DB queries, session handling)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>News Portal - Live News Portal</title>
    <link rel="stylesheet" href="home.css"/>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="ticker">
    <div class="ticker-inner" id="ticker-inner">
      <span>Loading live headlines…</span>
      <span>Loading live headlines…</span>
    </div>
  </div>
  <div class="meta">
    <span id="live-date"></span>
    <span>🌐 Kathmandu, Nepal</span>
  </div>
</div>

<!-- HEADER -->
<header>
  <a href="#" class="logo"><span class="logo-dot"></span>Ekata<span>News</span></a>
  <div class="header-actions">
    <button class="btn btn-outline" onclick="openModal('login-modal')">Login</button>
    <button class="btn btn-solid" onclick="openModal('admin-modal')">Admin / Editor</button>
  </div>
</header>

<!-- NAV -->
<nav>
  <a href="#" class="active" onclick="filterCat(null,this)">All <span class="live-badge">LIVE</span></a>
  <a href="#" onclick="filterCat('World',this)">World</a>
  <a href="#" onclick="filterCat('Politics',this)">Politics</a>
  <a href="#" onclick="filterCat('Technology',this)">Technology</a>
  <a href="#" onclick="filterCat('Sports',this)">Sports</a>
  <a href="#" onclick="filterCat('Business',this)">Business</a>
  <a href="#" onclick="filterCat('Science',this)">Science</a>
</nav>

<!-- MAIN -->
<div class="container">
  <div class="grid-main">

    <!-- LEFT CONTENT -->
    <div>
      <!-- HERO -->
      <div class="sec-hdr">
        <span class="sec-tag">Breaking</span>
        <div class="bar"></div>
      </div>
      <div id="hero-section" class="loading-row"><div class="spinner"></div> Fetching top story…</div>

      <!-- CATEGORY STRIP -->
      <div class="sec-hdr" style="margin-top:36px;">
        <h2>Latest Stories</h2>
        <div class="bar"></div>
      </div>
      <div id="cat-strip" class="cat-strip"></div>

      <!-- MORE NEWS -->
      <div class="sec-hdr">
        <h2>More Headlines</h2>
        <div class="bar"></div>
        <span id="active-cat-label" style="font-size:.75rem;color:var(--muted);font-weight:700;"></span>
      </div>
      <div id="more-news" class="side-stack"></div>
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar">

      <!-- Search -->
      <div class="search-box">
        <h3>Search News</h3>
        <div class="search-row">
          <input type="text" id="search-input" placeholder="Keywords…" oninput="liveSearch(this.value)"/>
          <button onclick="liveSearch(document.getElementById('search-input').value)">🔍</button>
        </div>
        <div class="cat-filters" id="cat-filters">
          <button class="cat-chip active" onclick="filterChip('all',this)">All</button>
          <button class="cat-chip" onclick="filterChip('world',this)">World</button>
          <button class="cat-chip" onclick="filterChip('politics',this)">Politics</button>
          <button class="cat-chip" onclick="filterChip('technology',this)">Tech</button>
          <button class="cat-chip" onclick="filterChip('sports',this)">Sports</button>
          <button class="cat-chip" onclick="filterChip('business',this)">Business</button>
        </div>
      </div>

      <!-- Trending -->
      <div class="widget">
        <div class="widget-hdr">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          Trending Now
        </div>
        <div class="trend-list" id="trend-list"><div class="loading-row"><div class="spinner"></div></div></div>
      </div>

      <!-- Bookmarks -->
      <div class="widget">
        <div class="widget-hdr">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          Saved Articles
        </div>
        <div class="bm-list" id="bm-list">
          <div style="padding:16px 18px;font-size:.82rem;color:var(--muted);">No saved articles yet.</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>© 2025 <span>PressCore</span> — Powered by News API &nbsp;|&nbsp; All rights reserved</footer>

<!-- ═══════ LOGIN MODAL ═══════ -->
<div class="modal-overlay" id="login-modal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-hdr">
      <h2>Sign In</h2>
      <button class="modal-close" onclick="closeModal('login-modal')">×</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Email</label>
        <input type="email" placeholder="you@example.com"/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" placeholder="••••••••"/>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select>
          <option>User (Reader)</option>
          <option>Editor</option>
          <option>Admin</option>
        </select>
      </div>
      <button class="btn btn-solid" style="width:100%;padding:12px;" onclick="fakeLogin()">Sign In</button>
    </div>
  </div>
</div>

<!-- ═══════ ADMIN MODAL ═══════ -->
<div class="modal-overlay" id="admin-modal">
  <div class="modal">
    <div class="modal-hdr">
      <h2>Admin &amp; Editor Dashboard</h2>
      <button class="modal-close" onclick="closeModal('admin-modal')">×</button>
    </div>
    <div class="modal-body">
      <div class="tabs">
        <div class="tab active" onclick="switchTab(this,'tab-overview')">Overview</div>
        <div class="tab" onclick="switchTab(this,'tab-approval')">Approval Queue</div>
        <div class="tab" onclick="switchTab(this,'tab-write')">Write Article</div>
        <div class="tab" onclick="switchTab(this,'tab-users')">Manage Users</div>
        <div class="tab" onclick="switchTab(this,'tab-cats')">Categories</div>
      </div>

      <!-- Overview -->
      <div class="tab-panel active" id="tab-overview">
        <div class="stats-grid">
          <div class="stat-box"><div class="stat-num">142</div><div class="stat-lbl">Total Articles</div></div>
          <div class="stat-box"><div class="stat-num">8</div><div class="stat-lbl">Pending Review</div></div>
          <div class="stat-box"><div class="stat-num">24</div><div class="stat-lbl">Active Editors</div></div>
          <div class="stat-box"><div class="stat-num">3,821</div><div class="stat-lbl">Readers Today</div></div>
          <div class="stat-box"><div class="stat-num">97</div><div class="stat-lbl">Upvotes Today</div></div>
          <div class="stat-box"><div class="stat-num">5</div><div class="stat-lbl">Categories</div></div>
        </div>
        <p style="color:var(--muted);font-size:.85rem;">This is the admin overview. Use the tabs above to manage articles, users, and categories.</p>
      </div>

      <!-- Approval -->
      <div class="tab-panel" id="tab-approval">
        <table class="approval-table">
          <thead><tr><th>Title</th><th>Editor</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="approval-tbody"></tbody>
        </table>
      </div>

      <!-- Write -->
      <div class="tab-panel" id="tab-write">
        <div class="form-row">
          <div class="form-group">
            <label>Article Title</label>
            <input type="text" placeholder="Enter headline…"/>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select><option>Politics</option><option>Technology</option><option>Sports</option><option>World</option><option>Business</option><option>Science</option></select>
          </div>
        </div>
        <div class="form-group">
          <label>Tags (comma separated)</label>
          <input type="text" placeholder="e.g. nepal, economy, 2025"/>
        </div>
        <div class="form-group">
          <label>Image URL</label>
          <input type="text" placeholder="https://…"/>
        </div>
        <div class="form-group">
          <label>Content</label>
          <textarea placeholder="Write your article here…" style="min-height:160px;"></textarea>
        </div>
        <div style="display:flex;gap:10px;">
          <button class="btn btn-outline" style="color:var(--navy);border-color:var(--border);" onclick="toast('Saved as Draft')">Save Draft</button>
          <button class="btn btn-solid" onclick="toast('Submitted for Approval!')">Submit for Approval</button>
        </div>
      </div>

      <!-- Users -->
      <div class="tab-panel" id="tab-users">
        <div style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;color:var(--navy);">User Management</span>
          <button class="btn btn-solid" style="font-size:.8rem;" onclick="toast('User added!')">+ Add User</button>
        </div>
        <table class="approval-table">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
          <tbody>
            <tr><td>Sita Sharma</td><td>sita@press.np</td><td><span class="status-pill pill-approved">Admin</span></td><td><button class="btn-xs btn-reject" onclick="toast('User removed')">Remove</button></td></tr>
            <tr><td>Rajan Thapa</td><td>rajan@press.np</td><td><span class="status-pill pill-pending">Editor</span></td><td><button class="btn-xs btn-reject" onclick="toast('User removed')">Remove</button></td></tr>
            <tr><td>Anjali KC</td><td>anjali@press.np</td><td><span class="status-pill pill-draft">Reader</span></td><td><button class="btn-xs btn-reject" onclick="toast('User removed')">Remove</button></td></tr>
          </tbody>
        </table>
      </div>

      <!-- Categories -->
      <div class="tab-panel" id="tab-cats">
        <div style="margin-bottom:16px;font-weight:700;color:var(--navy);">Manage Categories</div>
        <ul id="cat-manage-list" style="list-style:none;display:flex;flex-direction:column;gap:10px;"></ul>
        <div style="display:flex;gap:10px;margin-top:16px;">
          <input type="text" id="new-cat-input" placeholder="New category name…" style="flex:1;"/>
          <button class="btn btn-solid" onclick="addCategory()">Add</button>
        </div>
      </div>
    </div>
  </div>
</div>

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
        <button class="bm-btn" onclick="bookmarkCurrent()">🔖 Save Article</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast-el"></div>

<script>
// ══════════════════════════
//  STATE
// ══════════════════════════
const API_KEY = 'pub_9fc7f4cbf9104e4bae5d48b1f0c35e3f'; // NewsData.io free key (demo)
let allArticles = [];
let currentCat  = null;
let currentArticle = null;
let bookmarks   = JSON.parse(localStorage.getItem('pc_bookmarks')||'[]');
let categories  = ['Politics','Technology','Sports','World','Business','Science'];
let votes       = {};

const NEWSAPI_URL = 'pub_79e53c037d4b4806b850c9451202acb4'; // NewsAPI.org free key (demo)

// Fallback mock data (always shown if API fails)
const MOCK = [
  {title:'Nepal Signs Historic Climate Agreement with 12 Nations',description:'The government reached a landmark environmental deal covering Himalayan glacier protection, renewable energy targets, and regional biodiversity.',category:'World',urlToImage:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=900&q=80',publishedAt:'2025-04-04T06:00:00Z',source:{name:'Kathmandu Post'},url:'#',upvotes:87,downvotes:4},
  {title:'New AI Chip Breaks Speed Records in Benchmark Tests',description:'A startup unveiled a processor that outperforms current GPUs by 3x on large language model inference tasks, drawing major investment interest.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1518770660439-4636190af475?w=900&q=80',publishedAt:'2025-04-03T14:30:00Z',source:{name:'TechCrunch'},url:'#',upvotes:120,downvotes:9},
  {title:'National Elections Set for October — Campaign Rules Announced',description:'The election commission finalized the schedule and issued strict campaign finance guidelines amid opposition demands for transparency.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=900&q=80',publishedAt:'2025-04-03T10:00:00Z',source:{name:'Republic World'},url:'#',upvotes:55,downvotes:20},
  {title:'National Football Team Qualifies for Asian Cup 2026',description:'A stunning 2–1 victory secured Nepal\'s historic first-ever qualification for the continental championship, sparking nationwide celebrations.',category:'Sports',urlToImage:'https://images.unsplash.com/photo-1551958219-acbc630e2f4d?w=900&q=80',publishedAt:'2025-04-02T18:45:00Z',source:{name:'Sports Nepal'}  ,url:'#',upvotes:245,downvotes:2},
  {title:'Central Bank Raises Repo Rate to Combat Inflation',description:'Monetary policy tightening aims to cool a 7.2% annual inflation rate while balancing growth targets amid global supply chain pressures.',category:'Business',urlToImage:'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=900&q=80',publishedAt:'2025-04-02T09:00:00Z',source:{name:'Financial Times NP'},url:'#',upvotes:33,downvotes:11},
  {title:'Researchers Discover New High-Altitude Orchid Species in Annapurna',description:'Botanists from Tribhuvan University identified a previously unknown orchid growing at 4,200m elevation, sparking biodiversity excitement.',category:'Science',urlToImage:'https://images.unsplash.com/photo-1487530811015-780f238e6a0a?w=900&q=80',publishedAt:'2025-04-01T12:00:00Z',source:{name:'Science Daily'},url:'#',upvotes:68,downvotes:1},
  {title:'Pokhara International Airport Sees Record Monthly Passengers',description:'Over 180,000 international arrivals were recorded in March 2025, marking a new high and validating the infrastructure investment.',category:'World',urlToImage:'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=900&q=80',publishedAt:'2025-03-31T08:00:00Z',source:{name:'Aviation Weekly'},url:'#',upvotes:41,downvotes:3},
  {title:'Budget 2025-26 Allocates 40% More to Education Sector',description:'The new fiscal budget prioritizes teacher training, digital infrastructure, and scholarship programs for students in remote districts.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=900&q=80',publishedAt:'2025-03-30T11:00:00Z',source:{name:'Kantipur Daily'},url:'#',upvotes:92,downvotes:7},
  {title:'Electric Vehicle Sales Surge 300% as Subsidies Kick In',description:'Incentive programs driving adoption of two and four wheelers on the road.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1593941707882-a56bbc8df21d?w=900&q=80',publishedAt:'2025-03-29T15:00:00Z',source:{name:'EV Times'},url:'#',upvotes:74,downvotes:5},
  {title:'Global Summit on Water Security Opens in Geneva',description:'Representatives from 78 countries converged to address fresh water scarcity projections for 2040 amid accelerating climate change.',category:'World',urlToImage:'https://images.unsplash.com/photo-1470076190571-4f4e0a10bfb4?w=900&q=80',publishedAt:'2025-03-28T07:30:00Z',source:{name:'UN News'},url:'#',upvotes:49,downvotes:6},
];

// ══════════════════════════
//  INIT
// ══════════════════════════
document.addEventListener('DOMContentLoaded', async ()=>{
  updateDate();
  setInterval(updateDate,60000);
  buildCatManage();
  buildApproval();
  useMockData();          // render immediately
  populateTicker(MOCK);
});

function updateDate(){
  const d=new Date();
  document.getElementById('live-date').textContent=d.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'});
}

function useMockData(){
  allArticles=MOCK.map((a,i)=>({...a,id:i,upvotes:a.upvotes||0,downvotes:a.downvotes||0}));
  renderAll();
}

// ══════════════════════════
//  RENDER
// ══════════════════════════
function renderAll(){
  let list=allArticles;
  if(currentCat) list=list.filter(a=>a.category===currentCat);
  const q=(document.getElementById('search-input').value||'').toLowerCase().trim();
  if(q) list=list.filter(a=>(a.title+a.description).toLowerCase().includes(q));

  renderHero(list[0]);
  renderCatStrip(list.slice(1,5));
  renderMore(list.slice(5));
  renderTrending(allArticles);
}

function img(url){
  return url||'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80';
}

function timeAgo(dateStr){
  const diff=Date.now()-new Date(dateStr).getTime();
  const mins=Math.floor(diff/60000);
  if(mins<60)return mins+'m ago';
  const hrs=Math.floor(mins/60);
  if(hrs<24)return hrs+'h ago';
  return Math.floor(hrs/24)+'d ago';
}

function renderHero(a){
  if(!a){document.getElementById('hero-section').innerHTML='<p style="color:var(--muted);padding:20px;">No articles found.</p>';return;}
  document.getElementById('hero-section').innerHTML=`
    <div class="hero-card" onclick="openArticle(${a.id})">
      <img src="${img(a.urlToImage)}" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80'"/>
      <div class="overlay"></div>
      <div class="content">
        <div class="cat">${a.category}</div>
        <h1>${a.title}</h1>
        <div class="meta-row">
          <span>📰 ${a.source?.name||'PressCore'}</span>
          <span>🕐 ${timeAgo(a.publishedAt)}</span>
          <div class="votes">
            <button class="vote-btn up" onclick="event.stopPropagation();quickVote(${a.id},'up',this)">👍 ${a.upvotes}</button>
            <button class="vote-btn dn" onclick="event.stopPropagation();quickVote(${a.id},'dn',this)">👎 ${a.downvotes}</button>
          </div>
        </div>
      </div>
    </div>`;
}

function renderCatStrip(list){
  document.getElementById('cat-strip').innerHTML=list.map(a=>`
    <div class="cat-card" onclick="openArticle(${a.id})">
      <img src="${img(a.urlToImage)}" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80'"/>
      <div class="body">
        <div class="label">${a.category}</div>
        <h3>${a.title}</h3>
        <div class="ago">${timeAgo(a.publishedAt)} · ${a.source?.name||'PressCore'}</div>
      </div>
    </div>`).join('');
}

function renderMore(list){
  document.getElementById('more-news').innerHTML=list.map(a=>`
    <div class="side-card" onclick="openArticle(${a.id})">
      <img src="${img(a.urlToImage)}" alt="" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&q=80'"/>
      <div class="info">
        <span class="cat-sm">${a.category}</span>
        <h3>${a.title}</h3>
        <div class="meta-sm">📰 ${a.source?.name||'PressCore'} · ${timeAgo(a.publishedAt)}</div>
      </div>
    </div>`).join('');
}

function renderTrending(list){
  const sorted=[...list].sort((a,b)=>(b.upvotes||0)-(a.upvotes||0)).slice(0,6);
  document.getElementById('trend-list').innerHTML=sorted.map((a,i)=>`
    <div class="trend-item ${i===0?'top':''}" onclick="openArticle(${a.id})">
      <div class="trend-num">${String(i+1).padStart(2,'0')}</div>
      <div>
        <div class="trend-title">${a.title}</div>
        <div class="trend-meta">👍 ${a.upvotes} · ${a.category}</div>
      </div>
    </div>`).join('');
}

function populateTicker(list){
  const inner=document.getElementById('ticker-inner');
  const items=list.slice(0,8).map(a=>`<span>${a.title}</span>`).join('');
  inner.innerHTML=items+items; // doubled for seamless loop
}

// ══════════════════════════
//  FILTERING
// ══════════════════════════
function filterCat(cat,el){
  currentCat=cat;
  document.querySelectorAll('nav a').forEach(a=>a.classList.remove('active'));
  if(el)el.classList.add('active');
  document.getElementById('active-cat-label').textContent=cat?`Showing: ${cat}`:'';
  renderAll();
}
function filterChip(cat,el){
  document.querySelectorAll('.cat-chip').forEach(c=>c.classList.remove('active'));
  el.classList.add('active');
  currentCat=cat==='all'?null:cat.charAt(0).toUpperCase()+cat.slice(1);
  renderAll();
}
function liveSearch(q){renderAll();}

// ══════════════════════════
//  VOTING
// ══════════════════════════
function quickVote(id,dir,btn){
  const a=allArticles.find(x=>x.id===id);
  if(!a)return;
  const key=id+'_'+dir;
  if(votes[key]){toast('Already voted!');return;}
  votes[key]=true;
  if(dir==='up')a.upvotes++;else a.downvotes++;
  btn.textContent=(dir==='up'?'👍 ':'👎 ')+a[dir==='up'?'upvotes':'downvotes'];
  toast(dir==='up'?'Upvoted! 👍':'Downvoted 👎');
}
function voteArticle(dir){
  if(!currentArticle)return;
  quickVote(currentArticle.id,dir,{textContent:''});
  document.getElementById('am-up').textContent=currentArticle.upvotes;
  document.getElementById('am-dn').textContent=currentArticle.downvotes;
  renderAll();
}

// ══════════════════════════
//  BOOKMARKS
// ══════════════════════════
function bookmarkCurrent(){
  if(!currentArticle)return;
  if(bookmarks.find(b=>b.id===currentArticle.id)){toast('Already saved!');return;}
  bookmarks.push({id:currentArticle.id,title:currentArticle.title});
  localStorage.setItem('pc_bookmarks',JSON.stringify(bookmarks));
  renderBookmarks();
  toast('Article saved! 🔖');
}
function renderBookmarks(){
  const el=document.getElementById('bm-list');
  if(!bookmarks.length){el.innerHTML='<div style="padding:16px 18px;font-size:.82rem;color:var(--muted);">No saved articles yet.</div>';return;}
  el.innerHTML=bookmarks.map((b,i)=>`
    <div class="bm-item">
      <div class="bm-title" onclick="openArticle(${b.id})">${b.title}</div>
      <button class="bm-rm" onclick="removeBookmark(${i})">✕</button>
    </div>`).join('');
}
function removeBookmark(i){
  bookmarks.splice(i,1);
  localStorage.setItem('pc_bookmarks',JSON.stringify(bookmarks));
  renderBookmarks();
  toast('Removed from saved.');
}

// ══════════════════════════
//  ARTICLE MODAL
// ══════════════════════════
function openArticle(id){
  const a=allArticles.find(x=>x.id===id);
  if(!a)return;
  currentArticle=a;
  document.getElementById('am-img').src=img(a.urlToImage);
  document.getElementById('am-category').textContent=a.category;
  document.getElementById('am-title').textContent=a.title;
  document.getElementById('am-cat').textContent=a.category+' · '+a.source?.name;
  document.getElementById('am-meta').innerHTML=`<span>📰 ${a.source?.name||'News Portal'}</span><span>🕐 ${timeAgo(a.publishedAt)}</span><span>👍 ${a.upvotes}</span><span>👎 ${a.downvotes}</span>`;
  document.getElementById('am-up').textContent=a.upvotes;
  document.getElementById('am-dn').textContent=a.downvotes;
  document.getElementById('am-body').innerHTML=`<p>${a.description||'Full article content would appear here. This portal uses a live News API to fetch real headlines.'}</p><p style="margin-top:14px;color:var(--muted);font-size:.85rem;">Continue reading on the source website for the full story.</p>`;
  openModal('article-modal');
}

// ══════════════════════════
//  MODALS
// ══════════════════════════
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');});
});

// ══════════════════════════
//  TABS
// ══════════════════════════
function switchTab(el,panelId){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById(panelId).classList.add('active');
}

// ══════════════════════════
//  ADMIN
// ══════════════════════════
function buildApproval(){
  const rows=[
    {title:'Budget 2025 Analysis',editor:'Rajan Thapa',cat:'Politics',status:'pending'},
    {title:'New EV Policy Draft',editor:'Anjali KC',cat:'Technology',status:'pending'},
    {title:'Cricket Series Preview',editor:'Bikash Rai',cat:'Sports',status:'draft'},
    {title:'Monsoon Forecast 2025',editor:'Puja Shrestha',cat:'Science',status:'pending'},
  ];
  document.getElementById('approval-tbody').innerHTML=rows.map((r,i)=>`
    <tr id="arow${i}">
      <td>${r.title}</td>
      <td>${r.editor}</td>
      <td>${r.cat}</td>
      <td><span class="status-pill pill-${r.status}">${r.status}</span></td>
      <td class="action-btns">
        <button class="btn-xs btn-approve" onclick="approveRow(${i})">Approve</button>
        <button class="btn-xs btn-reject" onclick="rejectRow(${i})">Reject</button>
      </td>
    </tr>`).join('');
}
function approveRow(i){
  const row=document.getElementById('arow'+i);
  if(row) row.cells[3].innerHTML='<span class="status-pill pill-approved">approved</span>';
  toast('Article Approved! ✅');
}
function rejectRow(i){
  const row=document.getElementById('arow'+i);
  if(row) row.cells[3].innerHTML='<span class="status-pill pill-rejected">rejected</span>';
  toast('Article Rejected ❌');
}

// ══════════════════════════
//  CATEGORIES
// ══════════════════════════
function buildCatManage(){
  const ul=document.getElementById('cat-manage-list');
  ul.innerHTML=categories.map((c,i)=>`
    <li style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
      <span style="font-weight:600;">${c}</span>
      <button class="btn-xs btn-reject" onclick="removeCat(${i})">Remove</button>
    </li>`).join('');
}
function addCategory(){
  const input=document.getElementById('new-cat-input');
  const val=input.value.trim();
  if(!val)return;
  categories.push(val);
  input.value='';
  buildCatManage();
  toast('Category added!');
}
function removeCat(i){
  categories.splice(i,1);
  buildCatManage();
  toast('Category removed.');
}

// ══════════════════════════
//  LOGIN
// ══════════════════════════
function fakeLogin(){
  closeModal('login-modal');
  toast('Logged in successfully! 👋');
}

// ══════════════════════════
//  TOAST
// ══════════════════════════
function toast(msg){
  const el=document.getElementById('toast-el');
  el.textContent=msg;
  el.classList.add('show');
  setTimeout(()=>el.classList.remove('show'),2800);
}

// init bookmarks on load
renderBookmarks();
</script>
</body>
</html>
