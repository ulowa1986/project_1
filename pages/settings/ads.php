<?php
if (!Auth::isAdmin()) {
    redirect('index.php');
}

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $js_enabled = isset($_POST['js_enabled']) ? 1 : 0;
    $banner_enabled = isset($_POST['banner_enabled']) ? 1 : 0;
    
    $js_code = $_POST['js_code'] ? encodeAdContent($_POST['js_code']) : '';
    $banner_code = $_POST['banner_code'] ? encodeAdContent($_POST['banner_code']) : '';
    
    $db->query("
        INSERT INTO advertisements (ad_type, ad_content, is_enabled) 
        VALUES 
        ('javascript', ?, ?),
        ('banner', ?, ?)
        ON DUPLICATE KEY UPDATE 
        ad_content = VALUES(ad_content),
        is_enabled = VALUES(is_enabled)
    ", [$js_code, $js_enabled, $banner_code, $banner_enabled]);
    
    setMessage('Advertisement settings updated');
    redirect('index.php?page=settings&action=ads');
}

$ads = [];
$result = $db->fetchAll("SELECT * FROM advertisements");
foreach ($result as $ad) {
    $ads[$ad['ad_type']] = [
        'content' => decodeAdContent($ad['ad_content']),
        'enabled' => $ad['is_enabled']
    ];
}
?>

<form method="POST">
    <div>
        <label>
            <input type="checkbox" name="js_enabled" 
                   <?php echo ($ads['javascript']['enabled'] ?? false) ? 'checked' : ''; ?>>
            Enable JavaScript Ads
        </label>
        <textarea name="js_code"><?php echo sanitize($ads['javascript']['content'] ?? ''); ?></textarea>
    </div>
    
    <div>
        <label>
            <input type="checkbox" name="banner_enabled"
                   <?php echo ($ads['banner']['enabled'] ?? false) ? 'checked' : ''; ?>>
            Enable Banner Ads
        </label>
        <textarea name="banner_code"><?php echo sanitize($ads['banner']['content'] ?? ''); ?></textarea>
    </div>
    
    <button type="submit">Save Advertisement Settings</button>
</form>
