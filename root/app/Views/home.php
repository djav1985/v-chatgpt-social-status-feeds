<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: home.php
 * Description: AI Social Status Generator dashboard.
 *
 * @var string $accountOwner Current account owner's username.
 * @var string $csrfToken CSRF token for status actions.
 * @var list<array{name: string, info: mixed, statuses: list<array<string, mixed>>, feedUrl: string}> $accountsData
 */

require __DIR__ . '/layouts/header.php';

$accountOwner = (string) $accountOwner;
$csrfToken = (string) $csrfToken;
$accountOwnerEsc = htmlspecialchars($accountOwner, ENT_QUOTES, 'UTF-8');
$csrfTokenEsc = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');

?>
<main class="page-body">
    <div class="container-xl">
        <div class="page-header">
            <div class="page-pretitle">Status campaigns</div>
            <h1 class="page-title">Recent statuses</h1>
        </div>

        <?php if ($accountsData === []) : ?>
            <section class="card empty-state">
                <div class="card-body">
                    <h2 class="h3">Please set up an account</h2>
                    <p class="text-secondary mb-0">Add a social account to begin managing status campaigns.</p>
                </div>
            </section>
        <?php else : ?>
            <div id="status-accordion" class="status-accordion">
                <?php foreach ($accountsData as $index => $account) : ?>
                    <?php
                    $accountName = (string) $account['name'];
                    $accountNameEsc = htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8');
                    $feedUrlEsc = htmlspecialchars((string) $account['feedUrl'], ENT_QUOTES, 'UTF-8');
                    $panelId = 'status-panel-' . (int) $index;
                    $isInitiallyOpen = $index === 0;
                    $statuses = $account['statuses'];
                    ?>
                    <section class="card status-campaign-card">
                        <div class="card-header status-campaign-header">
                            <button
                                id="status-heading-<?php echo (int) $index; ?>"
                                class="status-campaign-toggle<?php echo $isInitiallyOpen ? '' : ' collapsed'; ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?php echo $panelId; ?>"
                                aria-expanded="<?php echo $isInitiallyOpen ? 'true' : 'false'; ?>"
                                aria-controls="<?php echo $panelId; ?>"
                            >
                                <svg class="status-chevron" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                                <span class="status-campaign-title">
                                    Status Campaign: #<?php echo $accountNameEsc; ?>
                                </span>
                                <span class="badge bg-blue-lt"><?php echo count($statuses); ?> statuses</span>
                            </button>
                        </div>
                        <div id="<?php echo $panelId; ?>"
                            class="collapse status-panel<?php echo $isInitiallyOpen ? ' show' : ''; ?>"
                            data-bs-parent="#status-accordion"
                            data-account-name="<?php echo $accountNameEsc; ?>"
                            aria-labelledby="status-heading-<?php echo (int) $index; ?>">
                            <div class="card-body">
                                <?php if ($statuses === []) : ?>
                                    <div class="empty py-4">
                                        <p class="empty-title">No statuses available</p>
                                    </div>
                                <?php else : ?>
                                    <div class="status-grid">
                                        <?php foreach ($statuses as $status) : ?>
                                            <?php
                                            $statusText = (string) ($status['status'] ?? '');
                                            if ($statusText === '') {
                                                continue;
                                            }
                                            $createdAt = (string) ($status['created_at'] ?? '');
                                            $createdTimestamp = strtotime($createdAt);
                                            $displayDate = $createdTimestamp === false
                                                ? $createdAt
                                                : date('m/d/y g:ia', $createdTimestamp);
                                            $statusImage = (string) (
                                                $status['display_image'] ?? '/assets/images/default.png'
                                            );
                                            $imageUrl = (string) ($status['image_url'] ?? $statusImage);
                                            $statusTextEsc = htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8');
                                            $statusImageEsc = htmlspecialchars($statusImage, ENT_QUOTES, 'UTF-8');
                                            $imageUrlEsc = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
                                            $createdAtEsc = htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8');
                                            $displayDateEsc = htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8');
                                            $statusId = (int) ($status['id'] ?? 0);
                                            ?>
                                            <article class="card status-item">
                                                <img
                                                    class="status-image"
                                                    src="<?php echo $statusImageEsc; ?>"
                                                    alt=""
                                                    loading="lazy"
                                                >
                                                <div class="card-body">
                                                    <p class="status-text">
                                                        <?php echo $statusTextEsc; ?>
                                                    </p>
                                                    <time
                                                        class="status-date"
                                                        datetime="<?php echo $createdAtEsc; ?>"
                                                    >
                                                        <?php echo $displayDateEsc; ?>
                                                    </time>
                                                </div>
                                                <div class="card-footer status-actions">
                                                    <button
                                                        class="btn btn-primary status-icon-button copy-button"
                                                        type="button"
                                                        data-text="<?php echo $statusTextEsc; ?>"
                                                        data-url="<?php echo $imageUrlEsc; ?>"
                                                        aria-label="Copy status text and download image"
                                                        title="Copy text and download image"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path
                                                                d="M8 8V5a2 2 0 0 1 2-2h8l4 4v12a2 2 0 0 1-2 2h-2
                                                                M14 3v5h5M4 8h10a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4
                                                                a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2Z"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        class="btn btn-success status-icon-button share-button"
                                                        type="button"
                                                        data-text="<?php echo $statusTextEsc; ?>"
                                                        data-url="<?php echo $imageUrlEsc; ?>"
                                                        aria-label="Share status"
                                                        title="Share status"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <circle cx="18" cy="5" r="3" />
                                                            <circle cx="6" cy="12" r="3" />
                                                            <circle cx="18" cy="19" r="3" />
                                                            <path d="m8.7 10.7 6.6-4.4m-6.6 7 6.6 4.4" />
                                                        </svg>
                                                    </button>
                                                    <form class="delete-form" action="/home" method="post">
                                                        <input
                                                            type="hidden"
                                                            name="account"
                                                            value="<?php echo $accountNameEsc; ?>"
                                                        >
                                                        <input
                                                            type="hidden"
                                                            name="username"
                                                            value="<?php echo $accountOwnerEsc; ?>"
                                                        >
                                                        <input type="hidden" name="id" value="<?php echo $statusId; ?>">
                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?php echo $csrfTokenEsc; ?>"
                                                        >
                                                        <button
                                                            class="btn btn-danger status-icon-button"
                                                            type="submit"
                                                            name="delete_status"
                                                            aria-label="Delete status"
                                                            title="Delete status"
                                                        >
                                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                <path
                                                                    d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14
                                                                    M9 7V4h6v3"
                                                                />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="campaign-actions">
                                    <a
                                        class="btn btn-outline-primary"
                                        href="<?php echo $feedUrlEsc; ?>"
                                    >
                                        View feed
                                    </a>
                                    <form action="/home" method="post" class="m-0">
                                        <input type="hidden" name="account" value="<?php echo $accountNameEsc; ?>">
                                        <input
                                            type="hidden"
                                            name="username"
                                            value="<?php echo $accountOwnerEsc; ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo $csrfTokenEsc; ?>"
                                        >
                                        <button
                                            type="submit"
                                            class="btn btn-success"
                                            name="generate_status"
                                        >Generate Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<script defer src="/assets/js/status-page.js"></script>
<?php require __DIR__ . '/layouts/footer.php';
