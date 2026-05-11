<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function page_header(string $title): void
{
    $currentUser = current_user();
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $safeTitle ?> | Book Review Platform</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="index.php">Book Review Platform</a>
            <nav class="site-nav" aria-label="Primary navigation">
                <a href="index.php">Home</a>
                <a href="search.php">Search</a>
                <?php if ($currentUser !== null): ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="admin/index.php">Admin</a>
                    <?php elseif ($currentUser['role'] === 'creator'): ?>
                        <a href="creator/index.php">Creator</a>
                    <?php endif; ?>
                    <span class="nav-user"><?= e($currentUser['username']) ?></span>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Sign Up</a>
                <?php endif; ?>
            </nav>
        </header>
        <main class="page-shell">
    <?php
}

function page_footer(): void
{
    ?>
        </main>
        <footer class="site-footer">
            <p>&copy; <?= date('Y') ?> Book Review Platform</p>
        </footer>
    </body>
    </html>
    <?php
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
