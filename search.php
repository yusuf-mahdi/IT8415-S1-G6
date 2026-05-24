<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';

$reviews = [];
$searchError = '';

// Default values
$keyword = $_GET['keyword'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$creator = $_GET['creator'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'newest';

// Only search if the form was submitted or parameters are present
if (!empty($_GET)) {
    try {
        $pdo = db();
        
        $query = "SELECT
                r.review_id,
                r.review_title,
                r.review_content,
                r.cover_image AS review_cover,
                r.view_count,
                r.created_at,
                b.title AS book_title,
                b.author AS book_author,
                u.username AS creator_name,
                ROUND(AVG(rt.rating), 1) AS average_rating
            FROM dbProj_reviews r
            INNER JOIN dbProj_books b ON r.book_id = b.book_id
            INNER JOIN dbProj_users u ON r.user_id = u.user_id
            LEFT JOIN dbProj_ratings rt ON r.review_id = rt.review_id
            WHERE r.status = 'published'";

        $params = [];

        // 1. Title/Content Search using FULLTEXT index
        if ($keyword !== '') {
            $query .= " AND (MATCH(r.review_title, r.review_content) AGAINST(:keyword IN BOOLEAN MODE) 
                             OR MATCH(b.title, b.author, b.description) AGAINST(:keyword IN BOOLEAN MODE))";
            $params[':keyword'] = $keyword;
        }

        // 2. Date Range Search
        if ($dateFrom !== '') {
            $query .= " AND r.created_at >= :date_from";
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $query .= " AND r.created_at <= :date_to";
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        // 3. Creator Search
        if ($creator !== '') {
            $query .= " AND u.username LIKE :creator";
            $params[':creator'] = '%' . $creator . '%';
        }

        $query .= " GROUP BY r.review_id, r.review_title, r.review_content, r.cover_image, r.view_count, r.created_at, b.title, b.author, u.username";

        // 4. Popularity Search/Sort
        switch ($sortBy) {
            case 'rating_desc':
                $query .= " ORDER BY average_rating DESC, r.created_at DESC";
                break;
            case 'views_desc':
                $query .= " ORDER BY r.view_count DESC, r.created_at DESC";
                break;
            case 'oldest':
                $query .= " ORDER BY r.created_at ASC";
                break;
            case 'newest':
            default:
                $query .= " ORDER BY r.created_at DESC";
                break;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $searchError = 'An error occurred while searching. Please try again.';
    }
}

page_header('Search Reviews');
?>

<div class="content-wrapper">
    <h1>Search Reviews</h1>

    <?php if ($searchError): ?>
        <div class="alert alert-error"><?= e($searchError) ?></div>
    <?php endif; ?>

    <form method="GET" action="search.php" class="form-container search-form">
        <div class="form-group">
            <label for="keyword">Keyword (Title, Author, Content)</label>
            <input type="text" id="keyword" name="keyword" value="<?= e($keyword) ?>">
        </div>

        <div class="form-row" style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
            </div>
        </div>

        <div class="form-row" style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label for="creator">Creator/Reviewer Username</label>
                <input type="text" id="creator" name="creator" value="<?= e($creator) ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="sort_by">Sort By</label>
                <select id="sort_by" name="sort_by">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="rating_desc" <?= $sortBy === 'rating_desc' ? 'selected' : '' ?>>Highest Rating</option>
                    <option value="views_desc" <?= $sortBy === 'views_desc' ? 'selected' : '' ?>>Most Views</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="search.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>

    <?php if (!empty($_GET)): ?>
        <h2>Search Results (<?= count($reviews) ?>)</h2>
        <?php if (empty($reviews)): ?>
            <p>No reviews found matching your criteria.</p>
        <?php else: ?>
            <div class="review-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" style="border: 1px solid #ccc; border-radius: 8px; padding: 1rem; background: #fff;">
                        <?php if ($review['review_cover']): ?>
                            <img src="uploads/covers/<?= e($review['review_cover']) ?>" alt="Cover" style="max-width: 100%; height: auto; border-radius: 4px; margin-bottom: 1rem;">
                        <?php endif; ?>
                        <h3 style="margin-top: 0;">
                            <a href="review.php?id=<?= (int)$review['review_id'] ?>"><?= e($review['review_title']) ?></a>
                        </h3>
                        <p><strong>Book:</strong> <?= e($review['book_title']) ?> by <?= e($review['book_author']) ?></p>
                        <p><strong>By:</strong> <?= e($review['creator_name']) ?></p>
                        <p>
                            <strong>Rating:</strong> <?= $review['average_rating'] ? e((string)$review['average_rating']) . ' / 5' : 'No ratings yet' ?> <br>
                            <strong>Views:</strong> <?= (int)$review['view_count'] ?>
                        </p>
                        <p style="font-size: 0.9em; color: #555;">
                            <?= e(substr($review['review_content'], 0, 100)) ?>...
                        </p>
                        <a href="review.php?id=<?= (int)$review['review_id'] ?>" class="btn btn-secondary" style="display: inline-block; margin-top: 1rem;">Read More</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
page_footer();
?>