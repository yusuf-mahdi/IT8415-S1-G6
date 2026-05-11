<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('admin', '../login.php', '../index.php');

$errors = [];
$reportRows = [];
$form = [
    'start_date' => $_GET['start_date'] ?? date('Y-01-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
];

if (isset($_GET['start_date'], $_GET['end_date'])) {
    $startDate = trim((string) $form['start_date']);
    $endDate = trim((string) $form['end_date']);

    if (!is_valid_date($startDate)) {
        $errors[] = 'Start date is required and must be valid.';
    }

    if (!is_valid_date($endDate)) {
        $errors[] = 'End date is required and must be valid.';
    }

    if ($errors === [] && $startDate > $endDate) {
        $errors[] = 'Start date must be before or equal to end date.';
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare('CALL dbProj_GetPopularReviews(:start_date, :end_date)');
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->execute();
            $reportRows = $stmt->fetchAll();
            $stmt->closeCursor();
        } catch (PDOException $exception) {
            $errors[] = 'Popular reviews report could not be generated.';
        }
    }
}

function is_valid_date(string $date): bool
{
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate instanceof DateTime && $parsedDate->format('Y-m-d') === $date;
}

page_header('Popular Reviews Report', '../');
?>

<section class="page-intro">
    <h1>Popular Reviews Report</h1>
    <p>Generate the most popular published reviews within a selected date range.</p>
</section>

<?php if ($errors !== []): ?>
    <section class="notice notice-error" aria-labelledby="report-errors-title">
        <h2 id="report-errors-title">Report error</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<form class="form-panel report-form" method="get" action="popular_report.php" novalidate>
    <div class="form-field">
        <label for="start_date">Start date</label>
        <input type="date" id="start_date" name="start_date" value="<?= e((string) $form['start_date']) ?>" required>
    </div>

    <div class="form-field">
        <label for="end_date">End date</label>
        <input type="date" id="end_date" name="end_date" value="<?= e((string) $form['end_date']) ?>" required>
    </div>

    <div class="form-actions">
        <button type="submit">Generate Report</button>
        <a href="index.php">Back to admin dashboard</a>
    </div>
</form>

<?php if (isset($_GET['start_date'], $_GET['end_date']) && $errors === []): ?>
    <section class="table-panel" aria-labelledby="popular-report-title">
        <div class="section-heading">
            <h2 id="popular-report-title">Report Results</h2>
            <p class="muted-text"><?= e((string) $form['start_date']) ?> to <?= e((string) $form['end_date']) ?></p>
        </div>

        <?php if ($reportRows === []): ?>
            <section class="empty-state">
                <h2>No results</h2>
                <p>No published reviews were found in this date range.</p>
            </section>
        <?php else: ?>
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Review</th>
                            <th scope="col">Creator</th>
                            <th scope="col">Book</th>
                            <th scope="col">Average Rating</th>
                            <th scope="col">Views</th>
                            <th scope="col">Created</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?= e($row['review_title']) ?></td>
                                <td><?= e($row['creator']) ?></td>
                                <td><?= e($row['book_title']) ?></td>
                                <td><?= e($row['avg_rating'] !== null ? (string) round((float) $row['avg_rating'], 1) : 'No ratings') ?></td>
                                <td><?= e((string) $row['view_count']) ?></td>
                                <td><?= e(date('M j, Y', strtotime((string) $row['created_at']))) ?></td>
                                <td><a href="../review.php?id=<?= e((string) $row['review_id']) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
page_footer();
