<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';

$reviews = [];
$categories = [];
$loadError = '';

// Pagination settings
$limit = 6;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    $pdo = db();
    $categories = $pdo
        ->query('SELECT category_id, category_name FROM dbProj_categories ORDER BY category_name')
        ->fetchAll();

    // Get total count for pagination
    $totalReviews = $pdo->query("SELECT COUNT(*) FROM dbProj_reviews WHERE status = 'published'")->fetchColumn();
    $totalPages = (int)ceil($totalReviews / $limit);

    $stmt = $pdo->prepare(
        "SELECT
            r.review_id,
            r.review_title,
            r.review_content,
            r.cover_image AS review_cover,
            r.view_count,
            r.created_at,
            b.title AS book_title,
            b.author AS book_author,
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
        WHERE r.status = 'published'
        GROUP BY
            r.review_id,
            r.review_title,
            r.review_content,
            r.cover_image,
            r.view_count,
            r.created_at,
            b.title,
            b.author,
            b.cover_image,
            c.category_name,
            u.username
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reviews = $stmt->fetchAll();
} catch (PDOException $exception) {
    $loadError = 'Reviews could not be loaded. Check the database connection and import database.sql.';
}

function short_text(string $text, int $limit = 160): string
{
    $cleanText = trim(strip_tags($text));

    if (strlen($cleanText) <= $limit) {
        return $cleanText;
    }

    return rtrim(substr($cleanText, 0, $limit), " \t\n\r\0\x0B.,") . '...';
}

function review_cover(array $review): string
{
    return (string) ($review['review_cover'] ?: $review['book_cover'] ?: '');
}

page_header('Home');
?>

<section class="page-intro">
    <h1>Latest Book Reviews</h1>
    <p>Browse recent book reviews, ratings, and creator recommendations.</p>
</section>

<?php if ($categories !== []): ?>
    <nav class="category-nav" aria-label="Book categories">
        <?php foreach ($categories as $category): ?>
            <a href="search.php?category=<?= e((string) $category['category_id']) ?>">
                <?= e($category['category_name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if ($loadError !== ''): ?>
    <section class="notice notice-error">
        <p><?= e($loadError) ?></p>
    </section>
<?php elseif ($reviews === []): ?>
    <section class="empty-state">
        <h2>No published reviews yet</h2>
        <p>Published creator reviews will appear here in newest-first order.</p>
    </section>
<?php else: ?>
    <section class="review-grid" aria-label="Latest published reviews">
        <?php foreach ($reviews as $review): ?>
            <?php $cover = review_cover($review); ?>
            <article class="review-card">
                <?php if ($cover !== ''): ?>
                    <img class="review-card-image" src="<?= e($cover) ?>" alt="<?= e($review['book_title']) ?> cover">
                <?php else: ?>
                    <div class="review-card-image review-card-placeholder" aria-hidden="true">
                        <span><?= e(substr((string) $review['book_title'], 0, 1)) ?></span>
                    </div>
                <?php endif; ?>

                <div class="review-card-body">
                    <div class="review-card-meta">
                        <span><?= e($review['category_name'] ?? 'Uncategorized') ?></span>
                        <span><?= e(date('M j, Y', strtotime((string) $review['created_at']))) ?></span>
                    </div>

                    <h2><?= e($review['review_title']) ?></h2>
                    <p class="book-line"><?= e($review['book_title']) ?> by <?= e($review['book_author']) ?></p>
                    <p><?= e(short_text((string) $review['review_content'])) ?></p>

                    <div class="review-card-stats">
                        <span>By <?= e($review['creator_name']) ?></span>
                        <span>
                            <?= e($review['average_rating'] !== null ? (string) $review['average_rating'] : 'No') ?>
                            rating<?= (int) $review['rating_count'] === 1 ? '' : 's' ?>
                        </span>
                        <span><?= e((string) $review['view_count']) ?> views</span>
                    </div>

                    <a class="button-link" href="review.php?id=<?= e((string) $review['review_id']) ?>">View More</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination" style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
            <?php if ($page > 1): ?>
                <a href="index.php?page=<?= $page - 1 ?>" class="btn btn-secondary">&larr; Previous</a>
            <?php endif; ?>

            <span style="align-self: center;">Page <?= $page ?> of <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="index.php?page=<?= $page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php
page_footer();
