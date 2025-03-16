<?php
if (!Auth::isAdmin()) {
    redirect('index.php');
}

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'],
        'site_url' => $_POST['site_url'],
        'site_logo' => $_FILES['site_logo']['name'] ? 
            handleFileUpload($_FILES['site_logo'], 'logos') : $_POST['current_logo'],
        'site_favicon' => $_FILES['site_favicon']['name'] ? 
            handleFileUpload($_FILES['site_favicon'], 'favicon') : $_POST['current_favicon']
    ];
    
    foreach ($settings as $key => $value) {
        $db->query(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
    }
    
    setMessage('Settings updated successfully');
    redirect('index.php?page=settings');
}

$settings = [];
$result = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<form method="POST" enctype="multipart/form-data">
    <div>
        <label>Site Name:</label>
        <input type="text" name="site_name" value="<?php echo sanitize($settings['site_name'] ?? ''); ?>">
    </div>
    
    <div>
        <label>Site URL:</label>
        <input type="url" name="site_url" value="<?php echo sanitize($settings['site_url'] ?? ''); ?>">
    </div>
    
    <div>
        <label>Site Logo:</label>
        <?php if (!empty($settings['site_logo'])): ?>
            <img src="assets/uploads/logos/<?php echo $settings['site_logo']; ?>" height="50">
        <?php endif; ?>
        <input type="file" name="site_logo">
        <input type="hidden" name="current_logo" value="<?php echo $settings['site_logo'] ?? ''; ?>">
    </div>
    
    <div>
        <label>Favicon:</label>
        <?php if (!empty($settings['site_favicon'])): ?>
            <img src="assets/uploads/favicon/<?php echo $settings['site_favicon']; ?>" height="16">
        <?php endif; ?>
        <input type="file" name="site_favicon">
        <input type="hidden" name="current_favicon" value="<?php echo $settings['site_favicon'] ?? ''; ?>">
    </div>
    
    <button type="submit">Save Settings</button>
</form>
