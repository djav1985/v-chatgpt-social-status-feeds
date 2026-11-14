<?php
/** @var array<string, mixed> $userStats */
require __DIR__ . '/layouts/header.php';
$csrf = htmlspecialchars((string) App\Core\SessionManager::getInstance()->get('csrf_token', ''), ENT_QUOTES, 'UTF-8');
$stats = $userStats ?? [];
?>
<main class="page-body"><div class="container-xl">
  <div class="page-header"><div><div class="page-pretitle">Administration</div><h1 class="page-title">Accounts</h1></div></div>
  <div class="row row-cards align-items-start">
    <section class="col-12 col-lg-4"><div class="card"><div class="card-header"><h2 class="card-title">Manage accounts</h2></div><div class="card-body">
      <form class="form-grid" action="/accounts" method="post">
        <label for="account">Account name</label><input class="form-control" name="account" id="account" required>
        <label for="platform">Platform</label><select class="form-select" name="platform" id="platform" required><?php foreach (['facebook' => 'Facebook','twitter' => 'Twitter','instagram' => 'Instagram','google-business' => 'Google Business'] as $value => $label) :
            ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php
                                                                                                                 endforeach ?></select>
        <label for="add-prompt">Prompt</label><textarea class="form-control" rows="4" name="prompt" id="add-prompt" required>Create a compelling status update...</textarea>
        <label for="link">Link</label><input class="form-control" type="url" name="link" id="link" value="https://domain.com" required>
        <label for="days">Days</label><select class="form-select" name="days[]" id="days" multiple required><?php foreach (($daysOptions ?? []) as $day) :
            ?><option value="<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($day), ENT_QUOTES, 'UTF-8') ?></option><?php
                                                                                                            endforeach ?></select>
        <label for="cron">Post schedule</label><select class="form-select" name="cron[]" id="cron" multiple required><?php foreach (($cronOptions ?? []) as $option) :
            ?><option value="<?= htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8') ?>"<?= $option['value'] === 'null' ? ' selected' : '' ?>><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></option><?php
                                                                                                                     endforeach ?></select>
        <label for="hashtags">Include hashtags</label><select class="form-select" name="hashtags" id="hashtags"><option value="0" selected>No</option><option value="1">Yes</option></select>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button class="btn btn-success" name="edit_account">Add or update account</button>
        <button class="btn btn-secondary" id="cancel-account-update" type="button" hidden>Cancel update</button>
      </form></div><div class="card-footer"><strong>Summary</strong><ul class="mb-0"><li>Total accounts: <?= htmlspecialchars((string)($stats['totalAccounts'] ?? 0), ENT_QUOTES, 'UTF-8') ?></li><li>API calls: <?= htmlspecialchars((string)($stats['usedApiCalls'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($stats['maxApiCallsLabel'] ?? 'Off'), ENT_QUOTES, 'UTF-8') ?></li><li>Expires: <?= htmlspecialchars((string)($stats['expiresLabel'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></li></ul></div></div></section>
    <section class="col-12 col-lg-8"><div class="card"><div class="card-header"><h2 class="card-title">Account list</h2></div><div class="card-body"><div class="row row-cards">
      <?php foreach (($accountList ?? []) as $account) :
            $name = (string)$account->account;
            $days = explode(',', $account->days);
            $cron = array_values(array_filter(explode(',', $account->cron), 'is_numeric'));
            $times = $cron ? array_map(function ($v) {
                $h = (int)$v;
                return ($h % 12 ?: 12) . ' ' . ($h < 12 ? 'am' : 'pm');
            }, $cron) : ['Off']; ?>
      <div class="col-12 col-md-6"><article class="card h-100"><div class="card-body"><h3 class="h3">#<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3><p><strong>Prompt:</strong> <?= htmlspecialchars($account->prompt, ENT_QUOTES, 'UTF-8') ?></p><p><strong>Days:</strong> <?= htmlspecialchars(implode(', ', array_map('ucfirst', array_map('trim', $days))), ENT_QUOTES, 'UTF-8') ?></p><p><strong>Times:</strong> <?= htmlspecialchars(implode(', ', $times), ENT_QUOTES, 'UTF-8') ?></p><p class="text-break"><strong>Link:</strong> <a href="<?= htmlspecialchars($account->link, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($account->link, ENT_QUOTES, 'UTF-8') ?></a></p></div><div class="card-footer d-flex flex-wrap gap-2">
      <button class="btn btn-primary account-update" type="button" data-account-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" data-prompt="<?= htmlspecialchars($account->prompt, ENT_QUOTES, 'UTF-8') ?>" data-link="<?= htmlspecialchars($account->link, ENT_QUOTES, 'UTF-8') ?>" data-hashtags="<?= (int)$account->hashtags ?>" data-cron="<?= htmlspecialchars(implode(',', $cron), ENT_QUOTES, 'UTF-8') ?>" data-days="<?= htmlspecialchars(implode(',', array_map('trim', $days)), ENT_QUOTES, 'UTF-8') ?>" data-platform="<?= htmlspecialchars($account->platform, ENT_QUOTES, 'UTF-8') ?>">Update</button>
      <form action="/accounts" method="post" class="m-0"><input type="hidden" name="account" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button class="btn btn-danger" name="delete_account">Delete</button></form></div></article></div>
      <?php endforeach ?>
      <?php if (empty($accountList)) :
            ?><div class="col-12"><div class="empty"><p class="empty-title">No accounts yet</p></div></div><?php
      endif ?>
    </div></div></div></section>
    <section class="col-12"><div class="card"><div class="card-header"><h2 class="card-title">Weekly schedule</h2></div><div class="card-body"><div class="calendar-grid">
      <?php foreach (($calendarOverview['days'] ?? []) as $day) :
            ?><section class="calendar-day"><h3><?= htmlspecialchars(ucfirst($day), ENT_QUOTES, 'UTF-8') ?></h3><?php foreach (['morning','afternoon','night'] as $slot) :
    ?><div class="calendar-slot"><strong><?= htmlspecialchars(ucfirst($slot), ENT_QUOTES, 'UTF-8') ?></strong><?php $entries = $calendarOverview['schedule'][$day][$slot] ?? []; if (!$entries) :
    ?><p class="text-secondary mb-1">—</p><?php
    else :
        ?><ul><?php foreach ($entries as $entry) :
    ?><li><?= htmlspecialchars($entry['time'] . ': ' . $entry['account'], ENT_QUOTES, 'UTF-8') ?></li><?php
        endforeach ?></ul><?php
    endif ?></div><?php
            endforeach ?></section><?php
      endforeach ?>
    </div></div></div></section>
  </div>
</div></main><script defer src="/assets/js/admin-forms.js"></script>
<?php require __DIR__ . '/layouts/footer.php'; ?>
