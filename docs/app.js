(function () {
  const { esc, money, now } = RS;
  const app = document.getElementById('app');
  document.getElementById('yr').textContent = new Date().getFullYear();

  function toast(msg) { const t = document.getElementById('toast'); t.textContent = msg; t.classList.add('show'); clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('show'), 2200); }
  function updateCount() { document.getElementById('cartCount').textContent = RS.cart().reduce((a, l) => a + l.qty, 0); }
  function priceHtml(p) { return p.sale && p.sale < p.price ? `<div class="price"><span class="now red">${money(p.sale)}</span><s>${money(p.price)}</s></div>` : `<div class="price"><span class="now">${money(p.price)}</span></div>`; }
  const wish = () => RS.ls.get('rs_wish', []);
  function card(p) {
    const off = p.sale && p.sale < p.price ? Math.round((1 - p.sale / p.price) * 100) : 0;
    const cat = (RS.cat(p.cat) || {}).name || '';
    const liked = wish().includes(p.id);
    const cols = p.colors || [];
    return `<a class="card" href="#/p/${esc(p.slug)}"><div class="ph"><img src="${esc(p.img)}" alt="${esc(p.name)}" loading="lazy">
      ${off ? `<span class="tag">-${off}%</span>` : p.isNew ? '<span class="tag new">New</span>' : ''}
      <button type="button" class="heart ${liked ? 'on' : ''}" data-wish="${p.id}" aria-label="Wishlist">${liked ? '♥' : '♡'}</button>
      <button type="button" class="qadd" data-add="${p.id}">+ Add to cart</button></div>
      <div class="ci"><small class="ccat">${esc(cat)}</small><h3>${esc(p.name)}</h3>
      <div class="cprice">${off ? `<b class="red">${money(p.sale)}</b><s>${money(p.price)}</s><em>Save ${money(p.price - p.sale)}</em>` : `<b>${money(p.price)}</b>`}</div>
      <div class="dots">${cols.slice(0, 4).map(c => `<i style="background:${esc(c[1])}" title="${esc(c[0])}"></i>`).join('')}${cols.length ? `<span>${cols.length} colour${cols.length > 1 ? 's' : ''}</span>` : ''}</div></div></a>`;
  }
  document.addEventListener('click', e => {
    const h = e.target.closest('[data-wish]'), q = e.target.closest('[data-add]');
    if (!h && !q) return;
    e.preventDefault(); e.stopPropagation();
    if (h) { const id = +h.dataset.wish; let w = wish(); const on = !w.includes(id); w = on ? [...w, id] : w.filter(x => x !== id); RS.ls.set('rs_wish', w); h.classList.toggle('on', on); h.textContent = on ? '♥' : '♡'; toast(on ? 'Saved to wishlist' : 'Removed from wishlist'); }
    if (q) { const p = RS.products().find(x => x.id === +q.dataset.add); if (!p) return; const color = ((p.colors || [])[0] || [''])[0], size = (p.sizes || []).find(s => !(p.soldOut || []).includes(s)) || '';
      if (p.sizes && p.sizes.length && !size) { toast('Out of stock'); return; }
      const cart = RS.cart(); const key = p.id + '|' + color + '|' + size; const l = cart.find(x => x.key === key); if (l) l.qty++; else cart.push({ key, id: p.id, color, size, qty: 1 });
      RS.saveCart(cart); updateCount(); toast('Added: ' + p.name + (size ? ' (' + size + ')' : '')); }
  });
  const visible = () => RS.products().filter(p => p.status !== 'draft');

  function landing() {
    const ps = visible();
    if (!ps.length) return collection();
    const pickCat = slug => ps.find(x => x.cat === slug && x.landing) || ps.find(x => x.cat === slug && x.featured) || ps.find(x => x.cat === slug);
    let tiles = ps.filter(x => x.landing);
    ['shirts', 'pants', 't-shirts'].forEach(c => { const x = pickCat(c); if (x && !tiles.includes(x)) tiles.push(x); });
    const jacket = ps.find(x => /overshirt|jacket/i.test(x.slug)); if (jacket && !tiles.includes(jacket)) tiles.push(jacket);
    ps.forEach(x => { if (tiles.length < 4 && !tiles.includes(x)) tiles.push(x); });
    tiles = tiles.slice(0, 4);
    const rest = ps.filter(x => !tiles.includes(x)); const crops = (rest.length >= 4 ? rest : ps).slice(0, 4).map(x => [x, 'center 25%']);
    const reviews = ps.flatMap(p => (p.reviews || []).filter(r => r[1]).map(r => [r[0], r[1], p.name]));
    const end = RS.SHOP.offerEnd ? new Date(RS.SHOP.offerEnd) : null;
    const showTimer = end && end > new Date();
    const charts = RS.CATS.filter(c => SIZE_CHART[c.slug] && ps.some(p => p.cat === c.slug));
    const quality = ['৬০ বার ধোয়ার পরও প্রিন্ট ও রং অক্ষত থাকে', 'প্রিমিয়াম কোয়ালিটি ফেব্রিক, আরামদায়ক', 'মজবুত সেলাই ও টেকসই ফিনিশিং', 'স্টাইলিশ ও ট্রেন্ডি ফিটিং', 'হাতে পেয়ে টাকা দিন, ৭ দিনে সাইজ বদল'];
    const pick = {}; // key "id|size" -> qty
    app.innerHTML = `<div class="kl">
    <section class="kl-hero"><i class="pl a">+</i><i class="pl b">+</i><i class="pl c">+</i><i class="pl d">+</i>
      <div class="kl-welcome">WELCOME<br>TO</div><img class="kl-mk" src="img/logo-mark.png" alt="">
      <h1 class="kl-brand"><span style="--i:0">R</span><span style="--i:1">A</span><b style="--i:2">N</b><span style="--i:3">A</span><span style="--i:4">S</span><span style="--i:5">H</span><b style="--i:6">O</b><span style="--i:7">P</span></h1>
      <div class="kl-sub"><span>CREATIVE LOOK</span><span>NEW SEASON '27</span></div>
      <p class="kl-hint">জুম করতে ছবিতে ক্লিক করুন</p>
      <div class="kl-tiles">${tiles.map((p, i) => `<figure class="t${i}"><img src="${esc(p.img)}" alt="${esc(p.name)} - Rana Shop" ${i > 1 ? 'loading="lazy"' : 'fetchpriority="high"'} decoding="async" width="300" height="400" data-zoom="${esc(p.img)}"><figcaption>${/overshirt|jacket/i.test(p.slug) ? 'JACKET' : esc((RS.cat(p.cat) || {}).name || '').toUpperCase()}</figcaption></figure>`).join('')}<div class="kl-rings"></div></div>
      <a class="kl-btn big" href="#order" data-go>ORDER NOW</a>
    </section>
    <section class="kl-sec kl-q"><h2>গুণমান</h2><ul>${quality.map(q => `<li>${q}</li>`).join('')}</ul><a class="kl-btn sm" href="#order" data-go>অর্ডার করুন</a></section>
    <section class="kl-sec"><div class="kl-det">${crops.map(([p, pos]) => `<div data-zoom="${esc(p.img)}" style="background-image:url('${esc(p.img)}');background-position:${pos};background-size:cover"><span>${esc(p.name)}</span></div>`).join('')}</div><p class="kl-hint sm">জুম করে দেখতে ছবিতে ক্লিক করুন</p></section>
    ${reviews.length ? `<section class="kl-sec"><h2>কাস্টমার রিভিউ</h2><div class="kl-revs">${reviews.map(r => `<div><div class="who"><span>${esc((r[0] || 'C')[0])}</span><b>${esc(r[0] || 'Customer')}</b><small>${esc(r[2])}</small></div><p>“${esc(r[1])}”</p></div>`).join('')}</div></section>` : ''}
    ${showTimer ? `<section class="kl-sec"><h2>অফারটি শেষ হতে বাকি</h2><div class="kl-timer"><div><b id="tD">0</b>দিন</div><div><b id="tH">0</b>ঘন্টা</div><div><b id="tM">0</b>মিনিট</div><div><b id="tS">0</b>সেকেন্ড</div></div></section>` : ''}
    ${charts.length ? `<section class="kl-sec kl-chart"><h2 class="c">সাইজ চার্ট</h2><p class="c muted">আপনার সঠিক সাইজ নির্বাচন করুন · মাপ ইঞ্চিতে দেওয়া হয়েছে</p>
      <div class="kl-tabs">${charts.map((c, i) => `<button type="button" class="${i ? '' : 'on'}" data-tab="${c.slug}">${c.name}</button>`).join('')}</div>
      ${charts.map((c, i) => `<table class="kl-tbl" data-t="${c.slug}" ${i ? 'hidden' : ''}>${SIZE_CHART[c.slug].map((r, k) => `<tr>${r.map(v => k ? `<td>${v}</td>` : `<th>${v}</th>`).join('')}</tr>`).join('')}</table>`).join('')}</section>` : ''}
    <section class="kl-sec" id="order"><h2>অর্ডার করতে নিচের ফর্মটি পূরণ করুন, এবং সাইজ সিলেক্ট করুন</h2><p class="kl-how">প্রোডাক্টের ছবিতে বা সাইজে ক্লিক করলেই অর্ডারে যোগ হবে। একসাথে একাধিক প্রোডাক্টও নিতে পারবেন।</p>
      <form class="kl-form" id="klForm" novalidate>
        <div class="kl-prods">${ps.map(p => { const so = p.soldOut || []; return `<div class="kl-pc" data-id="${p.id}"><div class="im"><img src="${esc(p.img)}" alt="" loading="lazy">${p.sale && p.sale < p.price ? `<em>-${Math.round((1 - p.sale / p.price) * 100)}%</em>` : ''}</div>
          <span class="ok">✓ সিলেক্ট হয়েছে</span><b>${esc(p.name)}</b><div class="pr">${p.sale && p.sale < p.price ? `<s>${money(p.price)}</s>` : ''}<span>${money(now(p))}</span></div><small>সাইজ নির্বাচন করুন</small>
          <div class="sz">${(p.sizes && p.sizes.length ? p.sizes : ['Free']).map(s => so.includes(s) ? `<button type="button" disabled>${esc(s)}</button>` : `<button type="button" data-s="${esc(s)}">${esc(s)}</button>`).join('')}</div></div>`; }).join('')}</div>
        <div class="kl-bill">
          <div class="kl-in"><label>আপনার নাম <i>*</i><input name="name" placeholder="এখানে নাম লিখুন"></label>
            <label>সম্পূর্ণ ঠিকানা <i>*</i><input name="address" placeholder="বাসা, রোড, এলাকা, জেলা"></label>
            <label>মোবাইল নাম্বার <i>*</i><input name="phone" inputmode="tel" placeholder="01XXXXXXXXX"></label>
            <label>নোট (ঐচ্ছিক)<input name="note" placeholder="কিছু বলার থাকলে লিখুন"></label></div>
          <div class="kl-sum"><h3>YOUR ORDER</h3><div id="klLines"></div>
            <div class="row"><span>Subtotal</span><span id="klSub">৳0</span></div>
            <div class="row ship"><span>Shipment</span><span><label><input type="radio" name="zone" value="inside" checked> ঢাকার ভিতরে: ৳70</label><label><input type="radio" name="zone" value="outside"> ঢাকার বাইরে: ৳130</label></span></div>
            <div class="row tot"><span>Total</span><b id="klTot">৳0</b></div>
            <div class="kl-cod">পণ্য হাতে পেয়ে টাকা দিবেন (Cash on Delivery)</div>
            <p class="kl-err" id="klErr"></p>
            <button class="kl-btn block">অর্ডার কনফার্ম করুন</button></div>
        </div>
      </form></section>
    </div>
    <div class="kl-lb" id="klLb" hidden><img alt=""><button type="button" aria-label="Close">×</button></div>`;
    // reveal sections and product cards as they scroll into view (one time)
    const rvEls = app.querySelectorAll('.kl-sec, .kl-pc, .kl-det div, .kl-timer div');
    if ('IntersectionObserver' in window) { const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('seen'); io.unobserve(e.target); } }), { rootMargin: '0px 0px -8% 0px' });
      rvEls.forEach(el => { el.classList.add('rv-in'); io.observe(el); }); }
    // zoom lightbox
    const lb = document.getElementById('klLb');
    app.querySelectorAll('[data-zoom]').forEach(el => el.onclick = () => { lb.querySelector('img').src = el.dataset.zoom; lb.hidden = false; });
    lb.onclick = () => { lb.hidden = true; };
    app.querySelectorAll('[data-go]').forEach(a => a.onclick = e => { e.preventDefault(); document.getElementById('order').scrollIntoView({ behavior: 'smooth' }); });
    app.querySelectorAll('.kl-tabs button').forEach(b => b.onclick = () => { app.querySelectorAll('.kl-tabs button').forEach(x => x.classList.toggle('on', x === b)); app.querySelectorAll('.kl-tbl').forEach(t => t.hidden = t.dataset.t !== b.dataset.tab); });
    if (showTimer) { const tick = () => { let s = Math.max(0, Math.floor((end - new Date()) / 1000)); const el = id => document.getElementById(id); if (!el('tS')) return clearInterval(tm);
      el('tD').textContent = Math.floor(s / 86400); s %= 86400; el('tH').textContent = String(Math.floor(s / 3600)).padStart(2, '0'); s %= 3600; el('tM').textContent = String(Math.floor(s / 60)).padStart(2, '0'); el('tS').textContent = String(s % 60).padStart(2, '0'); };
      const tm = setInterval(tick, 1000); tick(); }
    const form = document.getElementById('klForm');
    const zone = () => form.querySelector('input[name=zone]:checked').value;
    const draw = () => {
      const ls = Object.entries(pick).map(([k, q]) => { const [id, size] = k.split('|'); const p = ps.find(x => x.id === +id); return { k, p, size, q }; }).filter(l => l.p);
      const sub = ls.reduce((a, l) => a + now(l.p) * l.q, 0); const ship = !ls.length ? 0 : zone() === 'inside' ? (sub >= 3000 ? 0 : 70) : 130;
      document.getElementById('klLines').innerHTML = ls.length ? ls.map(l => `<div class="ln"><img src="${esc(l.p.img)}" alt=""><span>${esc(l.p.name)}${l.size !== 'Free' ? ' - ' + esc(l.size) : ''}</span><div class="q"><button type="button" data-k="${l.k}" data-d="-1">−</button>${l.q}<button type="button" data-k="${l.k}" data-d="1">+</button></div><b>${money(now(l.p) * l.q)}</b></div>`).join('') : '<p class="muted">উপরের প্রোডাক্ট থেকে সাইজ সিলেক্ট করুন</p>';
      document.getElementById('klSub').textContent = money(sub); document.getElementById('klTot').textContent = money(sub + ship);
      app.querySelectorAll('.kl-pc').forEach(c => { let any = false; c.querySelectorAll('.sz button[data-s]').forEach(b => { const on = !!pick[c.dataset.id + '|' + b.dataset.s]; b.classList.toggle('on', on); any = any || on; }); c.classList.toggle('on', any); });
      app.querySelectorAll('#klLines [data-k]').forEach(b => b.onclick = () => { const k = b.dataset.k; pick[k] = (pick[k] || 0) + +b.dataset.d; if (pick[k] <= 0) delete pick[k]; draw(); });
      return { ls, sub, ship, total: sub + ship };
    };
    // tapping the photo or name picks the first available size (tap again to remove)
    app.querySelectorAll('.kl-pc').forEach(c => c.onclick = e => { if (e.target.closest('.sz')) return;
      const keys = Object.keys(pick).filter(k => k.startsWith(c.dataset.id + '|'));
      if (keys.length) keys.forEach(k => delete pick[k]); else { const b = c.querySelector('.sz button[data-s]'); if (b) pick[c.dataset.id + '|' + b.dataset.s] = 1; else toast('এই প্রোডাক্টের স্টক শেষ'); }
      draw(); if (!keys.length) toast('অর্ডারে যোগ হয়েছে, চাইলে সাইজ বদলান'); });
    app.querySelectorAll('.kl-pc .sz button[data-s]').forEach(b => b.onclick = () => { const k = b.closest('.kl-pc').dataset.id + '|' + b.dataset.s; if (pick[k]) delete pick[k]; else pick[k] = 1; draw(); });
    form.querySelectorAll('input[name=zone]').forEach(r => r.onchange = draw);
    draw();
    form.onsubmit = e => {
      e.preventDefault(); const f = new FormData(form); const v = k => String(f.get(k) || '').trim(); const t = draw(); const err = document.getElementById('klErr');
      const bad = !t.ls.length ? ['', 'অন্তত একটা প্রোডাক্টের সাইজ সিলেক্ট করুন'] : !v('name') ? ['name', 'আপনার নাম লিখুন'] : !v('address') ? ['address', 'সম্পূর্ণ ঠিকানা লিখুন'] : !/^(\+?88)?01[3-9]\d{8}$/.test(v('phone').replace(/[\s-]/g, '')) ? ['phone', 'সঠিক মোবাইল নম্বর লিখুন (01XXXXXXXXX)'] : null;
      if (bad) { err.textContent = bad[1]; if (bad[0]) form.querySelector(`[name=${bad[0]}]`).focus(); else app.querySelector('.kl-prods').scrollIntoView({ behavior: 'smooth' }); return; }
      err.textContent = ''; const orders = RS.orders(); const no = 'RS' + (1001 + orders.length);
      orders.unshift({ no, date: new Date().toISOString(), name: v('name'), email: '', phone: v('phone'), address: v('address'), zone: zone(), note: v('note'), pay: 'Cash on Delivery', status: 'Pending',
        items: t.ls.map(l => ({ name: l.p.name, img: l.p.img, color: ((l.p.colors || [])[0] || [''])[0], size: l.size === 'Free' ? '' : l.size, qty: l.q, price: now(l.p) })), sub: t.sub, ship: t.ship, total: t.total });
      RS.saveOrders(orders); sendOrderMail(orders[0]); location.hash = '#/done/' + no;
    };
  }
  function collection() {
    const ps = visible();
    const by = s => ps.find(p => p.slug === s);
    const pick = (...s) => s.map(by).filter(Boolean);
    const must = ps.filter(p => p.featured).slice(0, 6);
    const tile = (p, lbl) => p ? `<a class="htile" href="#/p/${esc(p.slug)}"><img src="${esc(p.img)}" alt="${esc(p.name)}" loading="lazy"><span>${esc(lbl || p.name)}<em>${RS.money(p.sale && p.sale < p.price ? p.sale : p.price)}</em></span></a>` : '';
    const heroImgs = pick('zip-front-denim-shirt', 'embroidered-cuff-panjabi', 'tie-dye-overshirt').map(p => p.img);
    const denim = pick('baggy-black-wash-jeans', 'light-blue-cargo-jeans', 'washed-graphic-tee');
    const black = pick('black-zip-polo', 'charcoal-wide-leg-trouser');
    app.innerHTML = `
    <section class="h-hero">${heroImgs.map(s => `<div style="background-image:url('${s}')"></div>`).join('')}
      <div class="h-hero-txt"><small>Rana Shop</small><h1>NEW SEASON '27</h1><p>Clean cuts, rich fabrics, made for everyday Dhaka.</p><div><a class="btn light" href="#/shop">Shop now</a><a class="btn outline" href="#/c/shirts">Shirts</a></div></div></section>
    <div class="h-strip"><span>Cash on Delivery all over Bangladesh</span><span>Free delivery inside Dhaka over ৳3,000</span><span>7-day easy exchange</span></div>
    <section class="h-must"><h2 class="h-title">MUST HAVES</h2><div class="h-row">${must.map(p => tile(p)).join('')}</div></section>
    ${denim.length >= 2 ? `<section class="h-banner dark"><div class="hb-imgs">${denim.map(p => `<a href="#/p/${esc(p.slug)}" style="background-image:url('${p.img}')"></a>`).join('')}</div>
      <div class="hb-txt right"><h2>DENIM<br><small>DESTINATION</small></h2><a class="btn light" href="#/c/pants">Shop pants</a></div></section>` : ''}
    <section class="h-cats">${RS.CATS.map(c => `<a href="#/c/${c.slug}" style="background-image:url('${c.img}')"><span>${c.name.toUpperCase()}</span></a>`).join('')}</section>
    ${black.length ? `<section class="h-banner black"><div class="hb-txt"><div class="mono"><img src="img/logo-mark.png" alt=""><span>RANA <b>|</b> BLACK</span></div><p>Redefining everyday luxury.</p><a class="btn light" href="#/c/t-shirts">Explore</a></div>
      <div class="hb-imgs two">${black.map(p => `<a href="#/p/${esc(p.slug)}" style="background-image:url('${p.img}')"></a>`).join('')}</div></section>` : ''}
    <section class="h-new"><div class="wrap"><div class="sec-head"><h2>New arrivals</h2><a href="#/shop">Shop all</a></div><div class="grid">${ps.slice(0, 8).map(card).join('')}</div></div></section>
    <section class="h-feats"><div class="wrap">
      <div><svg viewBox="0 0 24 24"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/></svg><b>FREE SHIPPING</b><small>Inside Dhaka on orders over ৳3,000</small></div>
      <div><svg viewBox="0 0 24 24"><path d="M4 13a8 8 0 0 1 16 0v4a2 2 0 0 1-2 2h-1v-6h3M4 13v4a2 2 0 0 0 2 2h1v-6H4"/></svg><b>SUPPORT 24/7</b><small>Message us on WhatsApp any time</small></div>
      <div><svg viewBox="0 0 24 24"><path d="M4 9h13l-3-3M20 15H7l3 3"/></svg><b>EASY EXCHANGE</b><small>Change size within 7 days</small></div>
      <div><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="1"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><b>100% SECURE PAYMENT</b><small>COD, bKash, Nagad and cards</small></div></div></section>
    <section class="h-express"><div><h2>Same-Day Delivery in Dhaka</h2><p>Order before 2 PM and get it today inside Dhaka city.</p><a class="btn outline" href="#/shop">Shop now</a></div></section>
    <section class="h-news"><h3>Subscribe to our newsletter</h3><p>New arrivals and offers, straight to your inbox.</p>
      <form id="newsF"><input type="email" required placeholder="Your email address"><button class="btn">Subscribe</button></form>
      <div class="social"><a href="#" aria-label="Facebook">f</a><a href="#" aria-label="Instagram">◎</a><a href="#" aria-label="TikTok">♪</a><a href="#" aria-label="YouTube">▶</a></div></section>`;
    document.getElementById('newsF').onsubmit = e => { e.preventDefault(); const l = RS.ls.get('rs_news', []); l.push(e.target[0].value); RS.ls.set('rs_news', l); e.target.reset(); toast('Thanks for subscribing!'); };
  }
  function listing(slug) {
    const c = slug ? RS.cat(slug) : null;
    const ps = visible().filter(p => !slug || p.cat === slug);
    app.innerHTML = `<div class="page-head"><div class="wrap"><h1>${c ? c.name : 'All products'}</h1><p>${ps.length} products</p></div></div>
      <div class="wrap"><div class="filters"><a href="#/shop" class="${!slug ? 'on' : ''}">All</a>${RS.CATS.map(x => `<a href="#/c/${x.slug}" class="${x.slug === slug ? 'on' : ''}">${x.name}</a>`).join('')}</div>
      ${ps.length ? `<div class="grid" style="margin-bottom:80px">${ps.map(card).join('')}</div>` : '<p class="empty">No products here yet.</p>'}</div>`;
  }
  const SIZE_CHART = {
    shirts: [['Size', 'Chest (in)', 'Length (in)', 'Sleeve (in)'], ['S', '38', '28', '24'], ['M', '40', '29', '24.5'], ['L', '42', '30', '25'], ['XL', '44', '31', '25.5'], ['XXL', '46', '32', '26']],
    't-shirts': [['Size', 'Chest (in)', 'Length (in)'], ['S', '40', '27'], ['M', '42', '28'], ['L', '44', '29'], ['XL', '46', '30'], ['XXL', '48', '31']],
    pants: [['Size', 'Waist (in)', 'Hip (in)', 'Length (in)'], ['28', '28', '38', '40'], ['30', '30', '40', '40.5'], ['32', '32', '42', '41'], ['34', '34', '44', '41.5'], ['36', '36', '46', '42']],
    shoes: [['Size (EU)', 'UK', 'Foot length (cm)'], ['40', '6', '25.5'], ['41', '7', '26'], ['42', '8', '26.5'], ['43', '9', '27.5'], ['44', '10', '28']],
  };
  const INFO = {
    shirts: { feats: ['প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক', 'আরামদায়ক ও বাতাস চলাচল করে', 'রং সহজে ওঠে না', 'মজবুত সেলাই', 'রেগুলার ফিট', 'অফিস ও ক্যাজুয়াল দুটোতেই মানানসই'], fabric: [['Fabric', '100% Cotton'], ['GSM', '140-160'], ['Fabric type', 'Woven'], ['Fit type', 'Regular fit']] },
    pants: { feats: ['প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক', 'টেকসই ও দীর্ঘস্থায়ী', 'রং সহজে ওঠে না', 'মজবুত সেলাই', 'আরামদায়ক ফিট', 'সব ঋতুতে পরার উপযোগী'], fabric: [['Fabric', '98% Cotton, 2% Spandex'], ['Weight', '12 oz'], ['Fabric type', 'Denim / Twill'], ['Fit type', 'Relaxed fit']] },
    't-shirts': { feats: ['প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক', 'নরম, আরামদায়ক ও বাতাস চলাচল করে', '৬০ ওয়াশেও প্রিন্ট নষ্ট হয় না', 'মজবুত সেলাই', 'ওভারসাইজ / রেগুলার ফিট', 'স্টাইলিশ ও ট্রেন্ডি লুক'], fabric: [['Fabric', '100% Cotton'], ['GSM', '180-220'], ['Fabric type', 'Single jersey'], ['Fit type', 'Oversized fit']] },
    shoes: { feats: ['প্রিমিয়াম লেদার', 'নরম ও আরামদায়ক সোল', 'হালকা ওজন', 'মজবুত সেলাই', 'পিছলে যায় না', 'সব পোশাকের সাথে মানানসই'], fabric: [['Upper', 'PU Leather'], ['Sole', 'Rubber'], ['Lining', 'Soft mesh'], ['Closure', 'Lace-up']] },
  };
  const ICO = {
    q: '<svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/></svg>',
    ok: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/></svg>',
    van: '<svg viewBox="0 0 24 24"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/></svg>',
    bike: '<svg viewBox="0 0 24 24"><circle cx="6" cy="16" r="3"/><circle cx="18" cy="16" r="3"/><path d="M6 16l4-7h5l3 7M10 9l-1-3H7M15 9l1-3h2"/></svg>',
    cash: '<svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="11" rx="1.5"/><circle cx="12" cy="12.5" r="2.5"/></svg>',
    ret: '<svg viewBox="0 0 24 24"><path d="M4 9h13l-3-3M20 15H7l3 3"/></svg>',
    lock: '<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="1.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
    user: '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
    phone: '<svg viewBox="0 0 24 24"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/></svg>',
    mail: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3.5 6l8.5 7 8.5-7"/></svg>',
    pin: '<svg viewBox="0 0 24 24"><path d="M12 21s7-6.3 7-12a7 7 0 0 0-14 0c0 5.7 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>',
    tag: '<svg viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8" r="1.4"/></svg>',
  };
  function product(slug, isHome) {
    const p = visible().find(x => x.slug === slug);
    if (!p) { app.innerHTML = '<div class="wrap empty"><h1>Product not found</h1><p><a class="btn" href="#/shop">Back to shop</a></p></div>'; return; }
    const c = RS.cat(p.cat);
    const info = INFO[p.cat] || INFO.shirts;
    const feats = (p.features && p.features.length) ? p.features : info.feats;
    const fabric = (p.fabric && p.fabric.length) ? p.fabric : info.fabric;
    const imgs = [p.img, ...(p.images || [])].filter(Boolean);
    const so = p.soldOut || []; let size = (p.sizes || []).find(s => !so.includes(s)) || '', color = ((p.colors || [])[0] || [''])[0], cur = 0, zone = 'inside';
    let rv = RS.ls.get('rs_recent', []).filter(id => id !== p.id); rv.unshift(p.id); RS.ls.set('rs_recent', rv.slice(0, 10));
    const related = visible().filter(x => x.cat === p.cat && x.id !== p.id).slice(0, 4);
    const chart = SIZE_CHART[p.cat];
    const off = p.sale && p.sale < p.price ? Math.round((1 - p.sale / p.price) * 100) : 0;
    const price = now(p);
    const noStock = p.sizes && p.sizes.length && !size;
    // close-up detail shots: real extra photos first, then zoomed crops of the main photo
    const crops = ['50% 30%', '30% 70%', '70% 55%', '50% 90%'];
    const details = imgs.length >= 4 ? imgs.slice(0, 4).map(s => [s, 'cover', 'center']) : [...imgs.slice(1).map(s => [s, 'cover', 'center']), ...crops.map(pos => [p.img, '230%', pos])].slice(0, 4);
    const reviews = (p.reviews || []).filter(r => r[1]);
    const others = isHome ? visible().filter(x => x.id !== p.id) : [];
    app.innerHTML = `${isHome ? '' : `<div class="crumbbar"><div class="wrap crumbs"><a href="#/">Home</a> › <a href="#/c/${p.cat}">${c ? c.name : ''}</a> › <span>${esc(p.name)}</span></div></div>`}
    <div class="wrap lp ${isHome ? 'lp-home' : ''}">
      <div class="lp-l">
        <div class="lp-gal ${imgs.length > 1 ? 'has-th' : ''}">${p.isNew || isHome ? '<span class="lp-badge">New arrival</span>' : ''}<div class="main-img zoom" id="zoomBox"><img id="mainImg" src="${esc(imgs[0])}" alt="${esc(p.name)}">
          ${imgs.length > 1 ? '<button type="button" class="arw l" data-d="-1">‹</button><button type="button" class="arw r" data-d="1">›</button>' : ''}${off ? `<span class="lp-off">-${off}%</span>` : ''}</div>
          ${imgs.length > 1 ? `<div class="lp-thumbs">${imgs.map((s, i) => `<button type="button" class="${i ? '' : 'on'}" data-i="${i}"><img src="${esc(s)}" alt=""></button>`).join('')}</div>` : ''}</div>
        <div class="lp-trust"><div>${ICO.q}<p><b>Premium Quality</b>১০০% বাছাই করা কোয়ালিটি</p></div><div>${ICO.ok}<p><b>Soft & Comfortable</b>সারাদিন আরাম</p></div><div>${ICO.tag}<p><b>Perfect Stitching</b>টেকসই ও মজবুত</p></div></div>
        <div class="lp-block"><h3 class="lp-h">Description <small>বিবরণ</small></h3><p class="lp-desc">${esc(p.desc)}</p></div>
        <div class="lp-block lp-two">
          <div><h3 class="lp-h">Key features <small>বৈশিষ্ট্য</small></h3><ul class="lp-feats">${feats.map(f => `<li>${ICO.ok}${esc(f)}</li>`).join('')}</ul></div>
          <div><h3 class="lp-h">${p.cat === 'shoes' ? 'Material' : 'Fabric'} details</h3><ul class="lp-fab">${fabric.map(r => `<li>${ICO.tag}${r[0] ? `<span>${esc(r[0])}:</span> ` : ''}<b>${esc(r[1])}</b></li>`).join('')}</ul></div>
        </div>
        ${p.colors && p.colors.length ? `<div class="lp-block"><h3 class="lp-h">Available colours <small>যে কালারগুলো আছে</small></h3><div class="lp-avail">${p.colors.map(x => `<div><img src="${esc(p.img)}" alt=""><span><i style="background:${esc(x[1])}"></i>${esc(x[0])}</span></div>`).join('')}</div></div>` : ''}
        <div class="lp-block"><h3 class="lp-h">Why you will love it <small>কাছ থেকে দেখুন</small></h3>
          <div class="lp-why">${details.map(d => `<div style="background-image:url('${esc(d[0])}');background-size:${d[1]};background-position:${d[2]}"></div>`).join('')}</div></div>
        ${chart ? `<div class="lp-block" id="sizeChartBox"><h3 class="lp-h">Size chart <small>সাইজ চার্ট</small></h3><table class="chart lp-chart">${chart.map((r, i) => `<tr>${r.map(v => i ? `<td>${v}</td>` : `<th>${v}</th>`).join('')}</tr>`).join('')}</table></div>` : ''}
        ${reviews.length ? `<div class="lp-block"><h3 class="lp-h">Customers say <small>কাস্টমার রিভিউ</small></h3><div class="lp-revs">${reviews.map(r => `<div><span class="st">★★★★★</span><p>${esc(r[1])}</p><b>– ${esc(r[0] || 'Customer')}</b></div>`).join('')}</div></div>` : ''}
        <div class="lp-block"><h3 class="lp-h">Delivery information <small>ডেলিভারি তথ্য</small></h3>
          <div class="lp-deliv"><div>${ICO.bike}<p><b>ঢাকার ভিতরে</b>ডেলিভারি চার্জ: ৳70<br>সময়: ১-২ দিন</p></div><div>${ICO.van}<p><b>ঢাকার বাইরে</b>ডেলিভারি চার্জ: ৳130<br>সময়: ২-৪ দিন</p></div></div>
          <div class="lp-cod">${ICO.cash} সারা বাংলাদেশে ক্যাশ অন ডেলিভারি, পণ্য হাতে পেয়ে টাকা দিন</div></div>
      </div>
      <div class="lp-r">
        <div class="lp-head"><span class="lp-new">${p.isNew ? 'New arrival' : esc(c ? c.name : '')}</span><h1>${esc(p.name)}</h1>
          <div class="lp-price"><b>${money(price)}</b>${off ? `<s>${money(p.price)}</s><em>${off}% OFF</em>` : ''}</div>
          <ul class="lp-pts">${feats.slice(0, 4).map(f => `<li>${ICO.q}${esc(f)}</li>`).join('')}</ul></div>
        <form class="lp-order" id="orderForm" novalidate>
          <h2>অর্ডার করুন <small>Place your order</small></h2>
          ${p.colors && p.colors.length ? `<div class="lp-step"><h4>১. কালার বেছে নিন <span id="cName">${esc(color)}</span></h4><div class="lp-colors">${p.colors.map((x, i) => `<button type="button" class="${i ? '' : 'on'}" data-c="${esc(x[0])}"><i style="background:${esc(x[1])}"></i><img src="${esc(p.img)}" alt=""><span>${esc(x[0])}</span></button>`).join('')}</div></div>` : ''}
          ${p.sizes && p.sizes.length ? `<div class="lp-step"><h4>২. সাইজ বেছে নিন ${chart ? '<a href="#" id="chartLink">সাইজ চার্ট</a>' : ''}</h4><div class="lp-sizes">${p.sizes.map(s => so.includes(s) ? `<button type="button" class="out" disabled title="Out of stock">${esc(s)}</button>` : `<button type="button" class="${s === size ? 'on' : ''}" data-s="${esc(s)}">${esc(s)}</button>`).join('')}</div></div>` : ''}
          <div class="lp-step lp-row"><h4>৩. পরিমাণ</h4><div class="qty"><button type="button" data-q="-1">−</button><input id="qty" value="1" inputmode="numeric"><button type="button" data-q="1">+</button></div></div>
          <div class="lp-step"><h4>৪. ডেলিভারি এলাকা</h4><div class="lp-zone">
            <label class="on"><input type="radio" name="zone" value="inside" checked><span>ঢাকার ভিতরে</span><b>৳70</b></label>
            <label><input type="radio" name="zone" value="outside"><span>ঢাকার বাইরে</span><b>৳130</b></label></div></div>
          <div class="lp-step"><h4>৫. আপনার তথ্য</h4>
            <label class="lp-in">${ICO.user}<input name="name" required placeholder="আপনার নাম লিখুন"></label>
            <label class="lp-in">${ICO.phone}<input name="phone" required inputmode="tel" placeholder="মোবাইল নম্বর লিখুন (01XXXXXXXXX)"></label>
            <label class="lp-in ta">${ICO.pin}<textarea name="address" rows="2" required placeholder="সম্পূর্ণ ঠিকানা লিখুন (বাসা, রোড, এলাকা, জেলা)"></textarea></label></div>
          <div class="lp-step"><h4>৬. অতিরিক্ত নোট <small>(ঐচ্ছিক)</small></h4><textarea class="lp-note" name="note" rows="2" placeholder="কোনো কিছু বলার থাকলে এখানে লিখুন..."></textarea></div>
          <div class="lp-sum" id="lpSum"></div>
          <p class="lp-err" id="lpErr"></p>
          <button class="lp-btn" id="confirmBtn" ${noStock ? 'disabled' : ''}>${noStock ? 'স্টক শেষ' : 'অর্ডার কনফার্ম করুন'}</button>
          <p class="lp-safe">${ICO.lock} আপনার তথ্য সম্পূর্ণ নিরাপদ · পণ্য হাতে পেয়ে টাকা দিন</p>
          <button type="button" class="lp-cart" id="addBtn" ${noStock ? 'disabled' : ''}>অথবা কার্টে রাখুন (Add to cart)</button>
        </form>
      </div>
    </div>
    <section class="lp-why-band"><h2>কেন Rana Shop থেকে কিনবেন?</h2><div class="wrap">
      <div>${ICO.q}<p><b>প্রিমিয়াম কোয়ালিটি</b>১০০% বাছাই করা পণ্য</p></div>
      <div>${ICO.cash}<p><b>ক্যাশ অন ডেলিভারি</b>হাতে পেয়ে টাকা দিন</p></div>
      <div>${ICO.ret}<p><b>সহজ এক্সচেঞ্জ</b>৭ দিনের মধ্যে সাইজ বদল</p></div>
      <div>${ICO.van}<p><b>দ্রুত ডেলিভারি</b>সারা বাংলাদেশে</p></div></div></section>
    ${isHome && others.length ? `<section class="sec lp-more"><div class="wrap"><div class="sec-head"><h2>আরো প্রোডাক্ট <small>More products</small></h2><a href="#/collection">সব কালেকশন দেখুন</a></div><div class="grid">${others.map(card).join('')}</div></div></section>` : ''}
    ${!isHome && related.length ? `<section class="sec"><div class="wrap"><div class="sec-head"><h2>You may also like</h2><a href="#/c/${p.cat}">View all</a></div><div class="grid">${related.map(card).join('')}</div></div></section>` : ''}
    <div class="stickybar" id="sticky"><div class="wrap sb-in"><img src="${esc(p.img)}" alt=""><div class="sb-t"><b>${esc(p.name)}</b><span>${money(price)}</span></div><button class="btn lp-gold" id="sbGo">অর্ডার করুন</button></div></div>`;
    const zb = document.getElementById('zoomBox'), zi = document.getElementById('mainImg');
    zb.onmousemove = e => { if (e.target.closest('.arw')) return; const r = zb.getBoundingClientRect(); zi.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%'; zb.classList.add('on'); };
    zb.onmouseleave = () => zb.classList.remove('on');
    zb.onclick = e => { if (!e.target.closest('.arw')) zb.classList.toggle('tap'); };
    const show = i => { cur = (i + imgs.length) % imgs.length; zi.src = imgs[cur]; app.querySelectorAll('.lp-thumbs button').forEach((b, k) => b.classList.toggle('on', k === cur)); };
    app.querySelectorAll('.lp-thumbs button').forEach(b => b.onclick = () => show(+b.dataset.i));
    app.querySelectorAll('.arw').forEach(b => b.onclick = e => { e.stopPropagation(); show(cur + +b.dataset.d); });
    app.querySelectorAll('.lp-colors button').forEach(b => b.onclick = () => { app.querySelectorAll('.lp-colors button').forEach(x => x.classList.remove('on')); b.classList.add('on'); color = b.dataset.c; document.getElementById('cName').textContent = color; });
    app.querySelectorAll('.lp-sizes button[data-s]').forEach(b => b.onclick = () => { size = b.dataset.s; app.querySelectorAll('.lp-sizes button').forEach(x => x.classList.toggle('on', x === b)); });
    const cl = document.getElementById('chartLink'); if (cl) cl.onclick = e => { e.preventDefault(); document.getElementById('sizeChartBox').scrollIntoView({ behavior: 'smooth', block: 'center' }); };
    const q = document.getElementById('qty'); const qn = () => Math.max(1, Math.min(20, parseInt(q.value) || 1));
    const draw = () => {
      const n = qn(), sub = price * n, ship = zone === 'inside' ? (sub >= 3000 ? 0 : 70) : 130;
      document.getElementById('lpSum').innerHTML = `<h4>অর্ডার সামারি</h4><div><span>পণ্যের দাম</span><span>${money(price)}</span></div><div><span>পরিমাণ</span><span>${n}</span></div>
        <div><span>সাবটোটাল</span><b>${money(sub)}</b></div><div><span>ডেলিভারি চার্জ</span><span>${ship ? money(ship) : 'ফ্রি'}</span></div><div class="tot"><span>সর্বমোট</span><b>${money(sub + ship)}</b></div>`;
      return { sub, ship, total: sub + ship, n };
    };
    app.querySelectorAll('[data-q]').forEach(b => b.onclick = () => { q.value = Math.max(1, Math.min(20, qn() + +b.dataset.q)); draw(); });
    q.oninput = draw;
    app.querySelectorAll('.lp-zone input').forEach(r => r.onchange = () => { zone = r.value; app.querySelectorAll('.lp-zone label').forEach(l => l.classList.toggle('on', l.contains(r))); draw(); });
    draw();
    document.getElementById('addBtn').onclick = () => {
      if (noStock) return;
      const cart = RS.cart(); const key = p.id + '|' + color + '|' + size; const line = cart.find(l => l.key === key);
      if (line) line.qty += qn(); else cart.push({ key, id: p.id, color, size, qty: qn() });
      RS.saveCart(cart); updateCount(); toast('কার্টে যোগ হয়েছে' + (size ? ' (' + size + ')' : ''));
    };
    const form = document.getElementById('orderForm'), err = document.getElementById('lpErr');
    form.onsubmit = e => {
      e.preventDefault(); if (noStock) return;
      const f = new FormData(form); const v = k => String(f.get(k) || '').trim();
      const bad = !v('name') ? 'আপনার নাম লিখুন' : !/^(\+?88)?01[3-9]\d{8}$/.test(v('phone').replace(/[\s-]/g, '')) ? 'সঠিক মোবাইল নম্বর লিখুন (01XXXXXXXXX)' : !v('address') ? 'সম্পূর্ণ ঠিকানা লিখুন' : '';
      if (bad) { err.textContent = bad; const el = form.querySelector(`[name="${bad.includes('নাম') ? 'name' : bad.includes('মোবাইল') ? 'phone' : 'address'}"]`); el.focus(); return; }
      err.textContent = '';
      const t = draw(); const orders = RS.orders(); const no = 'RS' + (1001 + orders.length);
      orders.unshift({ no, date: new Date().toISOString(), name: v('name'), email: '', phone: v('phone'), address: v('address'), zone, note: v('note'), pay: 'Cash on Delivery', status: 'Pending',
        items: [{ name: p.name, img: p.img, color, size, qty: t.n, price }], sub: t.sub, ship: t.ship, total: t.total });
      RS.saveOrders(orders); sendOrderMail(orders[0]); location.hash = '#/done/' + no;
    };
    document.getElementById('sbGo').onclick = () => form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const sticky = document.getElementById('sticky');
    const io = new IntersectionObserver(([en]) => sticky.classList.toggle('show', !en.isIntersecting && window.scrollY > 300));
    io.observe(form);
  }
  function lines() {
    const ps = RS.products();
    return RS.cart().map(l => ({ ...l, p: ps.find(x => x.id === l.id) })).filter(l => l.p);
  }
  function totals(ls, zone) {
    const sub = ls.reduce((a, l) => a + now(l.p) * l.qty, 0);
    const ship = !ls.length ? 0 : zone === 'inside' ? (sub >= 3000 ? 0 : 70) : 130;
    return { sub, ship, total: sub + ship };
  }
  function cart() {
    const ls = lines();
    if (!ls.length) { app.innerHTML = '<div class="wrap empty"><h1 style="font-family:var(--hf);font-size:2.6rem;margin-bottom:10px">Your cart is empty</h1><p style="margin-bottom:24px">Find something you like.</p><a class="btn" href="#/shop">Shop now</a></div>'; return; }
    const t = totals(ls, 'inside');
    app.innerHTML = `<div class="page-head"><div class="wrap"><h1>Your cart</h1></div></div><div class="wrap" style="padding:30px 24px 80px">
      <table class="cart-tbl">${ls.map((l, i) => `<tr><td><img src="${esc(l.p.img)}" alt=""></td><td><b style="font-weight:500">${esc(l.p.name)}</b><br><small style="color:var(--muted)">${esc([l.color, l.size].filter(Boolean).join(' / '))}</small></td>
        <td><div class="qty"><button data-i="${i}" data-d="-1">−</button><input value="${l.qty}" readonly><button data-i="${i}" data-d="1">+</button></div></td><td class="hide-m">${money(now(l.p))}</td><td><b style="font-weight:500">${money(now(l.p) * l.qty)}</b></td><td><a href="#" data-rm="${i}" style="color:var(--muted)">✕</a></td></tr>`).join('')}</table>
      <div style="max-width:380px;margin-left:auto" class="summary"><div class="row"><span>Subtotal</span><b>${money(t.sub)}</b></div><p style="font-size:.85rem;color:var(--muted);margin:8px 0 18px">Delivery is added at checkout.</p><a class="btn block" href="#/checkout">Checkout</a></div></div>`;
    const c = RS.cart();
    app.querySelectorAll('[data-d]').forEach(b => b.onclick = () => { const l = c[b.dataset.i]; l.qty = Math.max(1, l.qty + +b.dataset.d); RS.saveCart(c); updateCount(); cart(); });
    app.querySelectorAll('[data-rm]').forEach(b => b.onclick = e => { e.preventDefault(); c.splice(+b.dataset.rm, 1); RS.saveCart(c); updateCount(); cart(); });
  }
  function checkout() {
    const ls = lines();
    if (!ls.length) { location.hash = '#/cart'; return; }
    app.innerHTML = `<div class="page-head"><div class="wrap"><h1>Checkout</h1></div></div>
    <form class="wrap co" id="coForm"><div>
      <label>Full name *<input name="name" required></label>
      <label>Mobile number *<input name="phone" required pattern="(\\+?88)?01[3-9][0-9]{8}" placeholder="01XXXXXXXXX"></label>
      <label>Full address *<textarea name="address" rows="2" required placeholder="House, road, area"></textarea></label>
      <label>Delivery area<select name="zone" id="zone"><option value="inside">Inside Dhaka (৳70, free over ৳3,000)</option><option value="outside">Outside Dhaka (৳130)</option></select></label>
      <label>Note (optional)<input name="note"></label>
      <div class="opt-label">Payment</div><div class="pay">
        <label><input type="radio" name="pay" value="Cash on Delivery" checked> Cash on Delivery</label>
        <label><input type="radio" name="pay" value="bKash"> bKash</label>
        <label><input type="radio" name="pay" value="Nagad"> Nagad</label>
        <label><input type="radio" name="pay" value="Card"> Visa / Mastercard</label></div></div>
      <div><div class="summary" id="sum"></div></div></form>`;
    const zone = document.getElementById('zone');
    const draw = () => { const t = totals(ls, zone.value); document.getElementById('sum').innerHTML = `<h3 style="font-family:var(--hf);font-size:1.6rem;margin-bottom:14px">Order summary</h3>
      ${ls.map(l => `<div class="row"><span>${esc(l.p.name)} × ${l.qty}<br><small style="color:var(--muted)">${esc([l.color, l.size].filter(Boolean).join(' / '))}</small></span><span>${money(now(l.p) * l.qty)}</span></div>`).join('')}
      <div class="row" style="border-top:1px solid var(--line);margin-top:8px;padding-top:12px"><span>Subtotal</span><span>${money(t.sub)}</span></div><div class="row"><span>Delivery</span><span>${t.ship ? money(t.ship) : 'Free'}</span></div>
      <div class="row total"><span>Total</span><span>${money(t.total)}</span></div><button class="btn block" style="margin-top:18px">Place order</button>`; };
    zone.onchange = draw; draw();
    document.getElementById('coForm').onsubmit = e => {
      e.preventDefault(); const f = new FormData(e.target); const t = totals(ls, f.get('zone'));
      const sentTo = '';
      const orders = RS.orders(); const no = 'RS' + (1001 + orders.length);
      orders.unshift({ no, date: new Date().toISOString(), name: f.get('name'), email: sentTo, phone: f.get('phone'), address: f.get('address'), zone: f.get('zone'), note: f.get('note'), pay: f.get('pay'), status: 'Pending',
        items: ls.map(l => ({ name: l.p.name, img: l.p.img, color: l.color, size: l.size, qty: l.qty, price: now(l.p) })), ...t });
      RS.saveOrders(orders); RS.saveCart([]); updateCount(); sendOrderMail(orders[0]); location.hash = '#/done/' + no;
    };
  }
  function sendOrderMail(o) {
    const cfg = RS.EMAILJS;
    if (!o.email || !(cfg.publicKey && cfg.serviceId && cfg.templateId && window.emailjs)) return;
    const items = o.items.map(i => `${i.name} (${[i.color, i.size].filter(Boolean).join(' / ')}) x ${i.qty} = ${money(i.price * i.qty)}`).join('\n');
    emailjs.send(cfg.serviceId, cfg.templateId, { to_email: o.email, name: o.name, order_no: o.no, items, total: money(o.total), payment: o.pay, address: o.address, whatsapp: RS.SHOP.whatsapp },
      { publicKey: cfg.publicKey }).catch(() => {});
  }
  function done(no) {
    const o = RS.orders().find(x => x.no === no) || {};
    const wa = RS.SHOP.whatsapp.replace(/\D/g, '').replace(/^0/, '880');
    const mailOn = RS.EMAILJS.publicKey && RS.EMAILJS.serviceId && RS.EMAILJS.templateId;
    app.innerHTML = `<div class="wrap done"><img src="img/logo-mark.png" alt="" style="width:72px;margin:0 auto 22px;border-radius:50%"><h1>Thank you!</h1>
      <p style="color:var(--muted);margin-bottom:6px">Your order has been placed.</p>
      <p style="font-size:1.4rem;margin-bottom:6px">Order number: <b>${esc(no)}</b></p>
      ${o.email && mailOn ? `<p style="color:var(--muted);margin-bottom:6px">We sent the details to <b>${esc(o.email)}</b>.</p>` : ''}
      <p style="color:var(--muted);margin-bottom:28px">For any question, message us on WhatsApp: <b>${esc(RS.SHOP.whatsapp)}</b></p>
      <a class="btn" target="_blank" href="https://wa.me/${wa}?text=${encodeURIComponent('Hi, my order number is ' + no)}">WhatsApp us</a> <a class="btn ghost" href="#/shop">Continue shopping</a></div>`;
  }
  // per-page title, description and Product schema for search engines / link previews
  const BASE = 'https://boostnixmedia-hash.github.io/RANA-SHOP/';
  function seo(h) {
    const set = (sel, attr, v) => { const el = document.querySelector(sel); if (el) el.setAttribute(attr, v); };
    let title = 'Rana Shop | Premium Menswear in Bangladesh: Shirts, Pants, T-Shirts, Shoes', desc = '', img = BASE + 'img/og.jpg', ld = null;
    if (h[0] === 'p') { const p = visible().find(x => x.slug === h[1]); if (p) { const c = RS.cat(p.cat);
      title = `${p.name} | ${c ? c.name : ''} | Rana Shop`; desc = `${p.name}: ${p.desc} Price ${money(now(p))}. Cash on Delivery all over Bangladesh.`; if (!/^data:/.test(p.img)) img = BASE + p.img;
      ld = { '@context': 'https://schema.org', '@type': 'Product', name: p.name, description: p.desc, image: img, sku: 'RS-' + p.id, brand: { '@type': 'Brand', name: 'Rana Shop' }, category: c ? c.name : '',
        offers: { '@type': 'Offer', priceCurrency: 'BDT', price: now(p), availability: (p.sizes || []).some(s => !(p.soldOut || []).includes(s)) || !(p.sizes || []).length ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', url: BASE + '#/p/' + p.slug, seller: { '@type': 'Organization', name: 'Rana Shop' } } }; } }
    else if (h[0] === 'c') { const c = RS.cat(h[1]); if (c) { title = `Men's ${c.name} in Bangladesh | Rana Shop`; desc = `Shop men's ${c.name.toLowerCase()} at Rana Shop. Premium quality, Cash on Delivery all over Bangladesh.`; } }
    else if (h[0] === 'shop' || h[0] === 'collection') { title = 'All Products | Rana Shop'; }
    else if (h[0] === 'cart' || h[0] === 'checkout' || h[0] === 'done') { title = (h[0] === 'cart' ? 'Cart' : h[0] === 'checkout' ? 'Checkout' : 'Order placed') + ' | Rana Shop'; }
    document.title = title;
    if (desc) { set('meta[name="description"]', 'content', desc); set('meta[property="og:description"]', 'content', desc); }
    set('meta[property="og:title"]', 'content', title); set('meta[property="og:image"]', 'content', img); set('meta[property="og:url"]', 'content', BASE + location.hash);
    set('link[rel="canonical"]', 'href', BASE + (h[0] === 'p' || h[0] === 'c' ? location.hash : ''));
    let tag = document.getElementById('ldPage'); if (tag) tag.remove();
    if (ld) { tag = document.createElement('script'); tag.type = 'application/ld+json'; tag.id = 'ldPage'; tag.textContent = JSON.stringify(ld); document.head.appendChild(tag); }
  }
  function route() {
    const h = location.hash.replace(/^#\/?/, '').split('/');
    document.querySelector('.nav').classList.remove('open');
    document.querySelectorAll('.nav a').forEach(a => a.classList.toggle('on', a.getAttribute('href') === location.hash));
    ({ '': landing, collection, shop: () => listing(), c: () => listing(h[1]), p: () => product(h[1]), cart, checkout, done: () => done(h[1]) }[h[0]] || landing)();
    seo(h); window.scrollTo(0, 0); updateCount();
  }
  window.addEventListener('hashchange', route); route();
})();
