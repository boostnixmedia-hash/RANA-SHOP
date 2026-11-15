(function () {
  const { esc, money } = RS;
  const root = document.getElementById('root');
  const EMAIL = 'admin@ranashop.com', PASS = 'admin123';
  const STATUSES = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
  const pillCls = { Pending: 'y', Confirmed: 'b', Shipped: 'b', Delivered: 'g', Cancelled: 'r' };
  const src = img => /^(data:|https?:)/.test(img) ? img : '../' + img;
  const authed = () => sessionStorage.getItem('rs_admin') === '1';

  function login(err) {
    root.innerHTML = `<div class="login"><form class="box" id="lf" style="padding:34px">
      <div class="logo" style="justify-content:center;margin-bottom:24px"><img class="mark-img" src="../img/logo-mark.png" alt=""><span style="font-size:1.3rem">RANA SHOP</span></div>
      <h1 style="font-family:var(--bf);font-size:1.2rem;font-weight:500;text-align:center;margin-bottom:20px">Admin login</h1>
      ${err ? `<p style="color:var(--sale);margin-bottom:12px;font-size:.9rem">${err}</p>` : ''}
      <label>Email<input name="email" type="email" required value="${EMAIL}"></label>
      <label>Password<input name="pass" type="password" required></label>
      <button class="btn block">Log in</button>
      <p style="font-size:.8rem;color:var(--muted);margin-top:16px;text-align:center">Demo password: <b>${PASS}</b></p></form></div>`;
    document.getElementById('lf').onsubmit = e => {
      e.preventDefault(); const f = new FormData(e.target);
      if (f.get('email').trim().toLowerCase() === EMAIL && f.get('pass') === PASS) { sessionStorage.setItem('rs_admin', '1'); route(); }
      else login('Email or password is wrong.');
    };
  }
  function shell(active, title, body, action) {
    const pending = RS.orders().filter(o => o.status === 'Pending').length;
    root.innerHTML = `<div class="adm"><aside class="side">
      <a class="logo" href="#/"><img class="mark-img" src="../img/logo-mark.png" alt=""><span>RANA SHOP</span></a>
      <a href="#/" class="${active === 'dash' ? 'on' : ''}">Dashboard</a>
      <a href="#/orders" class="${active === 'orders' ? 'on' : ''}">Orders ${pending ? `<span class="pill y" style="margin-left:6px">${pending}</span>` : ''}</a>
      <a href="#/products" class="${active === 'products' ? 'on' : ''}">Products</a>
      <a href="#/new" class="${active === 'new' ? 'on' : ''}">+ Add product</a>
      <a href="../" target="_blank">View website ↗</a>
      <a href="#" id="logout">Log out</a></aside>
      <main class="adm-main"><h1>${title}${action || ''}</h1>${body}</main></div>`;
    document.getElementById('logout').onclick = e => { e.preventDefault(); sessionStorage.removeItem('rs_admin'); login(); };
  }
  function dash() {
    const os = RS.orders(), ps = RS.products();
    const sales = os.filter(o => o.status !== 'Cancelled').reduce((a, o) => a + o.total, 0);
    shell('dash', 'Dashboard', `<div class="stats">
      <div class="box"><small>Total sales</small><b>${money(sales)}</b></div>
      <div class="box"><small>Orders</small><b>${os.length}</b></div>
      <div class="box"><small>Pending orders</small><b>${os.filter(o => o.status === 'Pending').length}</b></div>
      <div class="box"><small>Products</small><b>${ps.length}</b></div></div>
      <div class="box"><h2>Latest orders</h2>${ordersTable(os.slice(0, 5))}</div>
      <div class="box" style="font-size:.88rem;color:var(--muted)">This is a demo. Products and orders you add are saved in this browser only.
        <a href="#" id="reset" style="color:var(--sale);margin-left:8px">Reset demo data</a></div>`);
    document.getElementById('reset').onclick = e => { e.preventDefault(); if (confirm('Reset all demo products and orders?')) { RS.reset(); route(); } };
  }
  function ordersTable(os) {
    if (!os.length) return '<p style="color:var(--muted)">No orders yet. Place one from the website to see it here.</p>';
    return `<div style="overflow-x:auto"><table class="tbl"><tr><th>Order</th><th>Customer</th><th class="hide-m">Date</th><th>Total</th><th>Status</th></tr>
      ${os.map(o => `<tr><td><a href="#/order/${o.no}"><b style="font-weight:500">${o.no}</b></a></td><td>${esc(o.name)}<br><small style="color:var(--muted)">${esc(o.phone)}</small></td>
      <td class="hide-m">${new Date(o.date).toLocaleString()}</td><td>${money(o.total)}</td><td><span class="pill ${pillCls[o.status]}">${o.status}</span></td></tr>`).join('')}</table></div>`;
  }
  function orders() { shell('orders', 'Orders', `<div class="box">${ordersTable(RS.orders())}</div>`); }
  function order(no) {
    const os = RS.orders(); const o = os.find(x => x.no === no);
    if (!o) return orders();
    shell('orders', 'Order ' + o.no, `<div class="form2"><div class="box"><h2>Items</h2><table class="tbl">${o.items.map(i => `<tr><td><img src="${src(i.img)}" alt=""></td><td>${esc(i.name)}<br><small style="color:var(--muted)">${esc([i.color, i.size].filter(Boolean).join(' / '))}</small></td><td>× ${i.qty}</td><td>${money(i.price * i.qty)}</td></tr>`).join('')}</table>
      <div style="max-width:300px;margin:16px 0 0 auto"><div class="row"><span>Subtotal</span><span>${money(o.sub)}</span></div><div class="row"><span>Delivery</span><span>${money(o.ship)}</span></div><div class="row total"><span>Total</span><span>${money(o.total)}</span></div></div></div>
      <div><div class="box"><h2>Status</h2><select id="st">${STATUSES.map(s => `<option ${s === o.status ? 'selected' : ''}>${s}</option>`).join('')}</select><button class="btn block" id="saveSt" style="margin-top:12px">Update status</button></div>
      <div class="box"><h2>Customer</h2><p>${esc(o.name)}<br>${esc(o.phone)}<br>${esc(o.address)}<br>${o.zone === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka'}</p><p style="margin-top:10px"><b style="font-weight:500">Payment:</b> ${esc(o.pay)}</p>${o.note ? `<p><b style="font-weight:500">Note:</b> ${esc(o.note)}</p>` : ''}</div></div></div>`,
      `<a class="btn ghost sm" href="#/orders">← All orders</a>`);
    document.getElementById('saveSt').onclick = () => { o.status = document.getElementById('st').value; RS.saveOrders(os); toast('Status updated'); route(); };
  }
  function products() {
    const ps = RS.products();
    shell('products', 'Products', `<div class="box" style="overflow-x:auto"><table class="tbl"><tr><th></th><th>Name</th><th class="hide-m">Category</th><th>Price</th><th class="hide-m">Status</th><th></th></tr>
      ${ps.map(p => `<tr><td><img src="${src(p.img)}" alt=""></td><td><a href="#/edit/${p.id}"><b style="font-weight:500">${esc(p.name)}</b></a>${p.featured ? ' <span class="pill">★ Featured</span>' : ''}</td>
      <td class="hide-m">${esc((RS.cat(p.cat) || {}).name || '')}</td><td>${p.sale ? `${money(p.sale)} <s style="color:var(--muted)">${money(p.price)}</s>` : money(p.price)}</td>
      <td class="hide-m"><span class="pill ${p.status === 'draft' ? '' : 'g'}">${p.status === 'draft' ? 'Draft' : 'Active'}</span></td>
      <td><div class="acts"><a class="btn ghost sm" href="#/edit/${p.id}">Edit</a><button class="btn danger sm" data-del="${p.id}">Delete</button></div></td></tr>`).join('')}</table></div>`,
      `<a class="btn sm" href="#/new">+ Add product</a>`);
    root.querySelectorAll('[data-del]').forEach(b => b.onclick = () => {
      if (!confirm('Delete this product?')) return;
      RS.saveProducts(RS.products().filter(p => p.id !== +b.dataset.del)); toast('Product deleted'); products();
    });
  }
  function form(id) {
    const ps = RS.products(); const p = id ? ps.find(x => x.id === +id) : null;
    if (id && !p) return products();
    const d = p || { name: '', desc: '', price: '', sale: null, cat: 'shirts', sizes: [], colors: [], featured: 1, status: 'active', img: '' };
    let img = d.img;
    shell(p ? 'products' : 'new', p ? 'Edit product' : 'Add product', `<form id="pf" class="form2"><div>
      <div class="box"><label>Product name *<input name="name" required value="${esc(d.name)}" placeholder="e.g. Oversized Cotton T-Shirt"></label>
        <label>Description<textarea name="desc" rows="3">${esc(d.desc)}</textarea></label></div>
      <div class="box"><h2>Photo</h2><label class="upl">Click to upload a photo (JPG / PNG)<input type="file" id="file" accept="image/*"></label><img id="prev" class="prev" ${img ? `src="${src(img)}"` : 'style="display:none"'} alt=""></div>
      <div class="box"><h2>Sizes & colours</h2>
        <label>Sizes (separate with commas)<input name="sizes" id="sizes" value="${esc(d.sizes.join(', '))}" placeholder="S, M, L, XL"></label>
        <div class="chips" style="margin:-6px 0 16px"><button type="button" data-z="S, M, L, XL, XXL">Shirt / T-shirt</button><button type="button" data-z="28, 30, 32, 34, 36">Pant</button><button type="button" data-z="39, 40, 41, 42, 43, 44">Shoe</button></div>
        <div style="font-size:.85rem;margin-bottom:8px">Colours</div><div id="cols"></div><button type="button" class="btn ghost sm" id="addCol">+ Add colour</button></div></div>
      <div><div class="box"><label>Status<select name="status"><option value="active">Active (visible on website)</option><option value="draft" ${d.status === 'draft' ? 'selected' : ''}>Draft (hidden)</option></select></label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="featured" style="width:auto;margin:0" ${d.featured ? 'checked' : ''}> Featured product</label>
        <button class="btn block">${p ? 'Save changes' : 'Add product'}</button>${p ? `<a class="btn ghost block" style="margin-top:8px" href="../#/p/${esc(p.slug)}" target="_blank">View on website</a>` : ''}</div>
      <div class="box"><h2>Price</h2><label>Regular price (৳) *<input name="price" type="number" min="1" required value="${esc(d.price)}"></label>
        <label>Sale price (৳) <small style="color:var(--muted)">(optional)</small><input name="sale" type="number" min="0" value="${esc(d.sale || '')}"></label></div>
      <div class="box"><h2>Category</h2><select name="cat">${RS.CATS.map(c => `<option value="${c.slug}" ${c.slug === d.cat ? 'selected' : ''}>${c.name}</option>`).join('')}</select></div></div></form>`);
    const cols = document.getElementById('cols');
    const addCol = (n = '', h = '#222222') => { const r = document.createElement('div'); r.className = 'color-row'; r.innerHTML = `<input class="cn" placeholder="Colour name" value="${esc(n)}"><input type="color" class="ch" value="${esc(h)}"><button type="button" class="btn ghost sm">✕</button>`; r.querySelector('button').onclick = () => r.remove(); cols.appendChild(r); };
    d.colors.forEach(c => addCol(c[0], c[1]));
    document.getElementById('addCol').onclick = () => addCol();
    root.querySelectorAll('[data-z]').forEach(b => b.onclick = () => { document.getElementById('sizes').value = b.dataset.z; });
    document.getElementById('file').onchange = e => {
      const f = e.target.files[0]; if (!f) return;
      const r = new FileReader(); r.onload = () => { const im = new Image(); im.onload = () => {
        const w = Math.min(700, im.width), h = Math.round(im.height * w / im.width); const c = document.createElement('canvas'); c.width = w; c.height = h;
        c.getContext('2d').drawImage(im, 0, 0, w, h); img = c.toDataURL('image/jpeg', 0.8); const pv = document.getElementById('prev'); pv.src = img; pv.style.display = 'block'; }; im.src = r.result; }; r.readAsDataURL(f);
    };
    document.getElementById('pf').onsubmit = e => {
      e.preventDefault(); const f = new FormData(e.target);
      if (!img) { alert('Please upload a photo.'); return; }
      const price = +f.get('price'), sale = +f.get('sale') || null;
      if (sale && sale >= price) { alert('Sale price must be lower than the regular price.'); return; }
      const list = RS.products(); const name = f.get('name').trim();
      let slug = RS.slugify(name), i = 2; while (list.some(x => x.slug === slug && (!p || x.id !== p.id))) slug = RS.slugify(name) + '-' + i++;
      const data = { name, slug, desc: f.get('desc').trim(), price, sale, cat: f.get('cat'), img, status: f.get('status'), featured: f.get('featured') ? 1 : 0,
        sizes: f.get('sizes').split(',').map(s => s.trim()).filter(Boolean), colors: [...cols.children].map(r => [r.querySelector('.cn').value.trim(), r.querySelector('.ch').value]).filter(c => c[0]) };
      if (p) Object.assign(list.find(x => x.id === p.id), data);
      else list.unshift({ id: Math.max(0, ...list.map(x => x.id)) + 1, isNew: true, ...data });
      if (RS.saveProducts(list)) { toast(p ? 'Product saved' : 'Product added'); location.hash = '#/products'; }
    };
  }
  function toast(msg) { let t = document.getElementById('toast'); if (!t) { t = document.createElement('div'); t.id = 'toast'; t.className = 'toast'; document.body.appendChild(t); } t.textContent = msg; t.classList.add('show'); clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('show'), 2200); }
  function route() {
    if (!authed()) return login();
    const h = location.hash.replace(/^#\/?/, '').split('/');
    ({ '': dash, orders, order: () => order(h[1]), products, new: () => form(), edit: () => form(h[1]) }[h[0]] || dash)();
    window.scrollTo(0, 0);
  }
  window.addEventListener('hashchange', route); route();
})();
