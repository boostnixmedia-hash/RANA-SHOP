<section <?= sec_attr($s, 'news') ?> id="<?= $sid ?>"><div class="wrap news-in reveal">
  <?php if (!empty($s['heading'])): ?><h2><?= e($s['heading']) ?></h2><?php endif ?>
  <?php if (!empty($s['subtitle'])): ?><p><?= e($s['subtitle']) ?></p><?php endif ?>
  <form class="news-form" method="post" action="<?= e(url('newsletter')) ?>" data-ajax>
    <?= csrf_field() ?><input type="email" name="email" placeholder="Your email address" required aria-label="Email"><button class="btn"><?= e($s['button_text'] ?? 'Subscribe') ?></button>
  </form>
</div></section>
