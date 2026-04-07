<?php
// Article detail page — article data is resolved client-side from shared MOCK store
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Article — EkataNews</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="news_detail.css"/>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
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
  <a href="home.php" onclick="return navFilter(null)">All <span class="live-badge">LIVE</span></a>
  <a href="home.php?cat=World" onclick="return navFilter('World')">World</a>
  <a href="home.php?cat=Politics" onclick="return navFilter('Politics')">Politics</a>
  <a href="home.php?cat=Technology" onclick="return navFilter('Technology')">Technology</a>
  <a href="home.php?cat=Sports" onclick="return navFilter('Sports')">Sports</a>
  <a href="home.php?cat=Business" onclick="return navFilter('Business')">Business</a>
  <a href="home.php?cat=Science" onclick="return navFilter('Science')">Science</a>
</nav>

<!-- MAIN -->
<div class="container">
  <div id="page-content">
    <!-- Content injected by JS -->
    <div class="not-found" id="loading-state">
      <p style="color:var(--muted);">Loading article…</p>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>© 2025 <span>EkataNews</span> — Powered by News API &nbsp;|&nbsp; All rights reserved</footer>

<!-- TOAST -->
<div class="toast" id="toast-el"></div>

<script>
// ══════════════════════════════════════
//  SHARED DATA  (same MOCK as home.php)
// ══════════════════════════════════════
const MOCK = [
  {title:'Nepal Signs Historic Climate Agreement with 12 Nations',description:'The government reached a landmark environmental deal covering Himalayan glacier protection, renewable energy targets, and regional biodiversity. The agreement, signed in a ceremony attended by ministers from across South Asia, outlines a joint action plan for the next decade. Experts have called it the most significant multilateral environmental commitment the nation has made, noting that enforcement mechanisms and funding structures are already in place. Civil society groups welcomed the development while urging continued public consultation on implementation.',category:'World',urlToImage:'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1200&q=80',publishedAt:'2025-04-04T06:00:00Z',source:{name:'Kathmandu Post'},url:'#',upvotes:87,downvotes:4},
  {title:'New AI Chip Breaks Speed Records in Benchmark Tests',description:'A startup unveiled a processor that outperforms current GPUs by 3x on large language model inference tasks, drawing major investment interest. The chip, built on a novel sparse-activation architecture, achieves unprecedented throughput while consuming significantly less power than competing solutions. Industry analysts predict it could reshape the economics of running large AI models in data centers. Several major cloud providers have already signed letters of intent to integrate the chip into their next-generation infrastructure.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&q=80',publishedAt:'2025-04-03T14:30:00Z',source:{name:'TechCrunch'},url:'#',upvotes:120,downvotes:9},
  {title:'National Elections Set for October — Campaign Rules Announced',description:'The election commission finalized the schedule and issued strict campaign finance guidelines amid opposition demands for transparency. Candidates must now disclose all donations above a set threshold within 48 hours. Independent monitors will be stationed at every counting center, and a dedicated digital portal will provide real-time updates on vote tabulation. The commission chair emphasized that these measures reflect lessons learned from previous cycles and represent the most rigorous oversight framework in the nation\'s electoral history.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=1200&q=80',publishedAt:'2025-04-03T10:00:00Z',source:{name:'Republic World'},url:'#',upvotes:55,downvotes:20},
  {title:'National Football Team Qualifies for Asian Cup 2026',description:'A stunning 2–1 victory secured Nepal\'s historic first-ever qualification for the continental championship, sparking nationwide celebrations. Thousands of supporters flooded the streets of Kathmandu after the final whistle, waving flags and setting off fireworks well past midnight. The head coach credited the team\'s grueling pre-tournament training camp in Germany for the tactical cohesion on display. The federation has pledged to increase investment in youth academies to sustain the momentum into the next qualifying cycle.',category:'Sports',urlToImage:'https://images.unsplash.com/photo-1551958219-acbc630e2f4d?w=1200&q=80',publishedAt:'2025-04-02T18:45:00Z',source:{name:'Sports Nepal'},url:'#',upvotes:245,downvotes:2},
  {title:'Central Bank Raises Repo Rate to Combat Inflation',description:'Monetary policy tightening aims to cool a 7.2% annual inflation rate while balancing growth targets amid global supply chain pressures. The Monetary Policy Committee voted 5–2 in favor of the 50-basis-point hike, signaling readiness to act further if price pressures persist. Economists noted that while the rate increase will raise borrowing costs for businesses and households, it sends a credible signal of commitment to the 4% inflation target. Banks are expected to pass through the full impact to lending rates within two to three weeks.',category:'Business',urlToImage:'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=1200&q=80',publishedAt:'2025-04-02T09:00:00Z',source:{name:'Financial Times NP'},url:'#',upvotes:33,downvotes:11},
  {title:'Researchers Discover New High-Altitude Orchid Species in Annapurna',description:'Botanists from Tribhuvan University identified a previously unknown orchid growing at 4,200m elevation, sparking biodiversity excitement. The species, provisionally named Dactylorhiza annapurnensis, features unusually large purple blooms adapted to the intense UV radiation at altitude. The discovery was made during a three-week expedition funded by the National Science Foundation. Researchers are now calling for expanded protected zones around the discovery site to prevent habitat disturbance from trekking traffic.',category:'Science',urlToImage:'https://images.unsplash.com/photo-1487530811015-780f238e6a0a?w=1200&q=80',publishedAt:'2025-04-01T12:00:00Z',source:{name:'Science Daily'},url:'#',upvotes:68,downvotes:1},
  {title:'Pokhara International Airport Sees Record Monthly Passengers',description:'Over 180,000 international arrivals were recorded in March 2025, marking a new high and validating the infrastructure investment. The figure surpasses the previous monthly record by 22%, driven by increased direct flights from Gulf hubs and new charter services from China. Airport management confirmed that a second terminal expansion has been fast-tracked to handle projected growth over the next five years. Tourism stakeholders welcomed the numbers, noting that hotel occupancy in the Lakeside district reached 94% for the month.',category:'World',urlToImage:'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1200&q=80',publishedAt:'2025-03-31T08:00:00Z',source:{name:'Aviation Weekly'},url:'#',upvotes:41,downvotes:3},
  {title:'Budget 2025-26 Allocates 40% More to Education Sector',description:'The new fiscal budget prioritizes teacher training, digital infrastructure, and scholarship programs for students in remote districts. A dedicated fund of NPR 12 billion will be channeled into upgrading school internet connectivity across all 77 districts by the end of the fiscal year. The finance minister stated that raising the quality of public education is the single highest-return investment the government can make. Opposition lawmakers praised the allocation while calling for independent audit mechanisms to prevent diversion of funds.',category:'Politics',urlToImage:'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200&q=80',publishedAt:'2025-03-30T11:00:00Z',source:{name:'Kantipur Daily'},url:'#',upvotes:92,downvotes:7},
  {title:'Electric Vehicle Sales Surge 300% as Subsidies Kick In',description:'Incentive programs driving adoption of two and four wheelers across the road network have exceeded all government projections. The subsidy scheme, introduced six months ago, covers up to 30% of the purchase price for certified electric vehicles. Charging infrastructure has expanded to over 400 public stations nationwide, addressing the range anxiety that previously deterred buyers. Industry bodies expect the trend to sustain through 2026 as several domestic manufacturers prepare to launch competitively priced models.',category:'Technology',urlToImage:'https://images.unsplash.com/photo-1593941707882-a56bbc8df21d?w=1200&q=80',publishedAt:'2025-03-29T15:00:00Z',source:{name:'EV Times'},url:'#',upvotes:74,downvotes:5},
  {title:'Global Summit on Water Security Opens in Geneva',description:'Representatives from 78 countries converged to address fresh water scarcity projections for 2040 amid accelerating climate change. The five-day summit opened with a keynote warning that current consumption patterns, if unchanged, will leave an estimated 3.5 billion people facing water stress within 15 years. Negotiators are working toward a binding framework on transboundary river management, desalination technology sharing, and emergency relief protocols. Several major donor nations announced combined pledges exceeding $8 billion toward water resilience infrastructure in developing regions.',category:'World',urlToImage:'https://images.unsplash.com/photo-1470076190571-4f4e0a10bfb4?w=1200&q=80',publishedAt:'2025-03-28T07:30:00Z',source:{name:'UN News'},url:'#',upvotes:49,downvotes:6},
];

