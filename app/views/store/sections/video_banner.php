<?php
$yt = '';
if (!empty($s['video_url']) && preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $s['video_url'], $m)) $yt = $m[1];
?>
<section class="hero video h-<?= e($s['height'] ?? 'large') ?> a-center" id="<?= $sid ?>" style="color:<?= e(safe_color($s['color'] ?? '', '#fff')) ?>">
  <div class="hero-bg">
    <?php if (!empty($s['video'])): ?><video autoplay muted loop playsinline preload="metadata" <?= !empty($s['poster']) ? 'poster="' . e(media($s['poster'])) . '"' : '' ?>><source src="<?= e(media($s['video'])) ?>" type="video/mp4"></video>
    <?php elseif ($yt): ?><iframe src="https://www.youtube-nocookie.com/embed/<?= e($yt) ?>?autoplay=1&mute=1&loop=1&controls=0&playlist=<?= e($yt) ?>&playsinline=1" allow="autoplay; encrypted-media" title="Video" loading="lazy"></iframe>
    <?php elseif (!empty($s['poster'])): ?><img src="<?= e(media($s['poster'])) ?>" alt=""><?php endif ?>
  </div>
  <div class="hero-ov" style="opacity:.3"></div>
  <div class="wrap hero-in"><div class="hero-txt reveal">
    <?php if (!empty($s['heading'])): ?><h2 class="h1"><?= e($s['heading']) ?></h2><?php endif ?>
    <?php if (!empty($s['subtitle'])): ?><p><?= e($s['subtitle']) ?></p><?php endif ?>
    <?php if (!empty($s['button_text'])): ?><div class="btns"><a class="btn light" href="<?= e(url($s['button_link'] ?? '/shop')) ?>"><?= e($s['button_text']) ?></a></div><?php endif ?>
  </div></div>
</section>
