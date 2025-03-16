<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Simple routing
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Security check
if (!Auth::isLoggedIn() && $page != 'login') {
    redirect('login.php');
}

// Admin area check
$admin_pages = ['users', 'settings'];
if (in_array($page, $admin_pages) && !Auth::isAdmin()) {
    setMessage('Access denied', 'error');
    redirect('dashboard.php');
}

// Include the requested page
$file = "pages/{$page}/{$action}.php";
if (file_exists($file)) {
    require_once $file;
} else {
    require_once 'pages/404.php';
}
