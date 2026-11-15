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

  function home() {
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
  function product(slug) {
    const p = visible().find(x => x.slug === slug);
    if (!p) { app.innerHTML = '<div class="wrap empty"><h1>Product not found</h1><p><a class="btn" href="#/shop">Back to shop</a></p></div>'; return; }
    const c = RS.cat(p.cat);
    const imgs = [p.img, ...(p.images || [])].filter(Boolean);
    const so = p.soldOut || []; let size = (p.sizes || []).find(s => !so.includes(s)) || '', color = ((p.colors || [])[0] || [''])[0], cur = 0;
    // recently viewed
    let rv = RS.ls.get('rs_recent', []).filter(id => id !== p.id); rv.unshift(p.id); RS.ls.set('rs_recent', rv.slice(0, 10));
    const recent = rv.slice(1).map(id => visible().find(x => x.id === id)).filter(Boolean).slice(0, 5);
    const related = visible().filter(x => x.cat === p.cat && x.id !== p.id).slice(0, 4);
    const chart = SIZE_CHART[p.cat];
    const sku = 'RS-' + String(p.cat).slice(0, 2).toUpperCase() + '-' + String(p.id).padStart(4, '0');
    app.innerHTML = `<div class="crumbbar"><div class="wrap crumbs"><a href="#/">Home</a> › <a href="#/c/${p.cat}">${c ? c.name : ''}</a> › <span>${esc(p.name)}</span></div></div>
    <div class="wrap pd pd2">
      <div class="gal ${imgs.length > 1 ? '' : 'one'}">
        <div class="thumbs">${imgs.map((s, i) => `<button type="button" class="${i ? '' : 'on'}" data-i="${i}"><img src="${esc(s)}" alt=""></button>`).join('')}</div>
        <div class="main-img zoom" id="zoomBox"><img id="mainImg" src="${esc(imgs[0])}" alt="${esc(p.name)}"><span class="zhint">Hover to zoom</span>
          ${imgs.length > 1 ? '<button type="button" class="arw l" data-d="-1">‹</button><button type="button" class="arw r" data-d="1">›</button>' : ''}</div>
      </div>
      <div class="pinfo"><h1>${esc(p.name)}</h1>${priceHtml(p)}<p class="short">${esc(p.desc)}</p>
        ${p.colors && p.colors.length ? `<div class="opt-label">Colour: <span id="cName">${esc(color)}</span></div><div class="colors sw2">${p.colors.map((x, i) => `<button type="button" class="${i ? '' : 'on'}" data-c="${esc(x[0])}" style="background:${esc(x[1])}" title="${esc(x[0])}"></button>`).join('')}</div>` : ''}
        ${p.sizes && p.sizes.length ? `<div class="opt-label">Size: <span id="sName">${esc(size)}</span>${chart ? ' <a href="#" id="chartLink" class="chartlink">Size chart</a>' : ''}</div><div class="sizes sz2">${p.sizes.map(s => so.includes(s) ? `<button type="button" class="out" disabled title="Out of stock">${esc(s)}</button>` : `<button type="button" class="${s === size ? 'on' : ''}" data-s="${esc(s)}">${esc(s)}</button>`).join('')}</div>` : ''}
        <div class="buy"><div class="qty"><button type="button" data-q="-1">−</button><input id="qty" value="1" inputmode="numeric"><button type="button" data-q="1">+</button></div>
          <button class="btn" id="addBtn" style="flex:1" ${p.sizes && p.sizes.length && !size ? 'disabled' : ''}>${p.sizes && p.sizes.length && !size ? 'Out of stock' : 'Add to cart'}</button><button type="button" class="heart sq ${wish().includes(p.id) ? 'on' : ''}" data-wish="${p.id}">${wish().includes(p.id) ? '♥' : '♡'}</button></div>
        <button class="btn ghost block" id="buyBtn">Buy now</button>
        <div class="acc">
          <details open><summary>Description & Size Chart</summary><div class="acc-b"><p>${esc(p.desc)}</p><ul><li>Premium quality fabric</li><li>Colour-fast, pre-washed</li><li>Machine wash cold, dry in shade</li></ul>
            ${chart ? `<table class="chart" id="sizeChart">${chart.map((r, i) => `<tr>${r.map(v => i ? `<td>${v}</td>` : `<th>${v}</th>`).join('')}</tr>`).join('')}</table>` : ''}</div></details>
          <details><summary>Delivery & Exchange</summary><div class="acc-b"><p>Inside Dhaka: 1–2 days (৳70, free over ৳3,000). Outside Dhaka: 2–4 days (৳130).</p><p>Wrong size? Exchange within 7 days. Item must be unused with tags.</p></div></details>
        </div>
        <div class="pays"><span>COD</span><span>VISA</span><span>Mastercard</span><span class="bk">bKash</span><span class="ng">Nagad</span></div>
        <p class="meta">SKU: ${sku}<br>Category: ${c ? c.name : ''}</p>
      </div></div>
    ${related.length ? `<section class="sec" style="padding-top:20px"><div class="wrap"><div class="sec-head"><h2>You may also like</h2><a href="#/c/${p.cat}">View all</a></div><div class="grid">${related.map(card).join('')}</div></div></section>` : ''}
    ${recent.length ? `<section class="rv"><h3>Recently viewed</h3><div class="rv-row">${recent.map(x => `<a href="#/p/${esc(x.slug)}"><img src="${esc(x.img)}" alt=""><span>${esc(x.name)}</span></a>`).join('')}</div></section>` : ''}
    <div class="stickybar" id="sticky"><div class="wrap sb-in"><img src="${esc(p.img)}" alt=""><div class="sb-t"><b>${esc(p.name)}</b><span>${money(now(p))}</span></div>
      ${p.sizes && p.sizes.length ? `<select id="sbSize">${p.sizes.filter(s => !so.includes(s)).map(s => `<option>${esc(s)}</option>`).join('')}</select>` : ''}<button class="btn" id="sbAdd">Add to cart</button></div></div>`;
    const zb = document.getElementById('zoomBox'), zi = document.getElementById('mainImg');
    zb.onmousemove = e => { if (e.target.closest('.arw')) return; const r = zb.getBoundingClientRect(); zi.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%'; zb.classList.add('on'); };
    zb.onmouseleave = () => zb.classList.remove('on');
    zb.onclick = e => { if (!e.target.closest('.arw')) zb.classList.toggle('tap'); };
    const show = i => { cur = (i + imgs.length) % imgs.length; zi.src = imgs[cur]; app.querySelectorAll('.thumbs button').forEach((b, k) => b.classList.toggle('on', k === cur)); };
    app.querySelectorAll('.thumbs button').forEach(b => b.onclick = () => show(+b.dataset.i));
    app.querySelectorAll('.arw').forEach(b => b.onclick = e => { e.stopPropagation(); show(cur + +b.dataset.d); });
    app.querySelectorAll('.colors button').forEach(b => b.onclick = () => { app.querySelectorAll('.colors button').forEach(x => x.classList.remove('on')); b.classList.add('on'); color = b.dataset.c; document.getElementById('cName').textContent = color; });
    const setSize = s => { size = s; app.querySelectorAll('.sizes button').forEach(x => x.classList.toggle('on', x.dataset.s === s)); const sn = document.getElementById('sName'); if (sn) sn.textContent = s; const ss = document.getElementById('sbSize'); if (ss) ss.value = s; };
    app.querySelectorAll('.sizes button[data-s]').forEach(b => b.onclick = () => setSize(b.dataset.s));
    const cl = document.getElementById('chartLink'); if (cl) cl.onclick = e => { e.preventDefault(); const d = app.querySelector('.acc details'); d.open = true; document.getElementById('sizeChart').scrollIntoView({ behavior: 'smooth', block: 'center' }); };
    const q = document.getElementById('qty');
    app.querySelectorAll('[data-q]').forEach(b => b.onclick = () => { q.value = Math.max(1, (parseInt(q.value) || 1) + +b.dataset.q); });
    const add = () => {
      if (p.sizes && p.sizes.length && !size) { toast('Sorry, this item is out of stock'); return; }
      const cart = RS.cart(); const key = p.id + '|' + color + '|' + size;
      const line = cart.find(l => l.key === key); const n = Math.max(1, parseInt(q.value) || 1);
      if (line) line.qty += n; else cart.push({ key, id: p.id, color, size, qty: n });
      RS.saveCart(cart); updateCount(); toast('Added to cart' + (size ? ' (' + size + ')' : ''));
    };
    document.getElementById('addBtn').onclick = add;
    document.getElementById('buyBtn').onclick = () => { add(); location.hash = '#/checkout'; };
    const ss = document.getElementById('sbSize'); if (ss) ss.onchange = () => setSize(ss.value);
    document.getElementById('sbAdd').onclick = add;
    const sticky = document.getElementById('sticky'), ab = document.getElementById('addBtn');
    const io = new IntersectionObserver(([en]) => sticky.classList.toggle('show', !en.isIntersecting && en.boundingClientRect.top < 0));
    io.observe(ab);
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
      <label>Gmail * <small style="color:var(--muted)">(we will email your order details)</small><input name="email" id="email" type="email" required placeholder="yourname@gmail.com"></label>
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
      const sentTo = f.get('email').trim().toLowerCase();
      const orders = RS.orders(); const no = 'RS' + (1001 + orders.length);
      orders.unshift({ no, date: new Date().toISOString(), name: f.get('name'), email: sentTo, phone: f.get('phone'), address: f.get('address'), zone: f.get('zone'), note: f.get('note'), pay: f.get('pay'), status: 'Pending',
        items: ls.map(l => ({ name: l.p.name, img: l.p.img, color: l.color, size: l.size, qty: l.qty, price: now(l.p) })), ...t });
      RS.saveOrders(orders); RS.saveCart([]); updateCount(); sendOrderMail(orders[0]); location.hash = '#/done/' + no;
    };
  }
  function sendOrderMail(o) {
    const cfg = RS.EMAILJS;
    if (!(cfg.publicKey && cfg.serviceId && cfg.templateId && window.emailjs)) return;
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
  function route() {
    const h = location.hash.replace(/^#\/?/, '').split('/');
    document.querySelector('.nav').classList.remove('open');
    document.querySelectorAll('.nav a').forEach(a => a.classList.toggle('on', a.getAttribute('href') === location.hash));
    ({ '': home, shop: () => listing(), c: () => listing(h[1]), p: () => product(h[1]), cart, checkout, done: () => done(h[1]) }[h[0]] || home)();
    window.scrollTo(0, 0); updateCount();
  }
  window.addEventListener('hashchange', route); route();
})();