// ══════════════════════════
//  STATE
// ══════════════════════════
let allArticles = MOCK.map((a, i) => ({...a, id: i, upvotes: a.upvotes||0, downvotes: a.downvotes||0}));
let bookmarks   = JSON.parse(localStorage.getItem('pc_bookmarks')||'[]');
let votes       = JSON.parse(localStorage.getItem('pc_votes')||'{}');
let currentArticle = null;

// ══════════════════════════
//  HELPERS
// ══════════════════════════
function img(url){
  return url || 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&q=80';
}

function timeAgo(dateStr){
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if(mins < 60) return mins + 'm ago';
  const hrs = Math.floor(mins / 60);
  if(hrs < 24) return hrs + 'h ago';
  return Math.floor(hrs / 24) + 'd ago';
}

function formatDate(dateStr){
  return new Date(dateStr).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });
}

function updateDate(){
  const el = document.getElementById('live-date');
  if(el) el.textContent = new Date().toLocaleDateString('en-US', {
    weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
  });
}

function isBookmarked(id){
  return bookmarks.some(b => b.id === id);
}

function toast(msg){
  const el = document.getElementById('toast-el');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2800);
}

// ══════════════════════════
//  RENDER ARTICLE
// ══════════════════════════
function renderArticle(a){
  document.title = a.title + ' — EkataNews';
  const saved = isBookmarked(a.id);
  const upVoted  = !!votes[a.id + '_up'];
  const dnVoted  = !!votes[a.id + '_dn'];

  const related = allArticles
    .filter(x => x.id !== a.id && x.category === a.category)
    .slice(0, 4);

  const trending = [...allArticles]
    .sort((x, y) => (y.upvotes||0) - (x.upvotes||0))
    .filter(x => x.id !== a.id)
    .slice(0, 5);

  document.getElementById('page-content').innerHTML = `
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="home.php">Home</a>
      <span class="sep">›</span>
      <a href="home.php?cat=${encodeURIComponent(a.category)}">${a.category}</a>
      <span class="sep">›</span>
      <span class="current">${a.title.length > 60 ? a.title.slice(0, 60) + '…' : a.title}</span>
    </nav>

    <div class="detail-grid">
      <!-- MAIN ARTICLE -->
      <main>
        <article class="article-card">
          <div class="article-hero">
            <img src="${img(a.urlToImage)}" alt="${a.title}"
                 onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&q=80'"/>
            <div class="hero-overlay"></div>
          </div>

          <div class="article-body">
            <span class="article-cat">${a.category}</span>
            <h1 class="article-title">${a.title}</h1>

            <div class="article-meta">
              <div class="meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                <strong>${a.source?.name || 'EkataNews'}</strong>
              </div>
              <div class="meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>${formatDate(a.publishedAt)}</span>
              </div>
              <div class="meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/></svg>
                <span id="upvote-count">${a.upvotes}</span> upvotes
              </div>
              <div class="meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10z"/></svg>
                <span id="downvote-count">${a.downvotes}</span> downvotes
              </div>
            </div>

            <div class="article-content" id="article-content">
              ${buildBodyParagraphs(a.description)}
            </div>

            <div class="action-bar">
              <a href="home.php" class="back-btn">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Back
              </a>
              <button class="vote-btn up ${upVoted ? 'active-up' : ''}" id="btn-up" onclick="voteArticle('up')">
                👍 <span id="lbl-up">${a.upvotes}</span> Upvote
              </button>
              <button class="vote-btn dn ${dnVoted ? 'active-dn' : ''}" id="btn-dn" onclick="voteArticle('dn')">
                👎 <span id="lbl-dn">${a.downvotes}</span> Downvote
              </button>
              <button class="bm-btn ${saved ? 'saved' : ''}" id="btn-bm" onclick="toggleBookmark()">
                🔖 ${saved ? 'Saved' : 'Save Article'}
              </button>
            </div>
          </div>
        </article>
      </main>

      <!-- SIDEBAR -->
      <aside class="sidebar">
        ${related.length ? `
        <div class="widget">
          <div class="widget-hdr">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            Related Stories
          </div>
          <div class="related-list">
            ${related.map(r => `
              <a class="related-item" href="news_detail.php?id=${r.id}">
                <img src="${img(r.urlToImage)}" alt="${r.title}"
                     onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&q=80'"/>
                <div class="rel-info">
                  <span class="rel-cat">${r.category}</span>
                  <div class="rel-title">${r.title}</div>
                  <div class="rel-meta">${timeAgo(r.publishedAt)} · ${r.source?.name || 'EkataNews'}</div>
                </div>
              </a>`).join('')}
          </div>
        </div>` : ''}

        <div class="widget">
          <div class="widget-hdr">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Trending Now
          </div>
          <div class="trend-list">
            ${trending.map((t, i) => `
              <a class="trend-item ${i === 0 ? 'top' : ''}" href="news_detail.php?id=${t.id}">
                <div class="trend-num">${String(i + 1).padStart(2, '0')}</div>
                <div>
                  <div class="trend-title">${t.title}</div>
                  <div class="trend-meta">👍 ${t.upvotes} · ${t.category}</div>
                </div>
              </a>`).join('')}
          </div>
        </div>
      </aside>
    </div>`;
}

