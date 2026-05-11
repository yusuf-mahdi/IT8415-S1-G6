<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

page_header('Home');
?>

<section class="page-intro">
    <h1>Latest Book Reviews</h1>
    <p>Browse recent book reviews, ratings, and creator recommendations.</p>
</section>

<section class="empty-state">
    <h2>Application setup started</h2>
    <p>The next Maha task will connect this page to the published reviews in MySQL.</p>
</section>

<?php
page_footer();
