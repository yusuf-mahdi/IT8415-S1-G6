<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('creator', '../login.php', '../index.php');

$user = current_user();
$review = null;
$errors = [];
$successMessage = '';
$reviewId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($reviewId === false || $reviewId === null) {
    $errors[] = 'Select a valid review to publish.';
} else {
    $review = find_creator_draft($reviewId, (int) $user['id'], $errors);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedReviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

    if ($postedReviewId === false || $postedReviewId === null) {
        $errors[] = 'Select a valid review to publish.';
    } else {
        $review = find_creator_draft($postedReviewId, (int) $user['id'], $errors);

        if ($review !== null && $errors === []) {
            if (trim((string) $review['review_title']) === '' || trim((string) $review['review_content']) === '') {
                $errors[] = 'Review title and content are required before publishing.';
            }

            if ($errors === []) {
                try {
                    $stmt = db()->prepare(
                        "UPDATE dbProj_reviews
                        SET status = 'published'
                        WHERE review_id = :review_id
                          AND user_id = :user_id
                          AND status = 'draft'"
                    );
                    $stmt->execute([
                        ':review_id' => $postedReviewId,
                        ':user_id' => $user['id'],
                    ]);

                    if ($stmt->rowCount() !== 1) {
                        $errors[] = 'Review could not be published.';
                    } else {
                        $successMessage = 'Review published successfully.';
                        $review = null;
                    }
                } catch (PDOException $exception) {
                    $errors[] = 'Review could not be published. Try again later.';
                }
            }
        }
    }
}

function find_creator_draft(int $reviewId, int $userId, array &$errors): ?array
{
    try {
        $stmt = db()->prepare(
            "SELECT
                r.review_id,
                r.review_title,
                r.review_content,
                r.status,
                r.created_at,
                b.title AS book_title,
                b.author AS book_author
            FROM dbProj_reviews r
            INNER JOIN dbProj_books b ON r.book_id = b.book_id
            WHERE r.review_id = :review_id
              AND r.user_id = :user_id
            LIMIT 1"
        );
        $stmt->execute([
            ':review_id' => $reviewId,
            ':user_id' => $userId,
        ]);
        $draft = $stmt->fetch();

        if ($draft === false) {
            $errors[] = 'Review was not found.';
            return null;
        }

        if ($draft['status'] !== 'draft') {
            $errors[] = 'Only draft reviews can be published.';
            return null;
        }

        return $draft;
    } catch (PDOException $exception) {
        $errors[] = 'Review could not be loaded. Check the database connection.';
        return null;
    }
}

function preview_text(string $text, int $limit = 260): string
{
    $cleanText = trim(strip_tags($text));

    if (strlen($cleanText) <= $limit) {
        return $cleanText;
    }

    return rtrim(substr($cleanText, 0, $limit), " \t\n\r\0\x0B.,") . '...';
}

page_header('Publish Review', '../');
?>

<section class="page-intro">
    <h1>Publish Review</h1>
    <p>Confirm that this draft is ready for viewers.</p>
</section>

<?php if ($successMessage !== ''): ?>
    <section class="notice notice-success">
        <p><?= e($successMessage) ?> <a href="index.php">Back to dashboard</a></p>
    </section>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <section class="notice notice-error" aria-labelledby="publish-errors-title">
        <h2 id="publish-errors-title">Cannot publish review</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <p><a href="index.php">Back to dashboard</a></p>
<?php elseif ($review !== null): ?>
    <section class="confirm-panel">
        <h2><?= e($review['review_title']) ?></h2>
        <p class="book-line"><?= e($review['book_title']) ?> by <?= e($review['book_author']) ?></p>
        <p><?= e(preview_text((string) $review['review_content'])) ?></p>
        <p class="muted-text">Created <?= e(date('M j, Y', strtotime((string) $review['created_at']))) ?></p>

        <form class="form-actions" method="post" action="publish.php?id=<?= e((string) $review['review_id']) ?>">
            <input type="hidden" name="review_id" value="<?= e((string) $review['review_id']) ?>">
            <button type="submit">Publish Review</button>
            <a href="index.php">Cancel</a>
        </form>
    </section>
<?php endif; ?>

<?php
page_footer();
