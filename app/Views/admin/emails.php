<div class="admin-head"><div><h2>Email delivery</h2><p>Inspect failed attempts, retry retained emails, and manage email records.</p></div><div class="flex wrap"><a class="btn btn-outline btn-sm" href="<?= url('/admin/settings?tab=mail') ?>">Email settings &amp; diagnostics</a><a class="btn btn-ghost btn-sm" href="<?= url('/admin/data?type=emails') ?>">Delete email records…</a></div></div>
<div class="admin-tabs"><?php foreach (['failed' => 'Failed', 'sent' => 'Accepted / sent', 'queued' => 'Queued / in progress', 'all' => 'All'] as $key => $label): ?><a class="<?= $status === $key ? 'active' : '' ?>" href="<?= url('/admin/emails?status=' . $key) ?>"><?= e($label) ?></a><?php endforeach; ?></div>
<form method="get" action="<?= url('/admin/emails') ?>" class="admin-filters">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Recipient or subject" aria-label="Search recipient or subject">
  <label for="email-from">From</label><input class="input" id="email-from" type="date" name="from" value="<?= e($from) ?>">
  <label for="email-to">To</label><input class="input" id="email-to" type="date" name="to" value="<?= e($to) ?>">
  <button class="btn btn-dark btn-sm" type="submit">Filter</button>
</form>
<?php if ($error !== ''): ?><div class="admin-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($selected): ?>
<div class="admin-card">
  <div class="admin-card-head"><div><h3>Email #<?= (int) $selected['id'] ?> · <?= e($selected['subject']) ?></h3><span>To <?= e($selected['to_email']) ?> · <?= e(fmt_datetime($selected['created_at'])) ?></span></div><span class="badge <?= $selected['status'] === 'failed' ? 'badge-red' : 'badge-dark' ?>"><?= e($selected['status']) ?></span></div>
  <div class="admin-card-body">
    <?php if ($selected['error']): ?><div class="admin-alert" role="status" style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e($selected['error']) ?></div><?php endif; ?>
    <p class="small muted">“Sent” means the mail server/provider accepted the request, not that it reached the inbox. Check spam, SPF/DKIM, provider activity, and sender-domain verification. After a timeout, verify that the original was not already accepted before resending.</p>
    <details><summary>Stored message preview (plain text, external content disabled)</summary><div class="admin-note mt-2" style="white-space:pre-wrap;max-height:300px;overflow:auto"><?= e($selected['body'] !== null && $selected['body'] !== '' ? html_entity_decode(strip_tags(preg_replace('/<\s*(script|style)\b[^>]*>.*?<\/\1>/is', '', $selected['body']) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Message body is no longer retained; this email cannot be resent.') ?></div></details>
    <?php if ($selected['status'] === 'failed' && trim((string) $selected['body']) !== ''): ?>
    <form action="<?= url('/admin/emails/' . (int) $selected['id'] . '/resend') ?>" method="post" class="mt-3" data-confirm="Resend this failed email to its original recipient using current mail settings?">
      <?= csrf_field() ?><label class="check"><input type="checkbox" name="confirm_resend" value="yes" required><span>I checked provider logs for possible prior acceptance. Send to the original recipient using current mail settings and store reply-to.</span></label>
      <button class="btn btn-primary btn-sm mt-2" type="submit">Retry failed email</button>
    </form>
    <?php endif; ?>
    <?php if ($selected['status'] !== 'queued'): ?><a class="btn btn-ghost btn-sm mt-2" href="<?= url('/admin/data?' . http_build_query(['type' => 'emails', 'record' => $selected['id'], 'before' => date('Y-m-d', strtotime('tomorrow'))])) ?>">Delete this email record…</a><?php endif; ?>
  </div>
</div>
<?php endif; ?>
<div class="admin-card">
<div class="admin-card-head"><div><h3>Delivery log</h3><span><?= (int) $count ?> matching · page <?= (int) $page ?> of <?= (int) $pages ?></span></div></div>
<?php if ($rows === []): ?><div class="admin-empty"><?= icon('mail') ?><p>No email records match these filters.</p></div><?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Recipient / subject</th><th>Status</th><th>Created</th><th>Latest error</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['to_email']) ?></strong><div class="small"><?= e($row['subject']) ?></div></td><td><span class="badge <?= $row['status'] === 'failed' ? 'badge-red' : 'badge-dark' ?>"><?= e($row['status'] === 'sent' ? 'Accepted / sent' : $row['status']) ?></span></td><td class="small"><?= e(fmt_datetime($row['created_at'])) ?></td><td class="small" style="max-width:280px;overflow-wrap:anywhere"><?= e(mb_substr((string) $row['error'], 0, 160)) ?></td><td><a class="btn btn-xs btn-outline" href="<?= url('/admin/emails?' . http_build_query(['status' => $status, 'q' => $q, 'from' => $from, 'to' => $to, 'page' => $page, 'id' => $row['id']])) ?>">Inspect</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</div>
<div class="flex wrap mt-3"><?php foreach (['Previous' => $page - 1, 'Next' => $page + 1] as $label => $number): ?><?php if ($number >= 1 && $number <= $pages): ?><a class="btn btn-outline btn-sm" href="<?= url('/admin/emails?' . http_build_query(['status' => $status, 'q' => $q, 'from' => $from, 'to' => $to, 'page' => $number])) ?>"><?= e($label) ?></a><?php endif; ?><?php endforeach; ?></div>
