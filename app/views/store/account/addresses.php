<section class="wrap acct"><h1>Addresses</h1><div class="acc-grid"><?php include __DIR__ . '/_nav.php'; ?>
  <div>
    <div class="addr-grid">
    <?php foreach ($addresses as $a): ?>
      <div class="addr-card"><b><?= e($a['label']) ?></b><?= $a['is_default'] ? ' <span class="badge">Default</span>' : '' ?>
        <p><?= e($a['name']) ?> · <?= e($a['phone']) ?><br><?= e($a['address']) ?><br><?= e($a['area']) ?>, <?= e($a['district']) ?></p>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <?php if (!$a['is_default']): ?><button name="action" value="default" class="link small">Make default</button><?php endif ?>
          <button name="action" value="delete" class="link small danger" onclick="return confirm('Delete this address?')">Delete</button></form>
      </div>
    <?php endforeach ?>
    </div>
    <h3>Add a new address</h3>
    <form method="post" class="form card-form"><?= csrf_field() ?><input type="hidden" name="action" value="save">
      <div class="f2"><label>Label<input name="label" value="Home"></label><label>Recipient name<input name="name" required value="<?= e($c['name']) ?>"></label></div>
      <div class="f2"><label>Mobile<input name="phone" type="tel" required value="<?= e($c['phone']) ?>"></label><label>District<select name="district" required><option value="">Select</option><?php foreach (bd_districts() as $d): ?><option><?= e($d) ?></option><?php endforeach ?></select></label></div>
      <label>Area / Thana<input name="area" required></label>
      <label>Full address<textarea name="address" rows="2" required></textarea></label>
      <button class="btn">Save address</button>
    </form>
  </div>
</div></section>
