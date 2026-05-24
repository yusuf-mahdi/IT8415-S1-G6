<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$selectedUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$creators   = [];
$reportData = [];
$error      = '';

try {
    $pdo      = db();
    $creators = $pdo->query(
        "SELECT user_id, username FROM dbProj_users WHERE role IN ('creator','admin') ORDER BY username"
    )->fetchAll();

    if ($selectedUserId) {
        $stmt = $pdo->prepare('CALL dbProj_GetReviewsByUser(:user_id)');
        $stmt->execute([':user_id' => $selectedUserId]);
        $reportData = $stmt->fetchAll();
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    $error = 'Failed to load report data.';
}

page_header('User Content Report', '../');
?>

<section class="page-intro">
    <h1>User Content Report</h1>
    <p><a href="index.php">&larr; Back to Dashboard</a></p>
</section>

<?php if ($error !== ''): ?>
    <section class="notice notice-error"><p><?= e($error) ?></p></section>
<?php endif; ?>

<form method="GET" action="user_report.php" class="form-panel" style="max-width:480px; margin-bottom:2rem;">
    <div class="form-field">
        <label for="user_id">Select Creator</label>
        <select id="user_id" name="user_id" required onchange="this.form.submit()">
            <option value="">— Select a user —</option>
            <?php foreach ($creators as $creator): ?>
                <option value="<?= (int) $creator['user_id'] ?>"
                    <?= $selectedUserId === (int) $creator['user_id'] ? 'selected' : '' ?>>
                    <?= e($creator['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <noscript>
        <div class="form-actions"><button type="submit" class="btn btn-primary">Generate Report</button></div>
    </noscript>
</form>

<?php if ($selectedUserId): ?>
    <?php $selectedName = array_column($creators, 'username', 'user_id')[$selectedUserId] ?? 'Unknown'; ?>

    <section class="table-panel">
        <div class="section-heading">
            <h2>Report for: <?= e($selectedName) ?></h2>
        </div>

        <?php if (empty($reportData)): ?>
            <section class="empty-state">
                <h2>No reviews found</h2>
                <p>This user has not created any reviews yet.</p>
            </section>
        <?php else: ?>
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Review Title</th>
                            <th>Book</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?= (int) $row['review_id'] ?></td>
                                <td><?= e($row['review_title']) ?></td>
                                <td><?= e($row['book_title']) ?></td>
                                <td><span class="status-badge status-<?= e($row['status']) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                                <td><?= (int) $row['view_count'] ?></td>
                                <td><?= e(date('Y-m-d', strtotime($row['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php page_footer(); ?>
