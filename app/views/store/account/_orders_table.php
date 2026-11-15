<?php if (!$orders): ?><div class="empty"><p>No orders yet.</p><a class="btn" href="<?= e(url('shop')) ?>">Start shopping</a></div>
<?php else: ?>
<table class="tbl"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr><td data-l="Order"><b><?= e($o['order_no']) ?></b></td><td data-l="Date"><?= date('d M Y', strtotime($o['created_at'])) ?></td><td data-l="Status"><span class="st st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td><td data-l="Total"><?= money($o['total']) ?></td><td><a class="link" href="<?= e(url('account/orders/' . $o['order_no'])) ?>">View</a></td></tr><?php endforeach ?>
</tbody></table><?php endif ?>
