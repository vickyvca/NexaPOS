<?php
// Basic configuration
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'swim_league');
define('DB_USER', 'root');
define('DB_PASS', '');

// Adjust if app is in a subfolder like /swim-league
define('BASE_URL', '/swim-league');

// Default pool length (meters) if not specified
define('DEFAULT_POOL_LENGTH_M', 50);
