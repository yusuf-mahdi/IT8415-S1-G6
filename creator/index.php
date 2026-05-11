<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('creator', '../login.php', '../index.php');

$user = current_user();

page_header('Creator Dashboard', '../');
?>

<section class="page-intro">
    <h1>Creator Dashboard</h1>
    <p>Welcome, <?= e($user['username'] ?? 'creator') ?>. Your review management tools will be added here.</p>
</section>

<section class="dashboard-grid" aria-label="Creator actions">
    <article class="dashboard-card">
        <h2>My Reviews</h2>
        <p>View your drafts and published reviews.</p>
    </article>
    <article class="dashboard-card">
        <h2>Add Review</h2>
        <p>Create a new review with images, media, and optional downloadable files.</p>
    </article>
    <article class="dashboard-card">
        <h2>Publish</h2>
        <p>Review draft content before publishing it for viewers.</p>
    </article>
</section>

<?php
page_footer();
