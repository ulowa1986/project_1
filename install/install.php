<?php
session_start();
$step = $_GET['step'] ?? 1;

if ($step == 1) {
    // Requirements check
    require_once 'check.php';
    $requirements = checkRequirements();
    $can_proceed = !in_array(false, array_column($requirements, 'status'));
} elseif ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database setup
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Import schema
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        
        $_SESSION['db_config'] = compact('db_host', 'db_name', 'db_user', 'db_pass');
        header('Location: install.php?step=3');
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
} elseif ($step == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Site setup
    $site_url = $_POST['site_url'];
    $site_name = $_POST['site_name'];
    $admin_user = $_POST['admin_user'];
    $admin_email = $_POST['admin_email'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
    
    try {
        $db = new PDO(
            "mysql:host=" . $_SESSION['db_config']['db_host'] . ";dbname=" . $_SESSION['db_config']['db_name'],
            $_SESSION['db_config']['db_user'],
            $_SESSION['db_config']['db_pass']
        );
        
        // Create admin user
        $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute([$admin_user, $admin_email, $admin_pass]);
        
        // Save site settings
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['site_url', $site_url]);
        $stmt->execute(['site_name', $site_name]);
        
        // Create config file
        $config = "<?php\n";
        $config .= "define('DB_HOST', '" . addslashes($_SESSION['db_config']['db_host']) . "');\n";
        $config .= "define('DB_NAME', '" . addslashes($_SESSION['db_config']['db_name']) . "');\n";
        $config .= "define('DB_USER', '" . addslashes($_SESSION['db_config']['db_user']) . "');\n";
        $config .= "define('DB_PASS', '" . addslashes($_SESSION['db_config']['db_pass']) . "');\n";
        $config .= "define('SITE_URL', '" . addslashes($site_url) . "');\n";
        $config .= "define('SITE_NAME', '" . addslashes($site_name) . "');\n";
        
        file_put_contents('../includes/config.php', $config);
        
        header('Location: install.php?step=4');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