function buildBodyParagraphs(text){
  if(!text) return '<p>Full article content is available on the source website.</p>';
  // Split on existing double-newlines or render as single block
  const paras = text.split(/\n{2,}/).filter(Boolean);
  return paras.map(p => `<p>${p}</p>`).join('');
}

// ══════════════════════════
//  NOT FOUND
// ══════════════════════════
function renderNotFound(){
  document.getElementById('page-content').innerHTML = `
    <div class="not-found">
      <h2>Article Not Found</h2>
      <p>The article you are looking for does not exist or has been removed.</p>
      <a href="home.php" class="btn btn-solid" style="text-decoration:none;display:inline-block;">← Back to Home</a>
    </div>`;
}

// ══════════════════════════
//  VOTING
// ══════════════════════════
function voteArticle(dir){
  if(!currentArticle) return;
  const a = currentArticle;
  const key = a.id + '_' + dir;
  const opposite = dir === 'up' ? 'dn' : 'up';
  const oppKey = a.id + '_' + opposite;

  if(votes[key]){ toast('Already voted!'); return; }

  // Remove opposite vote if exists
  if(votes[oppKey]){
    votes[oppKey] = false;
    if(opposite === 'up') a.upvotes = Math.max(0, a.upvotes - 1);
    else a.downvotes = Math.max(0, a.downvotes - 1);
    document.getElementById('btn-' + opposite).classList.remove('active-up', 'active-dn');
  }

  votes[key] = true;
  localStorage.setItem('pc_votes', JSON.stringify(votes));

  if(dir === 'up'){ a.upvotes++; } else { a.downvotes++; }

  document.getElementById('lbl-up').textContent = a.upvotes;
  document.getElementById('lbl-dn').textContent = a.downvotes;
  document.getElementById('upvote-count').textContent = a.upvotes;
  document.getElementById('downvote-count').textContent = a.downvotes;
  document.getElementById('btn-' + dir).classList.add(dir === 'up' ? 'active-up' : 'active-dn');

  toast(dir === 'up' ? 'Upvoted! 👍' : 'Downvoted 👎');
}

