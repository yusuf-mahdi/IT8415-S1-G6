<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$message = $_GET['message'] ?? '';
$error   = $_GET['error']   ?? '';

// Pagination
$limit  = 10;
$page   = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Handle Review Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $deleteId = (int) $_POST['delete_review_id'];
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM dbProj_ratings  WHERE review_id = :id')->execute([':id' => $deleteId]);
        $pdo->prepare('DELETE FROM dbProj_comments WHERE review_id = :id')->execute([':id' => $deleteId]);
        $pdo->prepare('DELETE FROM dbProj_reviews  WHERE review_id = :id')->execute([':id' => $deleteId]);
        $pdo->commit();
        header('Location: content.php?message=Review and associated data removed successfully.');
        exit;
    } catch (PDOException $e) {
        if (isset($pdo)) $pdo->rollBack();
        $error = 'Could not delete review.';
    }
}

// Fetch total + paginated reviews
try {
    $total      = (int) db()->query('SELECT COUNT(*) FROM dbProj_reviews')->fetchColumn();
    $totalPages = (int) ceil($total / $limit);

    $reviews = db()->query(
        "SELECT r.review_id, r.review_title, u.username AS creator,
                b.title AS book_title, r.status, r.created_at
         FROM dbProj_reviews r
         JOIN dbProj_users u ON r.user_id  = u.user_id
         JOIN dbProj_books b ON r.book_id   = b.book_id
         ORDER BY r.created_at DESC
         LIMIT $limit OFFSET $offset"
    )->fetchAll();
} catch (PDOException $e) {
    $error   = 'Failed to load content.';
    $reviews = [];
    $totalPages = 1;
}

page_header('Manage Content', '../');
?>

<section class="page-intro">
    <h1>Manage Content</h1>
    <p><a href="index.php">&larr; Back to Dashboard</a></p>
</section>

<?php if ($message !== ''): ?>
    <section class="notice notice-success"><p><?= e($message) ?></p></section>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <section class="notice notice-error"><p><?= e($error) ?></p></section>
<?php endif; ?>

<section class="table-panel">
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Book</th>
                    <th>Creator</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= (int) $r['review_id'] ?></td>
                        <td><a href="../review.php?id=<?= (int) $r['review_id'] ?>" target="_blank"><?= e($r['review_title']) ?></a></td>
                        <td><?= e($r['book_title']) ?></td>
                        <td><?= e($r['creator']) ?></td>
                        <td><span class="status-badge status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        <td><?= e(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this review? All ratings and comments will also be deleted.')">
                                <input type="hidden" name="delete_review_id" value="<?= (int) $r['review_id'] ?>">
                                <button type="submit" class="btn btn-error">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="content.php?page=<?= $page - 1 ?>" class="btn btn-secondary">&larr; Previous</a>
            <?php endif; ?>
            <span style="align-self:center;">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="content.php?page=<?= $page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php page_footer(); ?>
