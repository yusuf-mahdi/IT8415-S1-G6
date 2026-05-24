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
        <a href="users.php" class="btn btn-secondary">Go to User Management</a>
    </article>
    <article class="dashboard-card">
        <h2>Manage Content</h2>
        <p>Review, remove, and monitor all published content.</p>
        <a href="content.php" class="btn btn-secondary">Go to Content Management</a>
    </article>
    <article class="dashboard-card">
        <h2>Manage Comments</h2>
        <p>Monitor and remove inappropriate comments across all reviews.</p>
        <a href="comments.php" class="btn btn-secondary">Go to Comment Management</a>
    </article>
    <article class="dashboard-card">
        <h2>Reports</h2>
        <p>Generate popular-content and creator-content reports.</p>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="popular_report.php" class="btn btn-secondary">Popular Reviews Report</a>
            <a href="user_report.php" class="btn btn-secondary">User Content Report</a>
            <a href="activity_log.php" class="btn btn-secondary">System Activity Log (Triggers)</a>
        </div>
    </article>
</section>

<?php
page_footer();
