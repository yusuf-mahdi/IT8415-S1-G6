<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', '../login.php', '../index.php');

$message = $_GET['message'] ?? '';
$error   = $_GET['error']   ?? '';
$currentUser = current_user();

// Pagination
$limit  = 10;
$page   = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteId = (int) $_POST['delete_user_id'];

    if ($deleteId === $currentUser['id']) {
        $error = 'You cannot delete your own admin account.';
    } else {
        try {
            $stmt = db()->prepare('DELETE FROM dbProj_users WHERE user_id = :id');
            $stmt->execute([':id' => $deleteId]);
            header('Location: users.php?message=User deleted successfully.');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not delete user. They may have active reviews or interactions.';
        }
    }
}

// Fetch total for pagination
try {
    $total     = (int) db()->query('SELECT COUNT(*) FROM dbProj_users')->fetchColumn();
    $totalPages = (int) ceil($total / $limit);

    $users = db()->query(
        "SELECT user_id, username, email, role, created_at
         FROM dbProj_users
         ORDER BY role, username
         LIMIT $limit OFFSET $offset"
    )->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load users.';
    $users = [];
    $totalPages = 1;
}

page_header('Manage Users', '../');
?>

<section class="page-intro">
    <h1>Manage Users</h1>
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
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int) $u['user_id'] ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="status-badge status-<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                        <td>
                            <?php if ((int) $u['user_id'] !== $currentUser['id']): ?>
                                <form method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                    <input type="hidden" name="delete_user_id" value="<?= (int) $u['user_id'] ?>">
                                    <button type="submit" class="btn btn-error">Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-text">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="users.php?page=<?= $page - 1 ?>" class="btn btn-secondary">&larr; Previous</a>
            <?php endif; ?>
            <span style="align-self:center;">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="users.php?page=<?= $page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php page_footer(); ?>
