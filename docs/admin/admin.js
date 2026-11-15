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
      <label>Email<input name="email" type="email" required autocomplete="username"></label>
      <label>Password<input name="pass" type="password" required></label>
      <button class="btn block">Log in</button></form></div>`;
    document.getElementById('lf').onsubmit = e => {
      e.preventDefault(); const f = new FormData(e.target);
      if (f.get('email').trim().toLowerCase() === EMAIL && f.get('pass') === PASS) { sessionStorage.setItem('rs_admin', '1'); route(); }
      else login('Email or password is wrong.');
    };
  }
  function shell(active, title, body, action) {
    const pending = RS.orders().filter(o => !o.seen).length;
    root.innerHTML = `<div class="adm"><aside class="side">
      <a class="logo" href="#/"><img class="mark-img" src="../img/logo-mark.png" alt=""><span>RANA SHOP</span></a>
      <a href="#/" class="${active === 'dash' ? 'on' : ''}">Dashboard</a>
      <a href="#/orders" class="${active === 'orders' ? 'on' : ''}">Orders ${pending ? `<span class="pill y" style="margin-left:6px" title="New orders you have not opened yet">${pending}</span>` : ''}</a>
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
    bindOrders();
    document.getElementById('reset').onclick = e => { e.preventDefault(); if (confirm('Reset all demo products and orders?')) { RS.reset(); route(); } };
  }
  function ordersTable(os) {
    if (!os.length) return '<p style="color:var(--muted)">No orders yet. Place one from the website to see it here.</p>';
    return `<div style="overflow-x:auto"><table class="tbl"><tr><th>Order</th><th>Customer</th><th class="hide-m">Date</th><th>Total</th><th>Status</th><th></th></tr>
      ${os.map(o => `<tr class="orow" data-no="${o.no}" style="cursor:pointer"><td><b style="font-weight:500">${o.no}</b>${o.seen ? '' : ' <span class="pill y" style="margin-left:4px">New</span>'}</td><td>${esc(o.name)}<br><small style="color:var(--muted)">${esc(o.phone)}</small></td>
      <td class="hide-m">${new Date(o.date).toLocaleString()}</td><td>${money(o.total)}</td>
      <td><select class="qs" data-no="${o.no}" style="width:auto;margin:0;padding:6px 8px">${STATUSES.map(s => `<option ${s === o.status ? 'selected' : ''}>${s}</option>`).join('')}</select></td>
      <td><a class="btn sm" href="#/order/${o.no}">View</a></td></tr>`).join('')}</table></div>`;
  }
  function bindOrders() {
    root.querySelectorAll('.qs').forEach(sel => { sel.onclick = e => e.stopPropagation(); sel.onchange = () => { const os = RS.orders(); const o = os.find(x => x.no === sel.dataset.no); o.status = sel.value; RS.saveOrders(os); toast(o.no + ': ' + o.status); }; });
    root.querySelectorAll('.orow').forEach(r => r.onclick = e => { if (!e.target.closest('a,select')) location.hash = '#/order/' + r.dataset.no; });
  }
  function orders() {
    shell('orders', 'Orders', `<p style="margin:0 0 12px;font-size:.95rem">Total orders: <b>${RS.orders().length}</b> &nbsp;·&nbsp; New (not opened yet): <b>${RS.orders().filter(o => !o.seen).length}</b></p><div class="box">${ordersTable(RS.orders())}</div><p style="font-size:.85rem;color:var(--muted)">Tip: change the status right here, or tap an order to see everything.</p>`,
      `<button class="btn sm" id="xls">⬇ Download Excel</button>`);
    bindOrders();
    document.getElementById('xls').onclick = exportExcel;
  }
  function exportExcel() {
    const os = RS.orders();
    if (!os.length) { alert('No orders yet.'); return; }
    const rows = os.map(o => {
      const wa = String(o.phone || '').replace(/\D/g, '').replace(/^0/, '880');
      return { 'Order No': o.no, 'Date': new Date(o.date).toLocaleString(), 'Customer': o.name, 'Phone': o.phone, 'WhatsApp': wa ? '+' + wa : '', 'WhatsApp link': wa ? 'https://wa.me/' + wa : '',
        'Gmail': o.email || '', 'Address': o.address, 'Area': o.zone === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka',
        'Items': o.items.map(i => `${i.name} (${[i.color, i.size].filter(Boolean).join('/')}) x${i.qty}`).join(', '),
        'Subtotal': o.sub, 'Delivery': o.ship, 'Total': o.total, 'Payment': o.pay, 'Status': o.status, 'Note': o.note || '' };
    });
    const name = 'rana-shop-orders-' + new Date().toISOString().slice(0, 10) + '.xlsx';
    if (window.XLSX) {
      const ws = XLSX.utils.json_to_sheet(rows);
      ws['!cols'] = Object.keys(rows[0]).map(k => ({ wch: Math.min(50, Math.max(k.length, ...rows.map(r => String(r[k]).length)) + 2) }));
      const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Orders'); XLSX.writeFile(wb, name);
    } else {
      const keys = Object.keys(rows[0]); const q = v => '"' + String(v).replace(/"/g, '""') + '"';
      const csv = '\ufeff' + [keys.map(q).join(','), ...rows.map(r => keys.map(k => q(r[k])).join(','))].join('\r\n');
      const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' })); a.download = name.replace('.xlsx', '.csv'); a.click();
    }
    toast('Excel downloaded');
  }
  function order(no) {
    const os = RS.orders(); const o = os.find(x => x.no === no);
    if (!o) return orders();
    if (!o.seen) { o.seen = true; RS.saveOrders(os); }
    const bd = String(o.phone || '').replace(/\D/g, '').replace(/^0/, '880');
    shell('orders', 'Order ' + o.no, `<div class="form2"><div class="box"><h2>Items</h2><table class="tbl">${o.items.map(i => `<tr><td><img src="${src(i.img)}" alt=""></td><td>${esc(i.name)}<br><small style="color:var(--muted)">${esc([i.color, i.size].filter(Boolean).join(' / '))}</small></td><td>× ${i.qty}</td><td>${money(i.price * i.qty)}</td></tr>`).join('')}</table>
      <div style="max-width:300px;margin:16px 0 0 auto"><div class="row"><span>Subtotal</span><span>${money(o.sub)}</span></div><div class="row"><span>Delivery</span><span>${money(o.ship)}</span></div><div class="row total"><span>Total</span><span>${money(o.total)}</span></div></div></div>
      <div><div class="box"><h2>Status</h2><select id="st">${STATUSES.map(s => `<option ${s === o.status ? 'selected' : ''}>${s}</option>`).join('')}</select><button class="btn block" id="saveSt" style="margin-top:12px">Update status</button></div>
      <div class="box"><h2>Customer</h2><p>${esc(o.name)}<br>${esc(o.phone)}<br>${o.email ? esc(o.email) + ' ✓<br>' : ''}${esc(o.address)}<br>${o.zone === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka'}</p><p style="margin-top:10px"><b style="font-weight:500">Payment:</b> ${esc(o.pay)}</p>${o.note ? `<p><b style="font-weight:500">Note:</b> ${esc(o.note)}</p>` : ''}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px"><a class="btn ghost sm" href="tel:${esc(o.phone)}">📞 Call</a><a class="btn ghost sm" target="_blank" href="https://wa.me/${bd}?text=${encodeURIComponent('Assalamu alaikum ' + o.name + ', your Rana Shop order ' + o.no + ' (' + money(o.total) + ') is ' + o.status + '.')}">WhatsApp</a></div></div>
      <div class="box"><button class="btn ghost block" id="printBtn">🖨 Print invoice</button><button class="btn danger block" id="delOrder" style="margin-top:8px">Delete order</button></div></div></div>`,
      `<a class="btn ghost sm" href="#/orders">← All orders</a>`);
    document.getElementById('saveSt').onclick = () => { o.status = document.getElementById('st').value; RS.saveOrders(os); toast('Status updated'); route(); };
    document.getElementById('printBtn').onclick = () => window.print();
    document.getElementById('delOrder').onclick = () => { if (confirm('Delete order ' + o.no + '?')) { RS.saveOrders(os.filter(x => x.no !== o.no)); toast('Order deleted'); location.hash = '#/orders'; } };
  }
  function products() {
    const ps = RS.products();
    shell('products', 'Products', `<div class="box" style="overflow-x:auto"><table class="tbl"><tr><th></th><th>Name</th><th class="hide-m">Category</th><th>Price</th><th class="hide-m">Status</th><th></th></tr>
      ${ps.map(p => `<tr><td><img src="${src(p.img)}" alt=""></td><td><a href="#/edit/${p.id}"><b style="font-weight:500">${esc(p.name)}</b></a>${p.featured ? ' <span class="pill">★ Featured</span>' : ''}${p.landing ? ' <span class="pill">Homepage</span>' : ''}</td>
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
        <button type="button" class="btn ghost sm" id="autoIdea" style="margin-bottom:10px">✨ Auto idea (বাংলা ডেসক্রিপশন বেছে নিন)</button>
        <label>Description<textarea name="desc" rows="3">${esc(d.desc)}</textarea></label>
        <label>Key features <small style="color:var(--muted)">(one per line, Bangla or English. Leave empty to use the default list)</small><textarea name="features" rows="4" placeholder="প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক&#10;নরম ও আরামদায়ক">${esc((d.features || []).join('\n'))}</textarea></label>
        <label>Fabric details <small style="color:var(--muted)">(one per line as Name: value, e.g. Fabric: 100% Cotton)</small><textarea name="fabric" rows="4" placeholder="Fabric: 100% Cotton&#10;GSM: 180-220">${esc((d.fabric || []).map(r => r.join(': ')).join('\n'))}</textarea></label>
        <label>Customer reviews <small style="color:var(--muted)">(only real reviews, one per line as Name | review text. Empty = no review section)</small><textarea name="reviews" rows="3" placeholder="Rafiq, Dhaka | কাপড়ের কোয়ালিটি অসাধারণ!">${esc((d.reviews || []).map(r => r.join(' | ')).join('\n'))}</textarea></label></div>
      <div class="box"><h2>Photo</h2><label class="upl">Click to upload a photo (JPG / PNG)<input type="file" id="file" accept="image/*"></label><img id="prev" class="prev" ${img ? `src="${src(img)}"` : 'style="display:none"'} alt=""></div>
      <div class="box"><h2>Sizes & colours</h2>
        <label>Sizes (separate with commas)<input name="sizes" id="sizes" value="${esc(d.sizes.join(', '))}" placeholder="S, M, L, XL"></label>
        <div class="chips" style="margin:-6px 0 16px"><button type="button" data-z="S, M, L, XL, XXL">Shirt / T-shirt</button><button type="button" data-z="28, 30, 32, 34, 36">Pant</button><button type="button" data-z="39, 40, 41, 42, 43, 44">Shoe</button></div>
        <div style="font-size:.85rem;margin-bottom:6px">Sold out sizes <small style="color:var(--muted)">(tap to mark out of stock)</small></div><div class="chips" id="soChips" style="margin:0 0 16px"></div>
        <div style="font-size:.85rem;margin-bottom:8px">Colours</div><div id="cols"></div><button type="button" class="btn ghost sm" id="addCol">+ Add colour</button></div></div>
      <div><div class="box"><label>Status<select name="status"><option value="active">Active (visible on website)</option><option value="draft" ${d.status === 'draft' ? 'selected' : ''}>Draft (hidden)</option></select></label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="featured" style="width:auto;margin:0" ${d.featured ? 'checked' : ''}> Featured product</label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="landing" style="width:auto;margin:0" ${d.landing ? 'checked' : ''}> Show on homepage (landing page)</label>
        <button class="btn block">${p ? 'Save changes' : 'Add product'}</button>${p ? `<a class="btn ghost block" style="margin-top:8px" href="../#/p/${esc(p.slug)}" target="_blank">View on website</a>` : ''}</div>
      <div class="box"><h2>Price</h2><label>Regular price (৳) *<input name="price" type="number" min="1" required value="${esc(d.price)}"></label>
        <label>Sale price (৳) <small style="color:var(--muted)">(optional)</small><input name="sale" type="number" min="0" value="${esc(d.sale || '')}"></label></div>
      <div class="box"><h2>Category</h2><select name="cat">${RS.CATS.map(c => `<option value="${c.slug}" ${c.slug === d.cat ? 'selected' : ''}>${c.name}</option>`).join('')}</select></div></div></form>`);
    let soldOut = [...(d.soldOut || [])];
    const drawSo = () => { const sz = document.getElementById('sizes').value.split(',').map(s => s.trim()).filter(Boolean); soldOut = soldOut.filter(s => sz.includes(s));
      document.getElementById('soChips').innerHTML = sz.length ? sz.map(s => `<button type="button" class="${soldOut.includes(s) ? 'so' : ''}" data-so="${esc(s)}">${esc(s)}</button>`).join('') : '<small style="color:var(--muted)">Add sizes first</small>';
      root.querySelectorAll('[data-so]').forEach(b => b.onclick = () => { const s = b.dataset.so; soldOut = soldOut.includes(s) ? soldOut.filter(x => x !== s) : [...soldOut, s]; drawSo(); }); };
    document.getElementById('sizes').addEventListener('input', drawSo); drawSo();
    const cols = document.getElementById('cols');
    const addCol = (n = '', h = '#222222') => { const r = document.createElement('div'); r.className = 'color-row'; r.innerHTML = `<input class="cn" placeholder="Colour name" value="${esc(n)}"><input type="color" class="ch" value="${esc(h)}"><button type="button" class="btn ghost sm">✕</button>`; r.querySelector('button').onclick = () => r.remove(); cols.appendChild(r); };
    d.colors.forEach(c => addCol(c[0], c[1]));
    document.getElementById('addCol').onclick = () => addCol();
    // Auto idea: shows several ready-made, nicely written options for the product type; click one to fill the boxes
    const T = {
      shirt: { fb: [['ফ্যাব্রিক', 'প্রিমিয়াম কটন'], ['ফিট', 'রেগুলার'], ['কলার', 'ক্লাসিক'], ['যত্ন', 'ঠান্ডা পানিতে ধুবেন, মাঝারি তাপে ইস্ত্রি করবেন']], v: [
        ['প্রিমিয়াম', 'ক্রিস্প কলার আর মসৃণ ফ্যাব্রিকে তৈরি একটি নিখুঁত শার্ট। অফিসে যেমন মানায়, ছুটির দিনেও তেমনই আরামদায়ক।', ['প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক', 'ক্রিস্প কলার ও নিখুঁত ফিনিশ', 'সারাদিন পরলেও আরামদায়ক', 'অফিস ও পার্টি দুই জায়গায়ই মানানসই']],
        ['ক্যাজুয়াল', 'হালকা ও নরম ফ্যাব্রিকের ইজি শার্ট, পরে সহজেই বের হয়ে যাওয়া যায়। জিন্স বা চিনো দুইয়ের সাথেই দারুণ মানায়।', ['হালকা ও নরম ফ্যাব্রিক', 'রিলাক্সড ক্যাজুয়াল ফিট', 'জিন্স ও চিনোর সাথে পারফেক্ট', 'ঘামে আরাম, গরমে স্বস্তি']],
        ['বিস্তারিত', 'প্রিমিয়াম কটন ফ্যাব্রিকে তৈরি স্টাইলিশ শার্ট। নরম, আরামদায়ক এবং সারাদিন পরার জন্য একদম পারফেক্ট। সেলাই ও ফিনিশিংয়ে বাড়তি যত্ন নেওয়া হয়েছে, ধোয়ার পরও ফিট ও রঙ ঠিক থাকে।', ['১০০% প্রিমিয়াম কটন ফ্যাব্রিক', 'নরম ও আরামদায়ক', 'নিখুঁত সেলাই ও ফিনিশ', 'ধোয়ার পরও ফিট ও রঙ ঠিক থাকে']],
        ['সংক্ষিপ্ত', 'পরিষ্কার কাট, নরম ফ্যাব্রিক, পারফেক্ট ফিট। সব পোশাকের সাথেই মানিয়ে যায়।', ['নরম ফ্যাব্রিক', 'পারফেক্ট ফিট', 'সব পোশাকের সাথে মানানসই']]] },
      denim: { fb: [['ফ্যাব্রিক', 'স্ট্রেচ ডেনিম'], ['ওজন', '১০-১২ oz'], ['ফিট', 'স্ট্রেইট / রিলাক্সড'], ['যত্ন', 'উল্টো করে ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'পরিষ্কার ওয়াশ আর মজবুত সেলাইয়ের ক্লাসিক ডেনিম। সারাদিন পরলেও আকার ঠিক থাকে।', ['প্রিমিয়াম ডেনিম ফ্যাব্রিক', 'মজবুত স্টিচিং, দীর্ঘদিন টেকসই', 'স্টাইলিশ ওয়াশ ফিনিশ', 'সারাদিন পরলেও আরামদায়ক']],
        ['ক্যাজুয়াল', 'ওয়ার্ন-ইন ওয়াশের রিলাক্সড জিন্স, স্ট্রিট লুকের জন্য সেরা। টি-শার্ট, শার্ট বা স্নিকার্স সবকিছুর সাথেই মানায়।', ['রিলাক্সড ফিট, ট্রেন্ডি লুক', 'নরম ওয়াশড ডেনিম', 'টি-শার্ট ও শার্ট দুইয়ের সাথেই মানায়', 'মজবুত পকেট ও জিপার']],
        ['বিস্তারিত', 'উন্নত মানের ডেনিমে তৈরি স্টাইলিশ জিন্স। আরামদায়ক ফিট, মজবুত সেলাই, প্রতিদিনের জন্য পারফেক্ট। রঙ সহজে ফ্যাকাশে হয় না, তাই অনেকদিন নতুনের মতো থাকে।', ['উন্নত মানের ডেনিম', 'আরামদায়ক ফিট', 'মজবুত সেলাই', 'রঙ সহজে ফ্যাকাশে হয় না']],
        ['সংক্ষিপ্ত', 'ভালো ডেনিম, ভালো ফিট, অনেকদিন টেকসই।', ['প্রিমিয়াম ডেনিম', 'পারফেক্ট ফিট', 'টেকসই']]] },
      cargo: { fb: [['ফ্যাব্রিক', 'কটন টুইল'], ['ফিট', 'রিলাক্সড স্ট্রেইট'], ['পকেট', 'সাইড কার্গো পকেট'], ['যত্ন', 'ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'বড় সাইড পকেটসহ রিলাক্সড স্ট্রেইট-লেগ কার্গো। কাজে লাগে, আরামদায়ক, আর সহজে স্টাইল করা যায়।', ['রুমি সাইড কার্গো পকেট', 'রিলাক্সড স্ট্রেইট ফিট', 'মজবুত ফ্যাব্রিক', 'প্রতিদিন পরার জন্য পারফেক্ট']],
        ['ক্যাজুয়াল', 'আধুনিক শেপের রুমি কার্গো। ওভারসাইজড টি-শার্ট আর স্নিকার্সের সাথে পরলে দারুণ লাগে।', ['ট্রেন্ডি ক্যাজুয়াল লুক', 'বড় পকেটে মোবাইল ও জিনিসপত্র রাখা যায়', 'আরামদায়ক ফ্রি মুভমেন্ট', 'ওভারসাইজড টি-শার্টের সাথে দারুণ']],
        ['বিস্তারিত', 'আরামদায়ক রিলাক্সড ফিটের কার্গো প্যান্ট। বড় সাইড পকেট ও মজবুত ফ্যাব্রিকে তৈরি, রোজকার পরার জন্য সেরা। ঘোরাঘুরি, আড্ডা বা আউটিং সব জায়গায় মানানসই।', ['বড় সাইড পকেট', 'মজবুত ও টেকসই ফ্যাব্রিক', 'আরামদায়ক ফিট', 'ক্যাজুয়াল ও আউটিংয়ে মানানসই']],
        ['সংক্ষিপ্ত', 'বড় পকেট, রিলাক্সড ফিট, সহজে স্টাইল করা যায়।', ['কার্গো পকেট', 'রিলাক্সড ফিট', 'মজবুত ফ্যাব্রিক']]] },
      pant: { fb: [['ফ্যাব্রিক', 'কটন ব্লেন্ড'], ['ফিট', 'রিলাক্সড'], ['ওয়েস্ট', 'কমফোর্ট ওয়েস্ট'], ['যত্ন', 'ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'পরিষ্কার ড্রেপ আর প্রেসড ফ্রন্ট ক্রিজের আরামদায়ক প্যান্ট। দেখতে স্মার্ট, পরতে হালকা।', ['নরম ও আরামদায়ক ফ্যাব্রিক', 'পরিষ্কার ড্রেপ ও ফিনিশ', 'কমফোর্টেবল ওয়েস্ট', 'অফিস ও ক্যাজুয়াল দুই জায়গায়ই মানানসই']],
        ['ক্যাজুয়াল', 'নরম ফ্যাব্রিকের আরামদায়ক ওয়াইড-লেগ প্যান্ট, চলাফেরায় একদম ঝামেলা নেই।', ['ওয়াইড লেগ রিলাক্সড ফিট', 'ড্রস্ট্রিং ওয়েস্ট, সহজে পরা যায়', 'হালকা ও নরম ফ্যাব্রিক', 'গরমেও আরামদায়ক']],
        ['বিস্তারিত', 'নরম ও টেকসই ফ্যাব্রিকের স্টাইলিশ প্যান্ট। আরামদায়ক ফিট, নিখুঁত সেলাই, সারাদিন পরার জন্য পারফেক্ট। সহজে ইস্ত্রি ও যত্ন করা যায়।', ['নরম ও টেকসই ফ্যাব্রিক', 'আরামদায়ক ফিট', 'নিখুঁত সেলাই', 'সহজে ইস্ত্রি ও যত্ন']],
        ['সংক্ষিপ্ত', 'পরিষ্কার ড্রেপ, নরম ফ্যাব্রিক, সারাদিন আরাম।', ['নরম ফ্যাব্রিক', 'আরামদায়ক ফিট', 'মজবুত সেলাই']]] },
      polo: { fb: [['ফ্যাব্রিক', '১০০% কটন পিকে'], ['GSM', '২০০-২২০'], ['ফিট', 'রেগুলার'], ['যত্ন', 'ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'নিট কলার আর মসৃণ, ব্রিদেবল ফিলের নরম কটন পিকে পোলো। যেকোনো দিনের জন্য ক্লিন লুক।', ['প্রিমিয়াম কটন পিকে ফ্যাব্রিক', 'নিট কলার, পরিষ্কার ফিনিশ', 'ব্রিদেবল ও নরম', 'ক্যাজুয়াল ও সেমি-ফর্মাল দুইভাবেই পরা যায়']],
        ['ক্যাজুয়াল', 'রোজকার জন্য ইজি পোলো। হালকা, নরম, জিন্স বা ট্রাউজারের সাথে ভালো মানায়।', ['হালকা ও আরামদায়ক', 'রঙ ফ্যাকাশে হয় না', 'জিন্স ও ট্রাউজারের সাথে মানায়', 'গরমে স্বস্তি']],
        ['বিস্তারিত', 'প্রিমিয়াম কটনে তৈরি স্টাইলিশ পোলো শার্ট। নরম, আরামদায়ক এবং দেখতে ক্লাসি। ধোয়ার পরও আকার ও রঙ ঠিক থাকে।', ['১০০% কটন ফ্যাব্রিক', 'নরম ও আরামদায়ক', 'ক্লাসি লুক', 'ধোয়ার পরও আকার ঠিক থাকে']],
        ['সংক্ষিপ্ত', 'নরম পিকে, নিট কলার, পরতে আরাম।', ['কটন ফ্যাব্রিক', 'নিট কলার', 'আরামদায়ক ফিট']]] },
      tee: { fb: [['ফ্যাব্রিক', '১০০% কটন'], ['GSM', '১৮০-২২০'], ['ফিট', 'রেগুলার / ওভারসাইজড'], ['যত্ন', 'উল্টো করে ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'ভারী কটনের টি-শার্ট, নরম হ্যান্ড-ফিল, পরিষ্কার নেকলাইন আর আরামদায়ক রিলাক্সড ফিট।', ['প্রিমিয়াম কটন ফ্যাব্রিক', 'নরম ও আরামদায়ক', 'ওয়াশ করলেও প্রিন্ট ঠিক থাকে', 'ঘাম শোষণ করে, গরমে স্বস্তি']],
        ['ক্যাজুয়াল', 'বোল্ড প্রিন্ট আর ড্রপ-শোল্ডার শেপের ওভারসাইজড টি-শার্ট। সহজ স্ট্রিটওয়্যার, সবকিছুর সাথে মানায়।', ['ওভারসাইজড ড্রপ শোল্ডার ফিট', 'বোল্ড প্রিন্ট', 'ট্রেন্ডি স্ট্রিট লুক', 'জিন্স ও কার্গোর সাথে দারুণ']],
        ['বিস্তারিত', 'উন্নত মানের কটনে তৈরি আরামদায়ক টি-শার্ট। নরম ফ্যাব্রিক, নিখুঁত ফিট, রোজকার পরার জন্য সেরা। ধোয়ার পরও রঙ ও প্রিন্ট ঠিক থাকে।', ['উন্নত মানের কটন', 'নরম ও আরামদায়ক', 'নিখুঁত ফিট', 'ধোয়ার পরও রঙ ঠিক থাকে']],
        ['সংক্ষিপ্ত', 'নরম কটন, পরিষ্কার ফিট, প্রতিদিনের জন্য।', ['কটন ফ্যাব্রিক', 'নরম ও আরামদায়ক', 'পারফেক্ট ফিট']]] },
      panjabi: { fb: [['ফ্যাব্রিক', 'নরম কটন / ভিসকস ব্লেন্ড'], ['ফিট', 'রেগুলার'], ['ডিটেইলিং', 'এমব্রয়ডারি'], ['যত্ন', 'হালকা ধোয়া, ছায়ায় শুকাবেন']], v: [
        ['প্রিমিয়াম', 'সূক্ষ্ম কাজ আর নরম ঝোলের মার্জিত পাঞ্জাবি। ঈদ, বিয়ে আর প্রতিটি বিশেষ দিনের জন্য।', ['প্রিমিয়াম কোয়ালিটি ফ্যাব্রিক', 'সুন্দর এমব্রয়ডারি ডিটেইলস', 'নরম ও আরামদায়ক', 'ঈদ, বিয়ে ও যেকোনো অনুষ্ঠানে মানানসই']],
        ['ক্যাজুয়াল', 'জুমা ও পারিবারিক অনুষ্ঠানের জন্য হালকা, আরামদায়ক পাঞ্জাবি। সিম্পল, পরিপাটি আর ক্লাসি।', ['হালকা ও আরামদায়ক', 'সিম্পল ও ক্লাসি ডিজাইন', 'শুক্রবার ও পারিবারিক অনুষ্ঠানে মানানসই', 'নিখুঁত সেলাই']],
        ['বিস্তারিত', 'নরম ও আরামদায়ক ফ্যাব্রিকে তৈরি স্টাইলিশ পাঞ্জাবি। সুন্দর কাজ ও নিখুঁত ফিনিশ, উৎসব ও অনুষ্ঠানের জন্য পারফেক্ট। সারাদিন পরলেও গায়ে অস্বস্তি হয় না।', ['নরম ও আরামদায়ক ফ্যাব্রিক', 'সুন্দর হাতের কাজ', 'নিখুঁত ফিনিশ', 'উৎসব ও অনুষ্ঠানে মানানসই']],
        ['সংক্ষিপ্ত', 'সূক্ষ্ম কাজ, নরম ফ্যাব্রিক, ক্লাসি লুক।', ['নরম ফ্যাব্রিক', 'সুন্দর ডিজাইন', 'আরামদায়ক ফিট']]] },
      jacket: { fb: [['ফ্যাব্রিক', 'কটন টুইল'], ['ফিট', 'রিলাক্সড'], ['ক্লোজার', 'বোতাম / জিপ'], ['যত্ন', 'ঠান্ডা পানিতে ধুবেন']], v: [
        ['প্রিমিয়াম', 'লেয়ারিংয়ের জন্য তৈরি আউটার, ভালো ফ্যাব্রিক ওজন আর মজবুত গড়ন। টি-শার্ট বা শার্টের উপর পরে নিন।', ['স্টাইলিশ লেয়ারিং লুক', 'মজবুত ও টেকসই ফ্যাব্রিক', 'আরামদায়ক ফিট', 'হালকা ঠান্ডায় উপযোগী']],
        ['ক্যাজুয়াল', 'আধুনিক শেপের রিলাক্সড আউটার। টি-শার্ট, শার্ট বা হুডির উপর সহজে স্টাইল করা যায়।', ['রিলাক্সড ফিট', 'ট্রেন্ডি লুক', 'টি-শার্ট ও শার্টের উপর দারুণ', 'মজবুত পকেট']],
        ['বিস্তারিত', 'মজবুত ও আরামদায়ক ফ্যাব্রিকে তৈরি স্টাইলিশ জ্যাকেট। শীতের সকালে বা হালকা ঠান্ডায় পরার জন্য পারফেক্ট। ভেতরে যা-ই পরুন, লুক ঠিক থাকে।', ['মজবুত ও টেকসই ফ্যাব্রিক', 'আরামদায়ক ফিট', 'স্টাইলিশ ডিজাইন', 'হালকা শীতের জন্য উপযোগী']],
        ['সংক্ষিপ্ত', 'মজবুত গড়ন, আরামদায়ক, লেয়ারিংয়ের জন্য সহজ।', ['মজবুত ফ্যাব্রিক', 'আরামদায়ক ফিট', 'স্টাইলিশ লুক']]] },
      shoes: { fb: [['আপার', 'সিনথেটিক লেদার / মেশ'], ['সোল', 'রাবার'], ['ইনসোল', 'কুশন'], ['যত্ন', 'ভেজা কাপড় দিয়ে মুছবেন']], v: [
        ['প্রিমিয়াম', 'কুশন সোল, শক্ত গ্রিপ আর সব পোশাকের সাথে মানানোর মতো ক্লিন লুকের আরামদায়ক রোজকার জুতা।', ['আরামদায়ক কুশন সোল', 'মজবুত গ্রিপ, পিছলে যায় না', 'হালকা ওজন, সারাদিন পরার উপযোগী', 'সব পোশাকের সাথে মানানসই']],
        ['ক্যাজুয়াল', 'প্রিমিয়াম ফিনিশের ক্লিন লো-প্রোফাইল স্নিকার্স। জিন্স, কার্গো বা শর্টসের সাথে দারুণ লাগে।', ['ক্লিন ও প্রিমিয়াম লুক', 'নরম ইনসোল, পায়ে আরাম', 'টেকসই রাবার সোল', 'জিন্স ও কার্গোর সাথে দারুণ']],
        ['বিস্তারিত', 'আরামদায়ক ও টেকসই জুতা। নরম সোল, মজবুত গ্রিপ এবং স্টাইলিশ লুক, রোজকার ব্যবহারের জন্য পারফেক্ট। সারাদিন হাঁটলেও পায়ে ব্যথা হয় না।', ['নরম কুশন সোল', 'মজবুত গ্রিপ', 'হালকা ওজন', 'স্টাইলিশ ও টেকসই']],
        ['সংক্ষিপ্ত', 'হালকা, আরামদায়ক, ক্লিন লুক।', ['আরামদায়ক সোল', 'হালকা ওজন', 'টেকসই']]] },
    };
    const pf = document.getElementById('pf');
    const typeOf = () => { const nm = pf.elements.name.value.toLowerCase(), cat = pf.elements.cat.value;
      if (/cargo/.test(nm)) return 'cargo'; if (/jean|denim (pant|trouser)/.test(nm)) return 'denim'; if (/panjabi|punjabi|kurta/.test(nm)) return 'panjabi'; if (/polo/.test(nm)) return 'polo';
      if (/overshirt|jacket|coat|hoodie/.test(nm)) return 'jacket'; if (/tee|t-shirt|tshirt|graphic/.test(nm)) return 'tee'; if (/sneaker|shoe|loafer|sandal|boot/.test(nm)) return 'shoes';
      return { shirts: 'shirt', pants: 'pant', 't-shirts': 'tee', shoes: 'shoes' }[cat] || 'shirt'; };
    const apply = (t, i) => { const v = T[t].v[i]; pf.elements.desc.value = v[1]; pf.elements.features.value = v[2].join('\n'); pf.elements.fabric.value = T[t].fb.map(r => r.join(': ')).join('\n'); };
    const box = document.createElement('div'); box.id = 'ideaBox'; box.hidden = true; document.getElementById('autoIdea').after(box);
    const showIdeas = () => { const t = typeOf(); box.hidden = false;
      box.innerHTML = '<p style="margin:0 0 8px;font-size:.85rem;color:var(--muted)">একটি বেছে নিন, Description, Key features আর Fabric নিজে থেকেই ভরে যাবে। পরে চাইলে এডিট করতে পারবেন।</p>' + T[t].v.map((v, i) => `<div style="border:1px solid #ddd;border-radius:8px;padding:10px 12px;margin-bottom:8px;background:#fff"><b style="font-size:.85rem">${esc(v[0])}</b><p style="margin:4px 0;font-size:.88rem">${esc(v[1])}</p><small style="color:var(--muted)">${v[2].map(esc).join(' · ')}</small><div style="margin-top:6px"><button type="button" class="btn sm" data-use="${i}">এটি ব্যবহার করুন</button></div></div>`).join('');
      box.querySelectorAll('[data-use]').forEach(b => b.onclick = () => { apply(t, +b.dataset.use); box.hidden = true; }); };
    document.getElementById('autoIdea').onclick = showIdeas;
    // on a new product, show the options once the name is typed and the boxes are still empty
    if (!p) { const maybe = () => { if (!['desc', 'features', 'fabric'].some(n => pf.elements[n].value.trim()) && pf.elements.name.value.trim()) showIdeas(); };
      pf.elements.name.addEventListener('blur', maybe); pf.elements.cat.addEventListener('change', maybe); }
    root.querySelectorAll('[data-z]').forEach(b => b.onclick = () => { document.getElementById('sizes').value = b.dataset.z; drawSo(); });
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
      const data = { name, slug, desc: f.get('desc').trim(), price, sale, cat: f.get('cat'), img,
        landing: f.get('landing') ? 1 : 0,
        reviews: f.get('reviews').split('\n').map(x => x.trim()).filter(Boolean).map(x => { const k = x.indexOf('|'); return k >= 0 ? [x.slice(0, k).trim(), x.slice(k + 1).trim()] : ['', x]; }),
        features: f.get('features').split('\n').map(x => x.trim()).filter(Boolean),
        fabric: f.get('fabric').split('\n').map(x => x.trim()).filter(Boolean).map(x => { const k = x.indexOf(':'); return k > 0 ? [x.slice(0, k).trim(), x.slice(k + 1).trim()] : ['', x]; }), status: f.get('status'), featured: f.get('featured') ? 1 : 0,
        sizes: f.get('sizes').split(',').map(s => s.trim()).filter(Boolean), soldOut, colors: [...cols.children].map(r => [r.querySelector('.cn').value.trim(), r.querySelector('.ch').value]).filter(c => c[0]) };
      if (data.landing) list.forEach(x => { x.landing = 0; });
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
