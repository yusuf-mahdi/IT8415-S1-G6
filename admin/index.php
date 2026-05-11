<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('admin', '../login.php', '../index.php');

$user = current_user();

page_header('Admin Dashboard', '../');
?>

<section class="page-intro">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= e($user['username'] ?? 'admin') ?>. Management tools and reports will be added here.</p>
</section>

<section class="dashboard-grid" aria-label="Admin actions">
    <article class="dashboard-card">
        <h2>Manage Users</h2>
        <p>Review viewer, creator, and administrator accounts.</p>
    </article>
    <article class="dashboard-card">
        <h2>Manage Content</h2>
        <p>Review, remove, and monitor all published content.</p>
    </article>
    <article class="dashboard-card">
        <h2>Reports</h2>
        <p>Generate popular-content and creator-content reports.</p>
        <a href="popular_report.php">Popular Reviews Report</a>
    </article>
</section>

<?php
page_footer();
