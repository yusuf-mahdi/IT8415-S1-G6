<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('creator', '../login.php', '../index.php');

$user = current_user();
$reviews = [];
$loadError = '';
$draftCount = 0;

try {
    $stmt = db()->prepare(
        "SELECT
            r.review_id,
            r.review_title,
            r.status,
            r.view_count,
            r.created_at,
            b.title AS book_title,
            b.author AS book_author,
            ROUND(AVG(rt.rating), 1) AS average_rating,
            COUNT(rt.rating_id) AS rating_count,
            COUNT(DISTINCT cm.comment_id) AS comment_count
        FROM dbProj_reviews r
        INNER JOIN dbProj_books b ON r.book_id = b.book_id
        LEFT JOIN dbProj_ratings rt ON r.review_id = rt.review_id
        LEFT JOIN dbProj_comments cm ON r.review_id = cm.review_id
        WHERE r.user_id = :user_id
        GROUP BY
            r.review_id,
            r.review_title,
            r.status,
            r.view_count,
            r.created_at,
            b.title,
            b.author
        ORDER BY r.created_at DESC"
    );
    $stmt->execute([':user_id' => $user['id']]);
    $reviews = $stmt->fetchAll();
    $draftCount = count(array_filter($reviews, static fn (array $review): bool => $review['status'] === 'draft'));
} catch (PDOException $exception) {
    $loadError = 'Your reviews could not be loaded. Check the database connection.';
}

page_header('Creator Dashboard', '../');
?>

<section class="page-intro">
    <h1>Creator Dashboard</h1>
    <p>Welcome, <?= e($user['username'] ?? 'creator') ?>. Manage your drafts and published reviews.</p>
</section>

<?php if (isset($_GET['message'])): ?>
    <section class="notice notice-success">
        <p><?= e($_GET['message']) ?></p>
    </section>
<?php endif; ?>

<section class="dashboard-grid" aria-label="Creator actions">
    <article class="dashboard-card">
        <h2>My Reviews</h2>
        <p><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?> created.</p>
    </article>
    <article class="dashboard-card">
        <h2>Add Review</h2>
        <p>Create a new draft with images, media, and optional downloadable files.</p>
        <a href="create.php">Add Review</a>
    </article>
    <article class="dashboard-card">
        <h2>Publish</h2>
        <p><?= $draftCount ?> draft<?= $draftCount === 1 ? '' : 's' ?> ready for publishing.</p>
        <?php if ($draftCount > 0): ?>
            <a href="#my-reviews" class="btn btn-secondary">View Drafts</a>
        <?php endif; ?>
    </article>
</section>

<?php if ($loadError !== ''): ?>
    <section class="notice notice-error">
        <p><?= e($loadError) ?></p>
    </section>
<?php elseif ($reviews === []): ?>
    <section class="empty-state">
        <h2>No reviews yet</h2>
        <p>Your drafts and published reviews will appear here after you create them.</p>
        <a class="button-link empty-action" href="create.php">Add Review</a>
    </section>
<?php else: ?>
    <section class="table-panel" id="my-reviews" aria-labelledby="creator-reviews-title">
        <div class="section-heading">
            <h2 id="creator-reviews-title">My Reviews</h2>
            <a class="button-link" href="create.php">Add Review</a>
        </div>

        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Review</th>
                        <th scope="col">Book</th>
                        <th scope="col">Status</th>
                        <th scope="col">Rating</th>
                        <th scope="col">Comments</th>
                        <th scope="col">Views</th>
                        <th scope="col">Created</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?= e($review['review_title']) ?></td>
                            <td>
                                <?= e($review['book_title']) ?><br>
                                <span class="muted-text"><?= e($review['book_author']) ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= e($review['status']) ?>">
                                    <?= e(ucfirst((string) $review['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($review['average_rating'] === null): ?>
                                    <span class="muted-text">No ratings</span>
                                <?php else: ?>
                                    <?= e((string) $review['average_rating']) ?>
                                    <span class="muted-text">(<?= e((string) $review['rating_count']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $review['comment_count']) ?></td>
                            <td><?= e((string) $review['view_count']) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $review['created_at']))) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="edit.php?id=<?= e((string) $review['review_id']) ?>">Edit</a>
                                    <?php if ($review['status'] === 'draft'): ?>
                                        <a href="publish.php?id=<?= e((string) $review['review_id']) ?>">Publish</a>
                                    <?php else: ?>
                                        <a href="../review.php?id=<?= e((string) $review['review_id']) ?>">View</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php
page_footer();
