<?php
function checkRequirements() {
    $requirements = array();
    
    // PHP Version
    $requirements['php_version'] = [
        'required' => '8.0.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.0.0', '>=')
    ];
    
    // Extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
    foreach ($required_extensions as $ext) {
        $requirements['extensions'][$ext] = extension_loaded($ext);
    }
    
    // Directory Permissions
    $directories = [
        '../assets/uploads',
        '../includes'
    ];
    
    foreach ($directories as $dir) {
        $requirements['directories'][$dir] = is_writable($dir);
    }
    
    return $requirements;
}

// Usage
$requirements = checkRequirements();
$can_proceed = !in_array(false, array_column($requirements, 'status'));
