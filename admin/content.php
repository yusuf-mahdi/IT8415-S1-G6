<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Review Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $deleteId = (int)$_POST['delete_review_id'];
    
    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Delete associated ratings and comments first to maintain integrity
        $pdo->prepare("DELETE FROM dbProj_ratings WHERE review_id = :id")->execute([':id' => $deleteId]);
        $pdo->prepare("DELETE FROM dbProj_comments WHERE review_id = :id")->execute([':id' => $deleteId]);
        
        $stmt = $pdo->prepare("DELETE FROM dbProj_reviews WHERE review_id = :id");
        $stmt->execute([':id' => $deleteId]);

        $pdo->commit();
        header("Location: content.php?message=Review and associated data removed successfully.");
        exit;
    } catch (PDOException $e) {
        if (isset($pdo)) $pdo->rollBack();
        $error = "Could not delete review.";
    }
}

// Fetch all reviews
try {
    $reviews = db()->query("
        SELECT 
            r.review_id, 
            r.review_title, 
            u.username AS creator, 
            b.title AS book_title,
            r.status,
            r.created_at 
        FROM dbProj_reviews r
        JOIN dbProj_users u ON r.user_id = u.user_id
        JOIN dbProj_books b ON r.book_id = b.book_id
        ORDER BY r.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load content.";
    $reviews = [];
}

page_header('Manage Content', '../');
?>

<div class="content-wrapper">
    <h1>Manage Content</h1>
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
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Title</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Book</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Creator</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Status</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Date</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= (int)$r['review_id'] ?></td>
                    <td style="padding: 10px;">
                        <a href="../review.php?id=<?= (int)$r['review_id'] ?>" target="_blank"><?= e($r['review_title']) ?></a>
                    </td>
                    <td style="padding: 10px;"><?= e($r['book_title']) ?></td>
                    <td style="padding: 10px;"><?= e($r['creator']) ?></td>
                    <td style="padding: 10px;"><?= e($r['status']) ?></td>
                    <td style="padding: 10px;"><?= e(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                    <td style="padding: 10px;">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review? All ratings and comments for it will also be removed.')">
                            <input type="hidden" name="delete_review_id" value="<?= (int)$r['review_id'] ?>">
                            <button type="submit" class="btn btn-error" style="background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Remove</button>
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