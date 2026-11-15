// Shared demo data layer. Everything is kept in this browser (localStorage).
window.RS = (function () {
  const CATS = [
    { slug: 'shirts', name: 'Shirts', img: 'img/photo-overshirt.webp' },
    { slug: 'pants', name: 'Pants', img: 'img/photo-pant.webp' },
    { slug: 't-shirts', name: 'T-Shirts', img: 'img/photo-tee.webp' },
    { slug: 'shoes', name: 'Shoes', img: 'img/photo-shoe.webp' },
  ];
  const ls = {
    get(k, d) { try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); return true; } catch (e) { alert('Browser storage is full. Try a smaller photo.'); return false; } },
  };
  function products() {
    const saved = ls.get('rs_products', null);
    if (!saved) return window.SEED.map(p => ({ ...p }));
    // older saved lists pointed at drawings that were replaced by photos
    return saved.map(p => { const s = window.SEED.find(x => x.id === p.id); return s && /^img\/p\d+-0\.svg$/.test(p.img) ? { ...p, img: s.img, name: s.name, slug: s.slug } : p; });
  }
  function saveProducts(list) { return ls.set('rs_products', list); }
  function slugify(s) { return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'item'; }
  function money(n) { return '৳' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); }
  function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
  function now(p) { return p.sale && p.sale < p.price ? p.sale : p.price; }
  // Email verification codes are sent with EmailJS (free plan). Fill these 3 values from emailjs.com to turn on real emails.
  const EMAILJS = { publicKey: '', serviceId: '', templateId: '' };
  return {
    EMAILJS, CATS, ls, products, saveProducts, slugify, money, esc, now,
    cat: s => CATS.find(c => c.slug === s),
    cart: () => ls.get('rs_cart', []), saveCart: c => ls.set('rs_cart', c),
    orders: () => ls.get('rs_orders', []), saveOrders: o => ls.set('rs_orders', o),
    reset() { ['rs_products', 'rs_cart', 'rs_orders'].forEach(k => localStorage.removeItem(k)); },
  };
})();
