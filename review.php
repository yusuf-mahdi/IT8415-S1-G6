<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';

$review = null;
$comments = [];
$loadError = '';
$reviewId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($reviewId === false || $reviewId === null) {
    $loadError = 'Select a valid review.';
} else {
    try {
        $updateStmt = db()->prepare(
            "UPDATE dbProj_reviews
            SET view_count = view_count + 1
            WHERE review_id = :review_id
              AND status = 'published'"
        );
        $updateStmt->execute([':review_id' => $reviewId]);

        $stmt = db()->prepare(
            "SELECT
                r.review_id,
                r.review_title,
                r.review_content,
                r.cover_image AS review_cover,
                r.media_file,
                r.downloadable_file,
                r.view_count,
                r.created_at,
                b.title AS book_title,
                b.author AS book_author,
                b.description AS book_description,
                b.cover_image AS book_cover,
                c.category_name,
                u.username AS creator_name,
                ROUND(AVG(rt.rating), 1) AS average_rating,
                COUNT(rt.rating_id) AS rating_count
            FROM dbProj_reviews r
            INNER JOIN dbProj_books b ON r.book_id = b.book_id
            LEFT JOIN dbProj_categories c ON b.category_id = c.category_id
            INNER JOIN dbProj_users u ON r.user_id = u.user_id
            LEFT JOIN dbProj_ratings rt ON r.review_id = rt.review_id
            WHERE r.review_id = :review_id
              AND r.status = 'published'
            GROUP BY
                r.review_id,
                r.review_title,
                r.review_content,
                r.cover_image,
                r.media_file,
                r.downloadable_file,
                r.view_count,
                r.created_at,
                b.title,
                b.author,
                b.description,
                b.cover_image,
                c.category_name,
                u.username
            LIMIT 1"
        );
        $stmt->execute([':review_id' => $reviewId]);
        $review = $stmt->fetch();

        if ($review === false) {
            $loadError = 'Published review was not found.';
            $review = null;
        } else {
            $commentStmt = db()->prepare(
                "SELECT
                    cm.comment_text,
                    cm.created_at,
                    u.username
                FROM dbProj_comments cm
                INNER JOIN dbProj_users u ON cm.user_id = u.user_id
                WHERE cm.review_id = :review_id
                ORDER BY cm.created_at DESC"
            );
            $commentStmt->execute([':review_id' => $reviewId]);
            $comments = $commentStmt->fetchAll();
        }
    } catch (PDOException $exception) {
        $loadError = 'Review could not be loaded. Check the database connection.';
    }
}

function detail_cover(array $review): string
{
    return (string) ($review['review_cover'] ?: $review['book_cover'] ?: '');
}

function file_extension(string $path): string
{
    return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}

page_header($review !== null ? (string) $review['review_title'] : 'Review');
?>

<?php if ($loadError !== ''): ?>
    <section class="notice notice-error">
        <p><?= e($loadError) ?></p>
    </section>
    <p><a href="index.php">Back to home</a></p>
<?php elseif ($review !== null): ?>
    <?php $cover = detail_cover($review); ?>
    <article class="review-detail">
        <header class="review-detail-header">
            <div>
                <p class="eyebrow"><?= e($review['category_name'] ?? 'Uncategorized') ?></p>
                <h1><?= e($review['review_title']) ?></h1>
                <p class="book-line"><?= e($review['book_title']) ?> by <?= e($review['book_author']) ?></p>
                <div class="review-card-stats">
                    <span>By <?= e($review['creator_name']) ?></span>
                    <span><?= e(date('M j, Y', strtotime((string) $review['created_at']))) ?></span>
                    <span><?= e((string) $review['view_count']) ?> views</span>
                    <span>
                        <?= e($review['average_rating'] !== null ? (string) $review['average_rating'] : 'No') ?>
                        rating<?= (int) $review['rating_count'] === 1 ? '' : 's' ?>
                    </span>
                </div>
            </div>

            <?php if ($cover !== ''): ?>
                <img class="review-detail-cover" src="<?= e($cover) ?>" alt="<?= e($review['book_title']) ?> cover">
            <?php endif; ?>
        </header>

        <section class="content-panel">
            <h2>Review</h2>
            <p><?= nl2br(e($review['review_content'])) ?></p>
        </section>

        <?php if ($review['book_description'] !== null && trim((string) $review['book_description']) !== ''): ?>
            <section class="content-panel">
                <h2>Book Description</h2>
                <p><?= e($review['book_description']) ?></p>
            </section>
        <?php endif; ?>

        <?php if ($review['media_file'] !== null && $review['media_file'] !== ''): ?>
            <section class="content-panel">
                <h2>Media</h2>
                <?php $extension = file_extension((string) $review['media_file']); ?>
                <?php if (in_array($extension, ['mp4', 'webm'], true)): ?>
                    <video controls src="<?= e($review['media_file']) ?>"></video>
                <?php else: ?>
                    <audio controls src="<?= e($review['media_file']) ?>"></audio>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($review['downloadable_file'] !== null && $review['downloadable_file'] !== ''): ?>
            <section class="content-panel">
                <h2>Download</h2>
                <a class="button-link" href="<?= e($review['downloadable_file']) ?>" download>Download File</a>
            </section>
        <?php endif; ?>
    </article>

    <section class="content-panel" aria-labelledby="comments-title">
        <h2 id="comments-title">Comments</h2>
        <?php if ($comments === []): ?>
            <p class="muted-text">No comments yet.</p>
        <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-item">
                        <h3><?= e($comment['username']) ?></h3>
                        <p><?= e($comment['comment_text']) ?></p>
                        <p class="muted-text"><?= e(date('M j, Y', strtotime((string) $comment['created_at']))) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
page_footer();
