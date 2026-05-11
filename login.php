<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    redirect_after_login((string) current_user()['role']);
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'SELECT user_id, username, password, role
                FROM dbProj_users
                WHERE username = :username
                LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user === false) {
                $errors[] = 'Invalid username or password.';
            } else {
                $storedPassword = (string) $user['password'];
                $passwordMatches = password_verify($password, $storedPassword);

                if (!$passwordMatches && hash_equals($storedPassword, md5($password))) {
                    $passwordMatches = true;
                    $updateStmt = db()->prepare(
                        'UPDATE dbProj_users
                        SET password = :password
                        WHERE user_id = :user_id'
                    );
                    $updateStmt->execute([
                        ':password' => password_hash($password, PASSWORD_DEFAULT),
                        ':user_id' => $user['user_id'],
                    ]);
                }

                if (!$passwordMatches) {
                    $errors[] = 'Invalid username or password.';
                } else {
                    login_user($user);
                    redirect_after_login((string) $user['role']);
                }
            }
        } catch (PDOException $exception) {
            $errors[] = 'Login could not be completed. Check the database connection.';
        }
    }
}

page_header('Login');
?>

<section class="page-intro">
    <h1>Login</h1>
    <p>Access your creator, viewer, or admin account.</p>
</section>

<?php if ($errors !== []): ?>
    <section class="notice notice-error" aria-labelledby="login-errors-title">
        <h2 id="login-errors-title">Login failed</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<form class="form-panel" method="post" action="login.php" novalidate>
    <div class="form-field">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= e($username) ?>"
            autocomplete="username"
            required
        >
    </div>

    <div class="form-field">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >
    </div>

    <div class="form-actions">
        <button type="submit">Login</button>
        <a href="signup.php">Create an account</a>
    </div>
</form>

<?php
page_footer();
