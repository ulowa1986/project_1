<?php
// Generate slug from title
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Base64 encode/decode for ads
function encodeAdContent($content) {
    return base64_encode($content);
}

function decodeAdContent($content) {
    return base64_decode($content);
}

// Simple redirect
function redirect($page) {
    header("Location: $page");
    exit;
}

// Flash messages
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}
