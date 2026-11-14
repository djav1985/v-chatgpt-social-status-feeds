<?php
/** @var list<array<string, mixed>> $users */
require __DIR__ . '/layouts/header.php';
$csrf = htmlspecialchars((string) App\Core\SessionManager::getInstance()->get('csrf_token', ''), ENT_QUOTES, 'UTF-8');
$currentUser = (string) App\Core\SessionManager::getInstance()->get('username', '');
$maxApiCallOptions = [
    '0' => 'Off',
    '30' => '30',
    '90' => '90',
    '300' => '300',
    '9999999999' => 'Unlimited',
];
?>
<main class="page-body">
  <div class="container-xl">
    <div class="page-header"><div class="page-pretitle">Administration</div><h1 class="page-title">Users</h1></div>
    <div class="row g-3">
      <section class="col-12 col-lg-4">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Add or update user</h2></div>
          <div class="card-body">
            <form class="form-grid" action="/users" method="post">
              <label for="username">Username</label>
              <input class="form-control" name="username" id="username" required>
              <label for="password">Password</label>
              <input
                class="form-control"
                type="password"
                name="password"
                id="password"
                autocomplete="new-password"
                placeholder="********"
                aria-describedby="password-help"
              >
              <small id="password-help" class="form-hint">
                Leave blank to keep the current password. New users need a password.
              </small>
              <label for="email">Email</label>
              <input class="form-control" type="email" name="email" id="email" required>
              <label for="total-accounts">Total accounts</label>
              <select class="form-select" name="total-accounts" id="total-accounts" required>
                <?php foreach ([3, 5, 10] as $value) : ?>
                  <option value="<?= $value ?>"><?= $value ?></option>
                <?php endforeach ?>
              </select>
              <label for="max-api-calls">Max API calls</label>
              <select class="form-select" name="max-api-calls" id="max-api-calls" required>
                <?php foreach ($maxApiCallOptions as $value => $label) : ?>
                  <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach ?>
              </select>
              <label for="used-api-calls">Used API calls</label>
              <select class="form-select" name="used-api-calls" id="used-api-calls" required>
                <option value="0">0</option>
              </select>
              <label for="expires">Expires</label>
              <input class="form-control" type="date" name="expires" id="expires" required>
              <label for="admin">Admin</label>
              <select class="form-select" name="admin" id="admin" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <button class="btn btn-primary" type="submit" name="edit_users">Add or update user</button>
            </form>
          </div>
        </div>
      </section>
      <section class="col-12 col-lg-8">
        <div class="card">
          <div class="card-header"><h2 class="card-title">User list</h2></div>
          <div class="card-body"><div class="row row-cards">
            <?php foreach ($users as $user) :
                    $username = (string) ($user['username'] ?? '');
                    $maxApiCalls = htmlspecialchars(
                        (string) ($user['max_api_calls'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    $expires = htmlspecialchars((string) ($user['expires'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
              <div class="col-12 col-md-6">
                <article class="card h-100"><div class="card-body">
                  <h3 class="h3 text-break"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h3>
                  <p class="text-break">
                    <strong>Email:</strong>
                    <?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <p>
                    <strong>API calls:</strong>
                    <?= htmlspecialchars((string) ($user['used_api_calls'] ?? 0), ENT_QUOTES, 'UTF-8') ?> /
                    <?= htmlspecialchars((string) ($user['max_api_calls'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <p>
                    <strong>Expires:</strong>
                    <?= htmlspecialchars((string) ($user['expires'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-secondary user-update" type="button"
                      data-username="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                      data-email="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-admin="<?= (int) ($user['admin'] ?? 0) ?>"
                      data-total-accounts="<?= (int) ($user['total_accounts'] ?? 0) ?>"
                      data-max-api-calls="<?= $maxApiCalls ?>"
                      data-used-api-calls="<?= (int) ($user['used_api_calls'] ?? 0) ?>"
                      data-expires="<?= $expires ?>"
                    >Update</button>
                    <?php if ($username !== $currentUser) : ?>
                      <form action="/users" method="post" class="m-0">
                        <input
                          type="hidden"
                          name="username"
                          value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button class="btn btn-secondary" name="login_as">Login as</button>
                      </form>
                    <?php endif ?>
                    <form action="/users" method="post" class="m-0">
                      <input
                        type="hidden"
                        name="username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                      >
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                      <button class="btn btn-danger" name="delete_user">Delete</button>
                    </form>
                  </div>
                </div></article>
              </div>
            <?php endforeach ?>
            <?php if ($users === []) :
                ?><div class="col-12"><p class="text-secondary">No users found.</p></div><?php
            endif ?>
          </div></div>
        </div>
      </section>
    </div>
  </div>
</main>
<script defer src="/assets/js/admin-forms.js"></script>
<?php require __DIR__ . '/layouts/footer.php'; ?>