// ══════════════════════════
//  BOOKMARKS
// ══════════════════════════
function toggleBookmark(){
  if(!currentArticle) return;
  const a = currentArticle;
  const idx = bookmarks.findIndex(b => b.id === a.id);
  const btn = document.getElementById('btn-bm');

  if(idx !== -1){
    bookmarks.splice(idx, 1);
    btn.textContent = '🔖 Save Article';
    btn.classList.remove('saved');
    toast('Removed from saved.');
  } else {
    bookmarks.push({id: a.id, title: a.title});
    btn.textContent = '🔖 Saved';
    btn.classList.add('saved');
    toast('Article saved! 🔖');
  }
  localStorage.setItem('pc_bookmarks', JSON.stringify(bookmarks));
}

// ══════════════════════════
//  NAV FILTER (returns false to prevent default)
// ══════════════════════════
function navFilter(cat){
  const dest = cat ? 'home.php?cat=' + encodeURIComponent(cat) : 'home.php';
  window.location.href = dest;
  return false;
}

// ══════════════════════════
//  INIT
// ══════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  updateDate();
  setInterval(updateDate, 60000);

  const params = new URLSearchParams(window.location.search);
  const articleId = parseInt(params.get('id'), 10);

  if(isNaN(articleId) || articleId < 0 || articleId >= allArticles.length){
    renderNotFound();
    return;
  }

  currentArticle = allArticles[articleId];
  renderArticle(currentArticle);
});
</script>
</body>
</html>
