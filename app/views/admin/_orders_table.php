<?php if (!$list): ?><p class="muted">No orders yet.</p><?php else: ?>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($list as $o): ?><tr class="<?= $o['seen'] ? '' : 'unseen' ?>" onclick="location.href='<?= e(url('admin/orders/' . $o['id'])) ?>'">
<td><a href="<?= e(url('admin/orders/' . $o['id'])) ?>"><b><?= e($o['order_no']) ?></b></a><?= $o['seen'] ? '' : ' <span class="pill">NEW</span>' ?></td>
<td><?= e($o['name']) ?><br><small class="muted"><?= e($o['phone']) ?> · <?= e($o['district']) ?></small></td>
<td><b><?= money($o['total']) ?></b></td><td><?= e(pay_label($o['payment_method'])) ?><br><span class="st st-<?= e($o['payment_status']) ?>"><?= e(status_label($o['payment_status'])) ?></span></td>
<td><span class="st st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td><td><small><?= date('d M, h:i A', strtotime($o['created_at'])) ?></small></td></tr>
<?php endforeach ?></tbody></table></div><?php endif ?>
