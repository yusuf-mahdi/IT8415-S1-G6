<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role('creator', '../login.php', '../index.php');

$user = current_user();
$books = [];
$errors = [];
$form = [
    'book_id' => '',
    'review_title' => '',
    'review_content' => '',
];

try {
    $books = db()
        ->query('SELECT book_id, title, author FROM dbProj_books ORDER BY title')
        ->fetchAll();
} catch (PDOException $exception) {
    $errors[] = 'Books could not be loaded. Check the database connection.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['book_id'] = trim((string) ($_POST['book_id'] ?? ''));
    $form['review_title'] = trim((string) ($_POST['review_title'] ?? ''));
    $form['review_content'] = trim((string) ($_POST['review_content'] ?? ''));

    if ($form['book_id'] === '' || !ctype_digit($form['book_id'])) {
        $errors[] = 'Select a valid book.';
    }

    if ($form['review_title'] === '') {
        $errors[] = 'Review title is required.';
    } elseif (strlen($form['review_title']) > 200) {
        $errors[] = 'Review title must be 200 characters or less.';
    }

    if (strlen($form['review_content']) < 30) {
        $errors[] = 'Review content must be at least 30 characters.';
    }

    $coverPath = upload_file('cover_image', 'covers', ['image/jpeg', 'image/png', 'image/webp'], $errors);
    $mediaPath = upload_file('media_file', 'media', ['audio/mpeg', 'audio/wav', 'video/mp4', 'video/webm'], $errors);
    $downloadPath = upload_file(
        'downloadable_file',
        'downloads',
        ['application/pdf', 'text/plain', 'application/zip'],
        $errors
    );

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO dbProj_reviews
                    (book_id, user_id, review_title, review_content, cover_image, media_file, downloadable_file, status)
                VALUES
                    (:book_id, :user_id, :review_title, :review_content, :cover_image, :media_file, :downloadable_file, :status)'
            );
            $stmt->execute([
                ':book_id' => (int) $form['book_id'],
                ':user_id' => $user['id'],
                ':review_title' => $form['review_title'],
                ':review_content' => $form['review_content'],
                ':cover_image' => $coverPath,
                ':media_file' => $mediaPath,
                ':downloadable_file' => $downloadPath,
                ':status' => 'draft',
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            $errors[] = 'Draft could not be saved. Try again later.';
        }
    }
}

function upload_file(string $fieldName, string $folder, array $allowedTypes, array &$errors): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = readable_field_name($fieldName) . ' could not be uploaded.';
        return null;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = readable_field_name($fieldName) . ' must be 10MB or smaller.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file((string) $file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true)) {
        $errors[] = readable_field_name($fieldName) . ' type is not allowed.';
        return null;
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    $targetDir = __DIR__ . '/../uploads/' . $folder;
    $targetPath = $targetDir . '/' . $safeName;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        $errors[] = readable_field_name($fieldName) . ' folder could not be created.';
        return null;
    }

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        $errors[] = readable_field_name($fieldName) . ' could not be saved.';
        return null;
    }

    return 'uploads/' . $folder . '/' . $safeName;
}

function readable_field_name(string $fieldName): string
{
    return ucwords(str_replace('_', ' ', $fieldName));
}

page_header('Add Review', '../');
?>

<section class="page-intro">
    <h1>Add Review Draft</h1>
    <p>Create a draft review. Publishing will be handled in the next creator task.</p>
</section>

<?php if ($errors !== []): ?>
    <section class="notice notice-error" aria-labelledby="create-errors-title">
        <h2 id="create-errors-title">Fix these errors</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<form class="form-panel form-panel-wide" id="review-form" method="post" action="create.php" enctype="multipart/form-data" novalidate>
    <div class="form-field">
        <label for="book_id">Book</label>
        <select id="book_id" name="book_id" required>
            <option value="">Select a book</option>
            <?php foreach ($books as $book): ?>
                <option value="<?= e((string) $book['book_id']) ?>" <?= $form['book_id'] === (string) $book['book_id'] ? 'selected' : '' ?>>
                    <?= e($book['title']) ?> by <?= e($book['author']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-field">
        <label for="review_title">Review title</label>
        <input
            type="text"
            id="review_title"
            name="review_title"
            value="<?= e($form['review_title']) ?>"
            maxlength="200"
            required
        >
    </div>

    <div class="form-field">
        <label for="review_content">Review content</label>
        <textarea id="review_content" name="review_content" rows="9" minlength="30" required><?= e($form['review_content']) ?></textarea>
        <p class="field-hint">Write at least 30 characters before saving.</p>
    </div>

    <div class="form-field">
        <label for="cover_image">Cover image</label>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp">
        <p class="field-hint">Optional. JPG, PNG, or WebP. Maximum 10MB.</p>
    </div>

    <div class="form-field">
        <label for="media_file">Media file</label>
        <input type="file" id="media_file" name="media_file" accept="audio/mpeg,audio/wav,video/mp4,video/webm">
        <p class="field-hint">Optional. MP3, WAV, MP4, or WebM. Maximum 10MB.</p>
    </div>

    <div class="form-field">
        <label for="downloadable_file">Downloadable file</label>
        <input type="file" id="downloadable_file" name="downloadable_file" accept="application/pdf,text/plain,application/zip">
        <p class="field-hint">Optional. PDF, TXT, or ZIP. Maximum 10MB.</p>
    </div>

    <div class="form-actions">
        <button type="submit">Save Draft</button>
        <a href="index.php">Cancel</a>
    </div>
</form>

<script src="../assets/js/review-form-validation.js"></script>

<?php
page_footer();
