<?php

declare(strict_types=1);

function page_header(string $title): void
{
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
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
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
