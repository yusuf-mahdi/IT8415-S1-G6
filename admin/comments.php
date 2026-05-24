<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Comment Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $deleteId = (int)$_POST['delete_comment_id'];
    
    try {
        $stmt = db()->prepare("DELETE FROM dbProj_comments WHERE comment_id = :id");
        $stmt->execute([':id' => $deleteId]);

        header("Location: comments.php?message=Comment removed successfully.");
        exit;
    } catch (PDOException $e) {
        $error = "Could not delete comment.";
    }
}

// Fetch all comments
try {
    $comments = db()->query("
        SELECT 
            c.comment_id, 
            c.comment_text, 
            u.username AS commenter, 
            r.review_title,
            c.created_at 
        FROM dbProj_comments c
        JOIN dbProj_users u ON c.user_id = u.user_id
        JOIN dbProj_reviews r ON c.review_id = r.review_id
        ORDER BY c.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load comments.";
    $comments = [];
}

page_header('Manage Comments', '../');
?>

<div class="content-wrapper">
    <h1>Manage Comments</h1>
    <p><a href="index.php">&larr; Back to Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
        <thead>
            <tr style="background: #f4f4f4; text-align: left;">
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">ID</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Comment</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">User</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">On Review</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Date</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $c): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= (int)$c['comment_id'] ?></td>
                    <td style="padding: 10px; max-width: 300px;"><?= e($c['comment_text']) ?></td>
                    <td style="padding: 10px;"><?= e($c['commenter']) ?></td>
                    <td style="padding: 10px;"><?= e($c['review_title']) ?></td>
                    <td style="padding: 10px;"><?= e(date('Y-m-d', strtotime($c['created_at']))) ?></td>
                    <td style="padding: 10px;">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this comment?')">
                            <input type="hidden" name="delete_comment_id" value="<?= (int)$c['comment_id'] ?>">
                            <button type="submit" class="btn btn-error" style="background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
page_footer();
?>