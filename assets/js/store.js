/* Rana Shop storefront script (no dependencies) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const BASE = ($('meta[name=base]') || {}).content || '';
  const CSRF = ($('meta[name=csrf]') || {}).content || '';
  const U = (p) => BASE + '/' + p.replace(/^\//, '');

  async function api(path, data, method) {
    const opt = { method: method || (data ? 'POST' : 'GET'), headers: { Accept: 'application/json', 'X-CSRF-Token': CSRF }, credentials: 'same-origin' };
    if (data) { const fd = data instanceof FormData ? data : new FormData(); if (!(data instanceof FormData)) Object.entries(data).forEach(([k, v]) => fd.append(k, v)); fd.append('_csrf', CSRF); opt.body = fd; }
    const r = await fetch(U(path), opt);
    let j = {}; try { j = await r.json(); } catch (e) { j = { ok: false, error: 'Something went wrong. Please reload the page.' }; }
    return j;
  }
  function toast(msg) { const t = $('#toast'); if (!t || !msg) return; t.textContent = msg; t.classList.add('on'); clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('on'), 2600); }
  window.ranaToast = toast;
  const fmt = (n) => '৳' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 2 });

  /* ---------- panels (drawers, search, modals) ---------- */
  let openEl = null, lastFocus = null;
  function open(id) {
    const el = document.getElementById(id); if (!el) return;
    close(); lastFocus = document.activeElement;
    el.hidden = false; openEl = el;
    if (el.classList.contains('drawer') || el.id === 'search') $('#shade').hidden = false;
    document.body.style.overflow = 'hidden';
    if (id === 'cart') loadCart();
    const f = el.querySelector('input,button,a'); if (f) setTimeout(() => (id === 'search' ? $('#searchInput') : f).focus(), 50);
  }
  function close() {
    if (!openEl) return;
    openEl.hidden = true; if (openEl.id === 'quick') openEl.innerHTML = '';
    openEl = null; $('#shade').hidden = true; document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); open(o.dataset.open); return; }
    if (e.target.closest('[data-close]') || e.target.id === 'shade' || (e.target.classList && e.target.classList.contains('modal'))) { close(); }
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

  /* ---------- search suggestions ---------- */
  const si = $('#searchInput'), sg = $('#suggest');
  if (si) {
    let t;
    si.addEventListener('input', () => {
      clearTimeout(t);
      const q = si.value.trim();
      if (q.length < 2) { sg.innerHTML = ''; return; }
      t = setTimeout(async () => {
        const j = await api('api/search?q=' + encodeURIComponent(q));
        if (si.value.trim() !== q) return;
        sg.innerHTML = (j.items || []).map((p) => `<a href="${p.url}">${p.image ? `<img src="${p.image}" alt="">` : ''}<span>${esc(p.name)}<br><small>${p.price}</small></span></a>`).join('')
          + (j.items && j.items.length ? `<a class="all" href="${j.all}">See all results</a>` : '<p class="muted">No products found.</p>');
      }, 220);
    });
  }
  function esc(s) { return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]); }

  /* ---------- cart ---------- */
  function setCount(id, n) { const el = document.getElementById(id); if (!el) return; el.textContent = n; el.hidden = !n; }
  async function loadCart() {
    const j = await api('cart/drawer');
    $('#cartBody').innerHTML = j.html; setCount('cartCount', j.count);
  }
  async function addToCart(variantId, qty) {
    const j = await api('cart/add', { variant_id: variantId, qty: qty || 1 });
    if (j.ok) { setCount('cartCount', j.count); $('#cartBody').innerHTML = j.html; close(); open('cart'); }
    else toast(j.message || j.error);
    return j.ok;
  }
  document.addEventListener('click', async (e) => {
    const q = e.target.closest('#cartBody [data-q]');
    if (q) {
      const box = q.closest('[data-variant]'); const cur = parseInt(box.querySelector('span').textContent, 10);
      const j = await api('cart/update', { variant_id: box.dataset.variant, qty: cur + parseInt(q.dataset.q, 10) });
      $('#cartBody').innerHTML = j.html; setCount('cartCount', j.count); if (!j.ok) toast(j.message);
      return;
    }
    const rm = e.target.closest('#cartBody [data-remove]');
    if (rm) { const j = await api('cart/update', { variant_id: rm.dataset.remove, qty: 0 }); $('#cartBody').innerHTML = j.html; setCount('cartCount', j.count); }
  });

  /* ---------- wishlist ---------- */
  document.addEventListener('click', async (e) => {
    const w = e.target.closest('[data-wish]'); if (!w) return;
    e.preventDefault();
    const j = await api('wishlist/toggle', { product_id: w.dataset.wish });
    if (!j.ok) return toast(j.error);
    $$(`[data-wish="${w.dataset.wish}"]`).forEach((b) => { b.classList.toggle('on', j.on); b.setAttribute('aria-pressed', j.on); });
    setCount('wlCount', j.count); toast(j.on ? 'Saved to wishlist' : 'Removed from wishlist');
  });

  /* ---------- quick add ---------- */
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-quick]'); if (!b) return;
    e.preventDefault();
    const p = await api('api/product/' + b.dataset.quick);
    if (!p.variants) return;
    const inStock = p.variants.filter((v) => v.stock > 0);
    if (inStock.length === 1) return addToCart(inStock[0].id, 1);
    const colors = [...new Set(p.variants.map((v) => v.color))];
    const m = $('#quick');
    m.innerHTML = `<div class="modal-box"><div class="drawer-hd"><b>Quick add</b><button class="icon-btn" data-close aria-label="Close">✕</button></div>
      <div class="modal-body" style="display:grid;grid-template-columns:120px 1fr;gap:18px">
      <img src="${p.image}" alt="" style="width:120px;aspect-ratio:3/4;object-fit:cover;background:var(--surface)">
      <div><h3 style="margin:0 0 4px">${esc(p.name)}</h3><div class="price">${fmt(p.price)}</div>
      ${colors.filter(Boolean).length ? `<div class="opt"><div class="opt-l">Colour</div><div class="color-opts">${colors.map((c) => { const v = p.variants.find((x) => x.color === c); return `<button type="button" class="cbtn" data-qc="${esc(c)}" title="${esc(c)}"><i style="background:${esc(v.hex || '#ccc')}"></i></button>`; }).join('')}</div></div>` : ''}
      <div class="opt"><div class="opt-l">Size</div><div class="size-opts" id="qSizes"></div></div>
      <button class="btn block" id="qAdd" disabled>Select a size</button><p style="margin-top:10px"><a class="link small" href="${p.url}">View full details</a></p></div></div></div>`;
    open('quick');
    let color = colors.find((c) => p.variants.some((v) => v.color === c && v.stock > 0)) || colors[0], vid = null;
    const draw = () => {
      $$('[data-qc]', m).forEach((x) => x.classList.toggle('on', x.dataset.qc === color));
      $('#qSizes', m).innerHTML = p.variants.filter((v) => v.color === color).map((v) => `<button type="button" class="sbtn ${v.stock > 0 ? '' : 'na'} ${v.id === vid ? 'on' : ''}" data-qv="${v.id}" ${v.stock > 0 ? '' : 'disabled'}>${esc(v.size || 'One size')}</button>`).join('');
    };
    draw();
    m.onclick = (ev) => {
      const c = ev.target.closest('[data-qc]'); if (c) { color = c.dataset.qc; vid = null; $('#qAdd').disabled = true; $('#qAdd').textContent = 'Select a size'; draw(); }
      const s = ev.target.closest('[data-qv]'); if (s) { vid = parseInt(s.dataset.qv, 10); draw(); $('#qAdd').disabled = false; $('#qAdd').textContent = 'Add to bag'; }
      if (ev.target.closest('#qAdd') && vid) addToCart(vid, 1);
    };
  });

  /* ---------- product page ---------- */
  const pd = $('.pd[data-product]');
  if (pd) {
    const P = JSON.parse(pd.dataset.product);
    const V = P.variants;
    const colors = [...new Set(V.map((v) => v.color))];
    const hasColor = colors.some(Boolean), hasSize = V.some((v) => v.size);
    let color = hasColor ? (V.find((v) => v.stock > 0) || V[0]).color : '';
    let size = null;
    const form = $('#pdForm'), addBtn = $('#addBtn'), msg = $('#stockMsg');
    const pick = () => V.find((v) => v.color === color && (!hasSize || v.size === size));
    function draw() {
      $$('.cbtn', pd).forEach((b) => b.classList.toggle('on', b.dataset.color === color));
      if ($('#colorName')) $('#colorName').textContent = color;
      $$('.sbtn', pd).forEach((b) => {
        const v = V.find((x) => x.color === color && x.size === b.dataset.size);
        b.classList.toggle('on', b.dataset.size === size);
        b.classList.toggle('na', !v || v.stock <= 0);
        b.hidden = !v;
      });
      if ($('#sizeName')) $('#sizeName').textContent = size || '';
      const v = pick();
      $('#variantId').value = v ? v.id : '';
      const price = v ? v.price : P.base;
      $('#pdPrice').textContent = fmt(price); if ($('#stickyPrice')) $('#stickyPrice').textContent = fmt(price);
      if (!v && hasSize) { msg.textContent = ''; msg.className = 'stock-msg'; addBtn.disabled = false; addBtn.textContent = 'Add to bag'; }
      else if (v && v.stock <= 0) { msg.textContent = 'Out of stock in this size/colour'; msg.className = 'stock-msg low'; addBtn.disabled = true; addBtn.textContent = 'Sold out'; }
      else if (v) { msg.textContent = v.stock <= 5 ? `Only ${v.stock} left` : 'In stock'; msg.className = 'stock-msg' + (v.stock <= 5 ? ' low' : ''); addBtn.disabled = false; addBtn.textContent = 'Add to bag'; }
      const qi = $('#qty'); if (v && parseInt(qi.value, 10) > v.stock) qi.value = Math.max(1, v.stock);
    }
    pd.addEventListener('click', (e) => {
      const c = e.target.closest('.cbtn');
      if (c) {
        color = c.dataset.color;
        const v = pick(); if (v && v.stock <= 0) size = null;
        if (v && v.image) { const img = $(`#pdMain img[data-img="${v.image}"]`); if (img) img.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' }); }
        else { const any = V.find((x) => x.color === color && x.image); if (any) { const img = $(`#pdMain img[data-img="${any.image}"]`); if (img && window.innerWidth < 860) img.parentNode.scrollTo({ left: img.offsetLeft, behavior: 'smooth' }); } }
        draw();
      }
      const s = e.target.closest('.sbtn'); if (s && !s.classList.contains('na')) { size = s.dataset.size; draw(); }
      const st = e.target.closest('[data-step]');
      if (st) { const qi = $('#qty'); const v = pick(); const max = v ? v.stock : 20; qi.value = Math.min(Math.max(1, (parseInt(qi.value, 10) || 1) + parseInt(st.dataset.step, 10)), Math.min(20, max || 1)); }
      const th = e.target.closest('[data-thumb]');
      if (th) { const img = $$('#pdMain img')[th.dataset.thumb]; if (img) img.parentNode.scrollTo({ left: img.offsetLeft, behavior: 'smooth' }); }
    });
    form.addEventListener('submit', async (e) => {
      const buyNow = e.submitter && e.submitter.name === 'buy_now';
      if (hasSize && !size) { e.preventDefault(); msg.textContent = 'Please select a size'; msg.className = 'stock-msg low'; $('.size-opts').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
      if (buyNow) return; // normal POST → checkout
      e.preventDefault();
      addToCart($('#variantId').value, $('#qty').value);
    });
    const main = $('#pdMain');
    if (main) main.addEventListener('scroll', () => { const i = Math.round(main.scrollLeft / main.clientWidth); $$('[data-thumb]').forEach((b, k) => b.classList.toggle('on', k === i)); }, { passive: true });
    const sticky = $('#stickyBuy');
    if (sticky && 'IntersectionObserver' in window) new IntersectionObserver(([en]) => { sticky.hidden = en.isIntersecting || en.boundingClientRect.top > 0; }).observe(addBtn);
    $$('[data-scroll]').forEach((b) => b.addEventListener('click', () => document.getElementById(b.dataset.scroll).scrollIntoView({ behavior: 'smooth', block: 'center' })));
    draw();
  }

  /* ---------- checkout ---------- */
  const co = $('#coForm');
  if (co) {
    const refresh = async () => {
      const fd = new FormData(); fd.append('district', co.district.value); fd.append('coupon', $('#couponIn').value); fd.append('email', co.email.value);
      const j = await api('checkout/totals', fd);
      $('#tSub').textContent = j.subtotal; $('#tShip').textContent = j.shipping; $('#tTotal').textContent = j.total;
      $('#tDisc').textContent = '-' + j.discount; $('#tDiscRow').hidden = !(j.discount_raw > 0);
      $('#tZone').textContent = j.zone === 'inside' ? '(inside Dhaka)' : '(outside Dhaka)';
      $('#couponErr').textContent = $('#couponIn').value ? j.coupon_error : '';
      return j;
    };
    co.district.addEventListener('change', refresh);
    $('#couponBtn').addEventListener('click', async () => { const j = await refresh(); if (j.coupon_ok) toast('Coupon applied'); });
    $$('.addr-pick input').forEach((r) => r.addEventListener('change', () => {
      const d = JSON.parse(r.dataset.fill); Object.entries(d).forEach(([k, v]) => { if (co[k]) co[k].value = v; }); refresh();
    }));
    co.addEventListener('submit', (e) => {
      const btn = $('#placeBtn');
      if (btn.dataset.busy) { e.preventDefault(); return; }
      let bad = null;
      ['name', 'phone', 'email', 'district', 'area', 'address'].forEach((n) => { const f = co[n]; if (f && f.required && !f.value.trim()) { f.closest('label').classList.add('bad'); bad = bad || f; } else if (f) f.closest('label').classList.remove('bad'); });
      if (co.phone && co.phone.value && !/^(\+?88)?01[3-9]\d{8}$/.test(co.phone.value.replace(/[\s-]/g, ''))) { co.phone.closest('label').classList.add('bad'); bad = bad || co.phone; }
      if (bad) { e.preventDefault(); bad.focus(); toast('Please fill in the highlighted fields'); return; }
      btn.dataset.busy = 1; btn.textContent = 'Placing order…'; btn.style.opacity = .7; // prevents double submit
    });
  }

  /* ---------- newsletter ajax ---------- */
  $$('form[data-ajax]').forEach((f) => f.addEventListener('submit', async (e) => {
    e.preventDefault();
    const j = await api(f.getAttribute('action').replace(BASE, ''), new FormData(f));
    toast(j.message || j.error); if (j.ok) f.reset();
  }));

  /* ---------- copy coupon ---------- */
  document.addEventListener('click', (e) => {
    const c = e.target.closest('[data-copy]'); if (!c) return;
    if (navigator.clipboard) navigator.clipboard.writeText(c.dataset.copy).then(() => toast('Code copied: ' + c.dataset.copy));
  });

  /* ---------- slider ---------- */
  $$('.slider').forEach((sl) => {
    const slides = $$('.slide', sl), dots = $$('.dots button', sl); if (slides.length < 2) return;
    let i = 0, timer;
    const go = (n) => { slides[i].classList.remove('on'); dots[i] && dots[i].classList.remove('on'); i = (n + slides.length) % slides.length; slides[i].classList.add('on'); dots[i] && dots[i].classList.add('on'); };
    const auto = parseInt(sl.dataset.autoplay, 10) || 0;
    const start = () => { if (auto) { clearInterval(timer); timer = setInterval(() => go(i + 1), auto * 1000); } };
    $('.prev', sl).onclick = () => { go(i - 1); start(); }; $('.next', sl).onclick = () => { go(i + 1); start(); };
    dots.forEach((d, k) => (d.onclick = () => { go(k); start(); }));
    let x0 = null; sl.addEventListener('touchstart', (e) => (x0 = e.touches[0].clientX), { passive: true });
    sl.addEventListener('touchend', (e) => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); start(); } x0 = null; });
    start();
  });

  /* ---------- reveal on scroll ---------- */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { rootMargin: '0px 0px -8% 0px' });
    $$('.reveal').forEach((el) => io.observe(el));
  } else $$('.reveal').forEach((el) => el.classList.add('in'));
})();
