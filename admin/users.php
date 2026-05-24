<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteId = (int)$_POST['delete_user_id'];
    $currentUser = current_user();

    if ($deleteId === $currentUser['id']) {
        $error = "You cannot delete your own admin account.";
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            // Note: In a real app with referential integrity without CASCADE, 
            // we'd need to delete ratings, comments, and reviews first.
            // For now, we'll try to delete and handle potential errors.
            
            $stmt = $pdo->prepare("DELETE FROM dbProj_users WHERE user_id = :id");
            $stmt->execute([':id' => $deleteId]);

            $pdo->commit();
            header("Location: users.php?message=User deleted successfully.");
            exit;
        } catch (PDOException $e) {
            if (isset($pdo)) $pdo->rollBack();
            $error = "Could not delete user. They may have active reviews or interactions.";
        }
    }
}

// Fetch all users
try {
    $users = db()->query("SELECT user_id, username, email, role, created_at FROM dbProj_users ORDER BY role, username")->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to load users.";
    $users = [];
}

page_header('Manage Users', '../');
?>

<div class="content-wrapper">
    <h1>Manage Users</h1>
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
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Username</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Email</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Role</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Joined</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= (int)$u['user_id'] ?></td>
                    <td style="padding: 10px;"><?= e($u['username']) ?></td>
                    <td style="padding: 10px;"><?= e($u['email']) ?></td>
                    <td style="padding: 10px;"><?= e($u['role']) ?></td>
                    <td style="padding: 10px;"><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                    <td style="padding: 10px;">
                        <?php if ($u['user_id'] !== $currentUser['id']): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                                <input type="hidden" name="delete_user_id" value="<?= (int)$u['user_id'] ?>">
                                <button type="submit" class="btn btn-error" style="background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Delete</button>
                            </form>
                        <?php else: ?>
                            <span class="muted-text">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
page_footer();
?>