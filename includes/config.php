<?php
session_start();

// Database Configuration
define('DB_HOST', '');  // Set during installation
define('DB_NAME', '');  // Set during installation
define('DB_USER', '');  // Set during installation
define('DB_PASS', '');  // Set during installation

// Application Configuration
define('SITE_URL', '');     // Set during installation
define('SITE_NAME', '');    // Set during installation
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time Zone
date_default_timezone_set('UTC');
