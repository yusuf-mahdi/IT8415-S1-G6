<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';

$errors = [];
$successMessage = '';
$form = [
    'username' => '',
    'email' => '',
    'role' => 'viewer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['username'] = trim((string) ($_POST['username'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['role'] = trim((string) ($_POST['role'] ?? 'viewer'));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($form['username'] === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $form['username'])) {
        $errors[] = 'Username must be 3-50 characters and use only letters, numbers, and underscores.';
    }

    if ($form['email'] === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!in_array($form['role'], ['viewer', 'creator'], true)) {
        $errors[] = 'Select a valid account type.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $checkStmt = db()->prepare(
                'SELECT username, email
                FROM dbProj_users
                WHERE username = :username OR email = :email
                LIMIT 1'
            );
            $checkStmt->execute([
                ':username' => $form['username'],
                ':email' => $form['email'],
            ]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser !== false) {
                if ($existingUser['username'] === $form['username']) {
                    $errors[] = 'Username is already taken.';
                }

                if ($existingUser['email'] === $form['email']) {
                    $errors[] = 'Email is already registered.';
                }
            }
        } catch (PDOException $exception) {
            $errors[] = 'Could not check existing accounts. Check the database connection.';
        }
    }

    if ($errors === []) {
        try {
            $insertStmt = db()->prepare(
                'INSERT INTO dbProj_users (username, email, password, role)
                VALUES (:username, :email, :password, :role)'
            );
            $insertStmt->execute([
                ':username' => $form['username'],
                ':email' => $form['email'],
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $form['role'],
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $exception) {
            $errors[] = 'Account could not be created. Try again later.';
        }
    }
}

page_header('Sign Up');
?>

<section class="page-intro">
    <h1>Create Account</h1>
    <p>Sign up as a viewer or content creator.</p>
</section>

<?php if ($successMessage !== ''): ?>
    <section class="notice notice-success">
        <p><?= e($successMessage) ?></p>
    </section>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <section class="notice notice-error" aria-labelledby="signup-errors-title">
        <h2 id="signup-errors-title">Fix these errors</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<form class="form-panel" id="signup-form" method="post" action="signup.php" novalidate>
    <div class="form-field">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= e($form['username']) ?>"
            minlength="3"
            maxlength="50"
            pattern="[A-Za-z0-9_]+"
            autocomplete="username"
            required
        >
        <p class="field-hint">Use 3-50 letters, numbers, or underscores.</p>
    </div>

    <div class="form-field">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= e($form['email']) ?>"
            maxlength="100"
            autocomplete="email"
            required
        >
    </div>

    <div class="form-field">
        <label for="role">Account type</label>
        <select id="role" name="role" required>
            <option value="viewer" <?= $form['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
            <option value="creator" <?= $form['role'] === 'creator' ? 'selected' : '' ?>>Content Creator</option>
        </select>
        <p class="field-hint">Admin accounts are managed separately.</p>
    </div>

    <div class="form-field">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >
    </div>

    <div class="form-field">
        <label for="confirm_password">Confirm password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            autocomplete="new-password"
            required
        >
    </div>

    <div class="form-actions">
        <button type="submit">Create Account</button>
        <a href="login.php">Already have an account?</a>
    </div>
</form>

<script src="assets/js/signup-validation.js"></script>

<?php
page_footer();
