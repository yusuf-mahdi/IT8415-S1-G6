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
$selectedCategoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$keyword = $_GET['keyword'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$creator = $_GET['creator'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'newest';

if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    $pdo = db();
    $categories = $pdo
        ->query('SELECT category_id, category_name FROM dbProj_categories ORDER BY category_name')
        ->fetchAll();

    // Base query for counting and fetching
    $whereClause = "WHERE r.status = 'published'";
    $queryParams = [];

    if ($selectedCategoryId) {
        $whereClause .= " AND b.category_id = :category_id";
        $queryParams[':category_id'] = $selectedCategoryId;
    }

    if ($keyword !== '') {
        $whereClause .= " AND (MATCH(r.review_title, r.review_content) AGAINST(:keyword IN BOOLEAN MODE) 
                         OR MATCH(b.title, b.author, b.description) AGAINST(:keyword IN BOOLEAN MODE))";
        $queryParams[':keyword'] = $keyword;
    }

    if ($dateFrom !== '') {
        $whereClause .= " AND r.created_at >= :date_from";
        $queryParams[':date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $whereClause .= " AND r.created_at <= :date_to";
        $queryParams[':date_to'] = $dateTo . ' 23:59:59';
    }

    if ($creator !== '') {
        $whereClause .= " AND u.username LIKE :creator";
        $queryParams[':creator'] = '%' . $creator . '%';
    }

    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM dbProj_reviews r INNER JOIN dbProj_books b ON r.book_id = b.book_id INNER JOIN dbProj_users u ON r.user_id = u.user_id $whereClause");
    $countStmt->execute($queryParams);
    $totalReviews = $countStmt->fetchColumn();
    $totalPages = (int)ceil($totalReviews / $limit);

    // Sort order
    $orderBy = "ORDER BY r.created_at DESC";
    switch ($sortBy) {
        case 'rating_desc':
            $orderBy = "ORDER BY average_rating DESC, r.created_at DESC";
            break;
        case 'views_desc':
            $orderBy = "ORDER BY r.view_count DESC, r.created_at DESC";
            break;
        case 'oldest':
            $orderBy = "ORDER BY r.created_at ASC";
            break;
    }

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
        $whereClause
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
        $orderBy
        LIMIT :limit OFFSET :offset"
    );
    
    foreach ($queryParams as $key => $val) {
        $stmt->bindValue($key, $val);
    }
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

<section class="search-section" style="margin-bottom: 2rem;">
    <form method="GET" action="index.php" class="search-form" style="background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem;">
        <?php if ($selectedCategoryId): ?>
            <input type="hidden" name="category" value="<?= (int)$selectedCategoryId ?>">
        <?php endif; ?>
        
        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="keyword" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Search by Keyword (Title, Author, Content)</label>
            <input type="text" id="keyword" name="keyword" value="<?= e($keyword) ?>" placeholder="Enter search terms..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px;">
        </div>

        <div class="advanced-filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group">
                <label for="date_from" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="date_to" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="creator" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Creator</label>
                <input type="text" id="creator" name="creator" value="<?= e($creator) ?>" placeholder="Username..." style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="sort_by" style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Sort By</label>
                <select id="sort_by" name="sort_by" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px;">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="rating_desc" <?= $sortBy === 'rating_desc' ? 'selected' : '' ?>>Highest Rating</option>
                    <option value="views_desc" <?= $sortBy === 'views_desc' ? 'selected' : '' ?>>Most Views</option>
                </select>
            </div>
        </div>

        <div class="form-actions" style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Apply Filters</button>
            <a href="index.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
        </div>
    </form>
</section>

<?php if ($categories !== []): ?>
    <nav class="category-nav" aria-label="Book categories" style="margin-bottom: 2rem;">
        <?php 
            $queryBase = $_GET;
            unset($queryBase['category'], $queryBase['page']);
            $queryString = http_build_query($queryBase);
            $queryString = $queryString ? '&' . $queryString : '';
        ?>
        <a href="index.php?<?= $queryString ?>" class="<?= !$selectedCategoryId ? 'active' : '' ?>" style="<?= !$selectedCategoryId ? 'background: var(--accent); color: #fff;' : '' ?>">
            All Genres
        </a>
        <?php foreach ($categories as $category): ?>
            <a href="index.php?category=<?= e((string) $category['category_id']) ?><?= $queryString ?>" 
               class="<?= $selectedCategoryId === (int)$category['category_id'] ? 'active' : '' ?>"
               style="<?= $selectedCategoryId === (int)$category['category_id'] ? 'background: var(--accent); color: #fff;' : '' ?>">
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
        <h2>No reviews found</h2>
        <p>Try adjusting your search terms or filters.</p>
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
            <?php 
                $pageParams = $_GET;
                unset($pageParams['page']);
                $pageBaseUrl = "index.php?" . http_build_query($pageParams);
                $pageBaseUrl .= ($pageParams ? '&' : '');
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= $pageBaseUrl ?>page=<?= $page - 1 ?>" class="btn btn-secondary">&larr; Previous</a>
            <?php endif; ?>

            <span style="align-self: center;">Page <?= $page ?> of <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $pageBaseUrl ?>page=<?= $page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php
page_footer();
