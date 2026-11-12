<?php
/** @var array $reviews */
/** @var array $stats */
/** @var array $product */
?>
<div class="reviews-block" id="reviews">
  <div class="review-summary">
    <div>
      <strong style="font-size:2rem;line-height:1"><?= number_format((float) ($stats['average'] ?? 0), 1) ?></strong>
      <div><?= star_rating((float) ($stats['average'] ?? 0)) ?></div>
      <p class="small muted" style="margin:6px 0 0"><?= (int) ($stats['total'] ?? 0) ?> customer review<?= (int) ($stats['total'] ?? 0) === 1 ? '' : 's' ?></p>
    </div>
    <div class="review-summary-copy">
      <h3 style="margin-bottom:4px">Real feedback from our customers</h3>
      <p class="small muted" style="margin:0">Reviews are verified against paid orders and moderated before publishing.</p>
    </div>
  </div>

  <div class="grid grid-2 mt-3">
    <?php if ($reviews === []): ?>
      <div class="empty" style="grid-column:1/-1;border:1px solid var(--gray-200);border-radius:var(--radius-lg);background:#fff">
        <?= icon('star') ?>
        <h4>No reviews yet</h4>
        <p>Be the first to review this product.</p>
      </div>
    <?php else: ?>
      <?php foreach ($reviews as $review): ?>
        <div class="review-card">
          <div class="flex between">
            <?= star_rating((float) $review['rating']) ?>
            <?php if ((int) $review['verified_purchase']): ?><span class="badge badge-green">Verified purchase</span><?php endif; ?>
          </div>
          <?php if (!empty($review['title'])): ?><h4 style="margin:12px 0 4px"><?= e($review['title']) ?></h4><?php endif; ?>
          <blockquote>“<?= e($review['body']) ?>”</blockquote>
          <div class="who"><strong><?= e($review['name']) ?></strong> · <?= e(fmt_date($review['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (auth()): ?>
    <form action="<?= url('/product/' . $product['slug'] . '/review') ?>" method="post" class="card card-body mt-3" style="max-width:720px">
      <?= csrf_field() ?>
      <h3 style="margin-top:0">Write a review</h3>
      <?php if (error_for('review')): ?><div class="notice notice-error"><?= e(error_for('review')) ?></div><?php endif; ?>
      <div class="form-row">
        <div class="field">
          <label for="review-rating">Rating</label>
          <select class="select" id="review-rating" name="rating" required>
            <option value="5">5 — Excellent</option>
            <option value="4">4 — Good</option>
            <option value="3">3 — Average</option>
            <option value="2">2 — Below average</option>
            <option value="1">1 — Poor</option>
          </select>
        </div>
        <div class="field">
          <label for="review-title">Title</label>
          <input class="input" id="review-title" name="title" required maxlength="190" value="<?= e(old('title')) ?>">
        </div>
      </div>
      <div class="field">
        <label for="review-body">Your review</label>
        <textarea class="textarea" id="review-body" name="body" required minlength="10" maxlength="3000"><?= e(old('body')) ?></textarea>
      </div>
      <p class="tiny muted">Your review is published after moderation. We show your first name and last initial.</p>
      <div><button class="btn btn-dark" type="submit">Submit review</button></div>
    </form>
  <?php else: ?>
    <div class="notice notice-info mt-3"><?= icon('user') ?><div><a href="<?= url('/login?next=' . urlencode(current_path())) ?>" class="bold">Sign in</a> to write a review. Only signed-in customers can review products.</div></div>
  <?php endif; ?>
</div>
