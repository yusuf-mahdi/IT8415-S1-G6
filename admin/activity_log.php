<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$error = '';
$logs = [];

try {
    $logs = db()->query("
        SELECT 
            l.log_id, 
            u.username, 
            l.action, 
            l.created_at 
        FROM dbProj_activity_log l
        JOIN dbProj_users u ON l.user_id = u.user_id
        ORDER BY l.created_at DESC 
        LIMIT 50
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load activity logs.";
}

page_header('Activity Logs', '../');
?>

<div class="content-wrapper">
    <h1>System Activity Logs</h1>
    <p><a href="index.php">&larr; Back to Dashboard</a></p>
    <p class="field-hint">Showing the last 50 actions logged by database triggers.</p>

    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
        <thead>
            <tr style="background: #f4f4f4; text-align: left;">
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Time</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">User</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="3" style="padding: 20px; text-align: center; color: #777;">No activity recorded yet. (Try rating a review!)</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= e(date('Y-m-d H:i:s', strtotime($log['created_at']))) ?></td>
                        <td style="padding: 10px;"><?= e($log['username']) ?></td>
                        <td style="padding: 10px;"><?= e($log['action']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
page_footer();
?>