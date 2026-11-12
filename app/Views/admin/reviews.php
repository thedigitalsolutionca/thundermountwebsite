<?php use App\Core\Csrf; ?>
<div class="admin-head">
  <div><h2>Reviews</h2><p>Only approved reviews appear on the storefront.</p></div>
</div>
<div class="admin-tabs">
  <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="<?= url('/admin/reviews?status=pending') ?>">Pending <span><?= (int) $counts['pending'] ?></span></a>
  <a class="<?= $status === 'approved' ? 'active' : '' ?>" href="<?= url('/admin/reviews?status=approved') ?>">Approved <span><?= (int) $counts['approved'] ?></span></a>
  <a class="<?= $status === 'rejected' ? 'active' : '' ?>" href="<?= url('/admin/reviews?status=rejected') ?>">Rejected <span><?= (int) $counts['rejected'] ?></span></a>
  <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= url('/admin/reviews?status=') ?>">All</a>
</div>

<?php if ($reviews === []): ?>
  <div class="admin-card"><div class="admin-empty"><?= icon('star') ?><p>No reviews in this view.</p></div></div>
<?php else: ?>
  <div class="stack">
    <?php foreach ($reviews as $review): ?>
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <h3><?= e($review['title'] ?: 'Review') ?> <?= star_rating((float) $review['rating']) ?></h3>
            <span><?= e($review['name']) ?> on <a href="<?= url('/product/' . urlencode($review['product_slug'])) ?>" target="_blank" rel="noopener"><?= e($review['product_name']) ?></a> · <?= e(fmt_datetime($review['created_at'])) ?></span>
          </div>
          <div class="flex" style="gap:6px">
            <span class="badge <?= $review['status'] === 'approved' ? 'badge-green' : ($review['status'] === 'rejected' ? 'badge-red' : 'badge-amber') ?>"><?= e(ucfirst($review['status'])) ?></span>
            <?php if ((int) $review['verified_purchase']): ?><span class="badge badge-blue">Verified</span><?php endif; ?>
          </div>
        </div>
        <div class="admin-card-body">
          <p class="small"><?= nl2br(e($review['body'])) ?></p>
          <div class="flex" style="gap:6px">
            <?php if ($review['status'] !== 'approved'): ?>
              <form action="<?= url('/admin/reviews/' . (int) $review['id'] . '/status?from=' . urlencode($status)) ?>" method="post">
                <?= csrf_field() ?><input type="hidden" name="status" value="approved">
                <button class="btn btn-xs btn-dark" type="submit">Approve</button>
              </form>
            <?php endif; ?>
            <?php if ($review['status'] !== 'rejected'): ?>
              <form action="<?= url('/admin/reviews/' . (int) $review['id'] . '/status?from=' . urlencode($status)) ?>" method="post">
                <?= csrf_field() ?><input type="hidden" name="status" value="rejected">
                <button class="btn btn-xs btn-outline" type="submit">Reject</button>
              </form>
            <?php endif; ?>
            <form action="<?= url('/admin/reviews/' . (int) $review['id'] . '/delete') ?>" method="post" data-confirm="Delete this review permanently?">
              <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
