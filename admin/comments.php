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

// Handle Comment Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $deleteId = (int) $_POST['delete_comment_id'];
    try {
        db()->prepare('DELETE FROM dbProj_comments WHERE comment_id = :id')
           ->execute([':id' => $deleteId]);
        header('Location: comments.php?message=Comment removed successfully.');
        exit;
    } catch (PDOException $e) {
        $error = 'Could not delete comment.';
    }
}

// Fetch total + paginated comments
try {
    $total      = (int) db()->query('SELECT COUNT(*) FROM dbProj_comments')->fetchColumn();
    $totalPages = (int) ceil($total / $limit);

    $comments = db()->query(
        "SELECT c.comment_id, c.comment_text, u.username AS commenter,
                r.review_title, r.review_id, c.created_at
         FROM dbProj_comments c
         JOIN dbProj_users u   ON c.user_id   = u.user_id
         JOIN dbProj_reviews r ON c.review_id  = r.review_id
         ORDER BY c.created_at DESC
         LIMIT $limit OFFSET $offset"
    )->fetchAll();
} catch (PDOException $e) {
    $error    = 'Failed to load comments.';
    $comments = [];
    $totalPages = 1;
}

page_header('Manage Comments', '../');
?>

<section class="page-intro">
    <h1>Manage Comments</h1>
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
                    <th>Comment</th>
                    <th>User</th>
                    <th>On Review</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $c): ?>
                    <tr>
                        <td><?= (int) $c['comment_id'] ?></td>
                        <td><?= e($c['comment_text']) ?></td>
                        <td><?= e($c['commenter']) ?></td>
                        <td><a href="../review.php?id=<?= (int) $c['review_id'] ?>" target="_blank"><?= e($c['review_title']) ?></a></td>
                        <td><?= e(date('Y-m-d', strtotime($c['created_at']))) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this comment?')">
                                <input type="hidden" name="delete_comment_id" value="<?= (int) $c['comment_id'] ?>">
                                <button type="submit" class="btn btn-error">Delete</button>
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
                <a href="comments.php?page=<?= $page - 1 ?>" class="btn btn-secondary">&larr; Previous</a>
            <?php endif; ?>
            <span style="align-self:center;">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="comments.php?page=<?= $page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php page_footer(); ?>
