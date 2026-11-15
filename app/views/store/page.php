<section class="page-head"><div class="wrap narrow"><nav class="crumbs"><a href="<?= e(url('/')) ?>">Home</a><span>/</span><?= e($page['title']) ?></nav><h1><?= e($page['title']) ?></h1></div></section>
<section class="wrap narrow page-body">
  <div class="rte"><?= clean_html((string)$page['body']) ?></div>
  <?php if (!empty($contact_form)): ?>
  <div class="contact-grid">
    <form method="post" class="form card-form">
      <?= csrf_field() ?>
      <div class="f2"><label>Name<input name="name" required value="<?= e(old('name')) ?>"></label><label>Phone<input name="phone" inputmode="tel" value="<?= e(old('phone')) ?>"></label></div>
      <label>Email<input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
      <label>Subject<input name="subject" value="<?= e(old('subject')) ?>"></label>
      <label>Message<textarea name="message" rows="5" required><?= e(old('message')) ?></textarea></label>
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button class="btn">Send message</button>
    </form>
    <div class="contact-info">
      <?php if ($v = setting('contact_phone')): ?><p><?= icon('phone', 18) ?> <a href="tel:<?= e($v) ?>"><?= e($v) ?></a></p><?php endif ?>
      <?php if ($v = setting('whatsapp')): ?><p><?= icon('brand-whatsapp', 18) ?> <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $v)) ?>" target="_blank" rel="noopener">WhatsApp us</a></p><?php endif ?>
      <?php if ($v = setting('contact_email')): ?><p><?= icon('mail', 18) ?> <a href="mailto:<?= e($v) ?>"><?= e($v) ?></a></p><?php endif ?>
      <?php if ($v = setting('address')): ?><p><?= icon('map-pin', 18) ?> <?= e($v) ?></p><?php endif ?>
    </div>
  </div>
  <?php clear_old(); endif ?>
</section>
