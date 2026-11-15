/* Rana Shop admin */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  // select all
  $$('[data-checkall]').forEach((c) => c.addEventListener('change', () => $$('input[name="ids[]"]', c.form).forEach((x) => (x.checked = c.checked))));

  // tiny rich text editor
  $$('.rte-bar').forEach((bar) => {
    const ed = document.getElementById(bar.dataset.for);
    bar.addEventListener('click', (e) => {
      const b = e.target.closest('[data-cmd]'); if (!b) return;
      e.preventDefault(); ed.focus();
      let val = b.dataset.val || null;
      if (b.dataset.cmd === 'createLink') { val = prompt('Link address (https://...)'); if (!val) return; }
      document.execCommand(b.dataset.cmd, false, val);
    });
  });

  const form = $('#pform');
  if (!form) return;
  form.addEventListener('submit', () => { $('#descField').value = $('#desc').innerHTML; });

  // image previews + drag order
  $('#imgInput').addEventListener('change', (e) => {
    const box = $('#imgPreview'); box.innerHTML = '';
    Array.from(e.target.files).forEach((f) => { const d = document.createElement('div'); d.className = 'img-tile new'; const im = document.createElement('img'); im.src = URL.createObjectURL(f); d.appendChild(im); box.appendChild(d); });
    if (e.target.files.length) box.insertAdjacentHTML('afterbegin', '<p class="muted small" style="width:100%">New photos (saved when you click Save):</p>');
  });
  let drag = null;
  $('#imgs').addEventListener('dragstart', (e) => { drag = e.target.closest('.img-tile'); });
  $('#imgs').addEventListener('dragover', (e) => { e.preventDefault(); const t = e.target.closest('.img-tile'); if (t && drag && t !== drag) { const r = t.getBoundingClientRect(); t.parentNode.insertBefore(drag, (e.clientX - r.left) > r.width / 2 ? t.nextSibling : t); } });
  $('#imgs').addEventListener('drop', () => { $$('#imgs .img-tile').forEach((t, i) => { const inp = t.querySelector('input[name^=img_sort]'); if (inp) inp.value = i; }); });

  // variants matrix
  const data = JSON.parse($('#varData').textContent);
  let colors = data.colors.length ? data.colors : [];
  const map = data.map;
  const has = $('#hasVariants'), sizesIn = $('#sizesIn');
  const sizes = () => sizesIn.value.split(',').map((s) => s.trim()).filter(Boolean).filter((v, i, a) => a.indexOf(v) === i);
  function readMatrix() { $$('#matrix tbody tr').forEach((tr) => { map[tr.dataset.key] = { stock: tr.querySelector('.st-in').value, price: tr.querySelector('.pr-in').value, sku: tr.querySelector('.sku-in').value }; }); }
  function drawColors() {
    $('#colorList').innerHTML = colors.map((c, i) => `<div class="color-row" data-i="${i}">
      <input type="color" value="${esc(c.hex || '#cccccc')}" class="c-hex" aria-label="Colour">
      <input class="c-name" value="${esc(c.name)}" placeholder="Colour name, e.g. Black">
      ${data.images.length ? `<select class="c-img" title="Photo for this colour"><option value="">Photo: auto</option>${data.images.map((im, k) => `<option value="${im.id}" ${+c.img === im.id ? 'selected' : ''}>Photo ${k + 1}</option>`).join('')}</select>` : ''}
      <button type="button" class="x" aria-label="Remove colour">✕</button></div>`).join('');
  }
  function drawMatrix() {
    const sz = sizes().length ? sizes() : [''];
    const cs = colors.length ? colors : [{ name: '' }];
    let h = '<thead><tr><th>Colour</th><th>Size</th><th>Stock</th><th>Price (optional)</th><th>SKU (auto)</th></tr></thead><tbody>';
    let n = 0;
    cs.forEach((c) => sz.forEach((s) => {
      const key = c.name + '|' + s, m = map[key] || {};
      h += `<tr data-key="${esc(key)}"><td>${c.hex ? `<i class="sw" style="background:${esc(c.hex)}"></i>` : ''}${esc(c.name || '—')}<input type="hidden" name="v[${n}][color]" value="${esc(c.name)}"><input type="hidden" name="v[${n}][hex]" value="${esc(c.hex || '')}"></td>
        <td>${esc(s || '—')}<input type="hidden" name="v[${n}][size]" value="${esc(s)}"></td>
        <td><input class="st-in" type="number" min="0" name="v[${n}][stock]" value="${esc(m.stock ?? 0)}"></td>
        <td><input class="pr-in" type="number" min="0" step="0.01" name="v[${n}][price]" value="${esc(m.price ?? '')}" placeholder="same"></td>
        <td><input class="sku-in" name="v[${n}][sku]" value="${esc(m.sku ?? '')}" placeholder="auto"></td></tr>`;
      n++;
    }));
    h += `</tbody><tfoot><tr><td colspan="2"><b>Set all stock to</b></td><td><input type="number" min="0" id="allStock" placeholder="e.g. 10"></td><td colspan="2"></td></tr></tfoot>`;
    $('#matrix').innerHTML = h;
    $$('.c-img').forEach((s, i) => { s.name = 'color_img[' + colors[i].name + ']'; });
    $('#allStock').addEventListener('input', (e) => { $$('#matrix .st-in').forEach((x) => (x.value = e.target.value)); });
  }
  function toggle() {
    $('#varBox').hidden = !has.checked; $('#simpleBox').hidden = has.checked;
    $$('#varBox input, #varBox select').forEach((x) => (x.disabled = !has.checked));
    $$('#simpleBox input').forEach((x) => (x.disabled = has.checked));
  }
  $('#colorList').addEventListener('input', (e) => {
    const row = e.target.closest('.color-row'); if (!row) return; readMatrix();
    const c = colors[row.dataset.i], old = c.name;
    if (e.target.classList.contains('c-name')) { c.name = e.target.value; Object.keys(map).forEach((k) => { if (k.startsWith(old + '|')) map[c.name + k.slice(old.length)] = map[k]; }); }
    if (e.target.classList.contains('c-hex')) c.hex = e.target.value;
    drawMatrix(); $$('.c-img').forEach((s, i) => { s.name = 'color_img[' + colors[i].name + ']'; });
  });
  $('#colorList').addEventListener('change', (e) => { if (e.target.classList.contains('c-img')) colors[e.target.closest('.color-row').dataset.i].img = e.target.value; });
  $('#colorList').addEventListener('click', (e) => { if (!e.target.closest('.x')) return; readMatrix(); colors.splice(+e.target.closest('.color-row').dataset.i, 1); drawColors(); drawMatrix(); });
  $('#addColor').addEventListener('click', () => { readMatrix(); colors.push({ name: '', hex: '#111111' }); drawColors(); drawMatrix(); $$('.c-name').pop().focus(); });
  sizesIn.addEventListener('input', () => { readMatrix(); drawMatrix(); });
  $$('[data-sizes]').forEach((b) => b.addEventListener('click', () => { readMatrix(); sizesIn.value = b.dataset.sizes; drawMatrix(); }));
  has.addEventListener('change', toggle);
  drawColors(); drawMatrix(); toggle();
})();
