<?php

// This is a standalone page for the customer queue display
$title = 'Customer Queue';

// Load settings for branding (used by view header)
$pdo = get_pdo($config);
$settings = load_settings($pdo);

require __DIR__ . '/queue-display.view.php';