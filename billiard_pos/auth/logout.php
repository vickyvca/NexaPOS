<?php
require_once __DIR__ . '/../config.php';
session_destroy();
header('Location: /billiard_pos/auth/login.php');
exit;
