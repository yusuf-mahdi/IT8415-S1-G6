<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$selectedUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$creators = [];
$reportData = [];
$error = '';

try {
    $pdo = db();
    
    // Fetch all creators and admins for the dropdown
    $creators = $pdo->query("SELECT user_id, username FROM dbProj_users WHERE role IN ('creator', 'admin') ORDER BY username")->fetchAll();

    if ($selectedUserId) {
        // Execute stored procedure
        $stmt = $pdo->prepare("CALL dbProj_GetReviewsByUser(:user_id)");
        $stmt->execute([':user_id' => $selectedUserId]);
        $reportData = $stmt->fetchAll();
        $stmt->closeCursor(); // Important when calling multiple procedures or mixing with other queries
    }
} catch (PDOException $e) {
    $error = "Failed to load report data: " . $e->getMessage();
}

page_header('User Content Report', '../');
?>

<div class="content-wrapper">
    <h1>User Content Report</h1>
    <p><a href="index.php">&larr; Back to Dashboard</a></p>

    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="user_report.php" class="form-container" style="background: #f9f9f9; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <div class="form-group">
            <label for="user_id">Select Creator:</label>
            <select id="user_id" name="user_id" required onchange="this.form.submit()" style="padding: 8px; width: 100%; max-width: 300px;">
                <option value="">-- Select a User --</option>
                <?php foreach ($creators as $creator): ?>
                    <option value="<?= (int)$creator['user_id'] ?>" <?= $selectedUserId === (int)$creator['user_id'] ? 'selected' : '' ?>>
                        <?= e($creator['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </noscript>
    </form>

    <?php if ($selectedUserId): ?>
        <h2>Report for: <?= e(array_column($creators, 'username', 'user_id')[$selectedUserId] ?? 'Unknown') ?></h2>
        
        <?php if (empty($reportData)): ?>
            <p>This user has not created any reviews yet.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="background: #f4f4f4; text-align: left;">
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">ID</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Review Title</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Book Title</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Status</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Views</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?= (int)$row['review_id'] ?></td>
                            <td style="padding: 10px;"><?= e($row['review_title']) ?></td>
                            <td style="padding: 10px;"><?= e($row['book_title']) ?></td>
                            <td style="padding: 10px;">
                                <span style="text-transform: capitalize; padding: 2px 8px; border-radius: 12px; font-size: 0.85em; background: <?= $row['status'] === 'published' ? '#d4edda' : '#fff3cd' ?>;">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px;"><?= (int)$row['view_count'] ?></td>
                            <td style="padding: 10px;"><?= e(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
page_footer();
?>