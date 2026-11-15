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
    if (q) { const p = RS.products().find(x => x.id === +q.dataset.add); if (!p) return; const color = ((p.colors || [])[0] || [''])[0], size = (p.sizes || [])[0] || '';
      const cart = RS.cart(); const key = p.id + '|' + color + '|' + size; const l = cart.find(x => x.key === key); if (l) l.qty++; else cart.push({ key, id: p.id, color, size, qty: 1 });
      RS.saveCart(cart); updateCount(); toast('Added: ' + p.name + (size ? ' (' + size + ')' : '')); }
  });
  const visible = () => RS.products().filter(p => p.status !== 'draft');

  function home() {
    const ps = visible();
    const feat = ps.filter(p => p.featured).slice(0, 8);
    app.innerHTML = `
    <section class="hero"><div class="wrap"><div class="hero-txt">
      <div class="eyebrow">New Season 2027</div><h1>Quiet luxury, everyday prices</h1>
      <p>Shirts, pants, tees and shoes made from fabrics that feel as good as they look.</p>
      <a class="btn" href="#/shop">Shop now</a> <a class="btn ghost" href="#/c/shirts">Shirts</a></div></div></section>
    <div class="feats"><div class="wrap">
      <div><b>Nationwide delivery</b><small>2–4 days anywhere in Bangladesh</small></div>
      <div><b>Cash on Delivery</b><small>Pay when you receive</small></div>
      <div><b>7-day exchange</b><small>Easy size exchange</small></div>
      <div><b>Secure payment</b><small>bKash, Nagad and cards</small></div></div></div>
    <section class="sec"><div class="wrap"><div class="sec-head"><h2>Shop by category</h2><a href="#/shop">View all</a></div>
      <div class="cats">${RS.CATS.map(c => `<a class="cat" href="#/c/${c.slug}"><img src="${c.img}" alt="${c.name}"><span>${c.name}</span></a>`).join('')}</div></div></section>
    <section class="sec" style="padding-top:0"><div class="wrap"><div class="sec-head"><h2>New arrivals</h2><a href="#/shop">Shop all</a></div>
      <div class="grid">${ps.slice(0, 8).map(card).join('')}</div></div></section>
    <section class="sec" style="background:var(--surface)"><div class="wrap"><div class="sec-head"><h2>Featured</h2><a href="#/shop">See more</a></div>
      <div class="grid">${feat.map(card).join('')}</div></div></section>`;
  }
  function listing(slug) {
    const c = slug ? RS.cat(slug) : null;
    const ps = visible().filter(p => !slug || p.cat === slug);
    app.innerHTML = `<div class="page-head"><div class="wrap"><h1>${c ? c.name : 'All products'}</h1><p>${ps.length} products</p></div></div>
      <div class="wrap"><div class="filters"><a href="#/shop" class="${!slug ? 'on' : ''}">All</a>${RS.CATS.map(x => `<a href="#/c/${x.slug}" class="${x.slug === slug ? 'on' : ''}">${x.name}</a>`).join('')}</div>
      ${ps.length ? `<div class="grid" style="margin-bottom:80px">${ps.map(card).join('')}</div>` : '<p class="empty">No products here yet.</p>'}</div>`;
  }
  function product(slug) {
    const p = visible().find(x => x.slug === slug);
    if (!p) { app.innerHTML = '<div class="wrap empty"><h1>Product not found</h1><p><a class="btn" href="#/shop">Back to shop</a></p></div>'; return; }
    const c = RS.cat(p.cat);
    let size = (p.sizes || [])[0] || '', color = ((p.colors || [])[0] || [''])[0];
    app.innerHTML = `<div class="wrap pd">
      <div class="main-img zoom" id="zoomBox"><img src="${esc(p.img)}" alt="${esc(p.name)}"><span class="zhint">Hover to zoom</span></div>
      <div><div class="crumbs"><a href="#/">Home</a> / <a href="#/c/${p.cat}">${c ? c.name : ''}</a></div>
        <h1>${esc(p.name)}</h1>${priceHtml(p)}<p class="short">${esc(p.desc)}</p>
        ${p.colors && p.colors.length ? `<div class="opt-label">Colour: <span id="cName">${esc(color)}</span></div><div class="colors">${p.colors.map((x, i) => `<button type="button" class="${i ? '' : 'on'}" data-c="${esc(x[0])}" style="background:${esc(x[1])}" title="${esc(x[0])}"></button>`).join('')}</div>` : ''}
        ${p.sizes && p.sizes.length ? `<div class="opt-label">Size</div><div class="sizes">${p.sizes.map((s, i) => `<button type="button" class="${i ? '' : 'on'}" data-s="${esc(s)}">${esc(s)}</button>`).join('')}</div>` : ''}
        <div class="buy"><div class="qty"><button type="button" data-q="-1">−</button><input id="qty" value="1" inputmode="numeric"><button type="button" data-q="1">+</button></div>
          <button class="btn" id="addBtn" style="flex:1">Add to cart</button></div>
        <button class="btn ghost block" id="buyBtn">Buy now</button>
        <div class="perks" style="margin-top:24px"><span>✓ Cash on Delivery all over Bangladesh</span><span>✓ Delivery in 2–4 days</span><span>✓ 7-day size exchange</span></div></div></div>`;
    const zb = document.getElementById('zoomBox'), zi = zb.querySelector('img');
    zb.onmousemove = e => { const r = zb.getBoundingClientRect(); zi.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%'; zb.classList.add('on'); };
    zb.onmouseleave = () => zb.classList.remove('on');
    zb.onclick = () => zb.classList.toggle('tap');
    app.querySelectorAll('.colors button').forEach(b => b.onclick = () => { app.querySelectorAll('.colors button').forEach(x => x.classList.remove('on')); b.classList.add('on'); color = b.dataset.c; document.getElementById('cName').textContent = color; });
    app.querySelectorAll('.sizes button').forEach(b => b.onclick = () => { app.querySelectorAll('.sizes button').forEach(x => x.classList.remove('on')); b.classList.add('on'); size = b.dataset.s; });
    const q = document.getElementById('qty');
    app.querySelectorAll('[data-q]').forEach(b => b.onclick = () => { q.value = Math.max(1, (parseInt(q.value) || 1) + +b.dataset.q); });
    const add = () => {
      const cart = RS.cart(); const key = p.id + '|' + color + '|' + size;
      const line = cart.find(l => l.key === key); const n = Math.max(1, parseInt(q.value) || 1);
      if (line) line.qty += n; else cart.push({ key, id: p.id, color, size, qty: n });
      RS.saveCart(cart); updateCount(); toast('Added to cart');
    };
    document.getElementById('addBtn').onclick = add;
    document.getElementById('buyBtn').onclick = () => { add(); location.hash = '#/checkout'; };
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
