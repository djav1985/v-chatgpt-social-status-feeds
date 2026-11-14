<?php
/** @var array{systemMsg?: string, who?: string, where?: string, what?: string, goal?: string, hasProfile?: bool} $systemMsg */
require __DIR__ . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrf = $escape(App\Core\SessionManager::getInstance()->get('csrf_token', ''));
$username = (string) App\Core\SessionManager::getInstance()->get('username', '');
?>
<main class="page-body">
  <div class="container-xl">
    <div class="page-header"><div class="page-pretitle">Account</div><h1 class="page-title">My Info</h1></div>
    <div class="row g-3">
      <section class="col-12 col-lg-8">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Your profile</h2></div>
          <div class="card-body">
            <form class="form-grid" method="post" id="update-profile-form"
              data-who="<?= $escape($systemMsg['who'] ?? '') ?>"
              data-where="<?= $escape($systemMsg['where'] ?? '') ?>"
              data-what="<?= $escape($systemMsg['what'] ?? '') ?>"
              data-goal="<?= $escape($systemMsg['goal'] ?? '') ?>">
              <label for="who">Who</label>
              <input
                class="form-control"
                type="text"
                name="who"
                id="who"
                placeholder="We are... (name)"
                maxlength="50"
                required
              >
              <label for="where">Where</label>
              <input
                class="form-control"
                type="text"
                name="where"
                id="where"
                placeholder="We are located in..."
                maxlength="50"
                required
              >
              <label for="what">What</label>
              <textarea
                class="form-control"
                rows="5"
                name="what"
                id="what"
                placeholder="We... (build things)"
                maxlength="1024"
                required
              ></textarea>
              <label for="goal">Goal</label>
              <textarea
                class="form-control"
                rows="5"
                name="goal"
                id="goal"
                placeholder="Our goal is..."
                maxlength="500"
                required
              ></textarea>
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="accountOwner" value="<?= $escape($username) ?>">
              <button class="btn btn-success" type="submit" name="update_profile">Update profile</button>
            </form>
          </div>
        </div>
      </section>
      <section class="col-12 col-lg-4">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Change password</h2></div>
          <div class="card-body">
            <form class="form-grid" method="post">
              <label for="username">Username</label>
              <input class="form-control" type="text" id="username" value="<?= $escape($username) ?>" readonly>
              <label for="password">New password</label>
              <input
                class="form-control"
                type="password"
                name="password"
                id="password"
                autocomplete="new-password"
                required
              >
              <label for="password2">Confirm new password</label>
              <input
                class="form-control"
                type="password"
                name="password2"
                id="password2"
                autocomplete="new-password"
                required
              >
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <button class="btn btn-primary" type="submit" name="change_password">Change password</button>
            </form>
          </div>
        </div>
      </section>
      <section class="col-12">
        <div class="card">
          <div class="card-header"><h2 class="card-title">System message</h2></div>
          <div class="card-body">
            <p><strong class="text-primary"><?= $escape($systemMsg['systemMsg'] ?? '') ?></strong>
              <?php if (!empty($systemMsg['hasProfile'])) : ?>
                You work for <?= $escape($systemMsg['who'] ?? '') ?> located in
                    <?= $escape($systemMsg['where'] ?? '') ?>.
                    <?= $escape($systemMsg['what'] ?? '') ?> Your goal is <?= $escape($systemMsg['goal'] ?? '') ?>.
              <?php endif ?>
            </p>
          </div>
        </div>
      </section>
    </div>
  </div>
</main>
<script defer src="/assets/js/admin-forms.js"></script>
<?php require __DIR__ . '/layouts/footer.php'; ?>
