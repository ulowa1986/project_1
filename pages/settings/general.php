<?php
// Include necessary files and initialize authentication
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if user is admin
if (!Auth::isAdmin()) {
    setMessage('Access denied. Administrative privileges required.', 'error');
    redirect('../../index.php');
}

$db = Database::getInstance();
$current_time = '2025-03-16 15:10:35'; // Using provided UTC time
$current_user = 'ulowa1986'; // Using provided username

// Initialize variables
$error = '';
$settings = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token');
        }

        // Basic settings
        $settings = [
            'site_name' => trim($_POST['site_name']),
            'site_url' => trim($_POST['site_url']),
            'site_email' => trim($_POST['site_email'] ?? ''),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
            'timezone' => trim($_POST['timezone'] ?? 'UTC'),
            'last_updated' => $current_time,
            'last_updated_by' => $current_user
        ];

        // Validate required fields
        if (empty($settings['site_name'])) {
            throw new Exception('Site name is required');
        }

        if (empty($settings['site_url']) || !filter_var($settings['site_url'], FILTER_VALIDATE_URL)) {
            throw new Exception('Valid site URL is required');
        }

        if (!empty($settings['site_email']) && !filter_var($settings['site_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Handle file uploads
        $uploadTypes = [
            'site_logo' => [
                'dir' => 'logos',
                'maxSize' => 5242880, // 5MB
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                'current' => $_POST['current_logo'] ?? ''
            ],
            'site_favicon' => [
                'dir' => 'favicon',
                'maxSize' => 1048576, // 1MB
                'allowedTypes' => ['image/x-icon', 'image/png', 'image/ico'],
                'current' => $_POST['current_favicon'] ?? ''
            ]
        ];

        foreach ($uploadTypes as $fileKey => $config) {
            if (!empty($_FILES[$fileKey]['name'])) {
                $uploadedFile = $_FILES[$fileKey];
                
                // Validate file size
                if ($uploadedFile['size'] > $config['maxSize']) {
                    throw new Exception("$fileKey exceeds maximum allowed size");
                }

                // Validate file type
                if (!in_array($uploadedFile['type'], $config['allowedTypes'])) {
                    throw new Exception("Invalid file type for $fileKey");
                }

                // Handle file upload
                $settings[$fileKey] = handleFileUpload($uploadedFile, $config['dir']);

                // Delete old file if exists
                if (!empty($config['current']) && $settings[$fileKey] !== $config['current']) {
                    deleteFile("assets/uploads/{$config['dir']}/{$config['current']}");
                }
            } else {
                $settings[$fileKey] = $config['current'];
            }
        }

        // Begin transaction
        $db->beginTransaction();

        // Update settings
        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) 
                 VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }

        // Log the changes
        $db->query(
            "INSERT INTO activity_logs (user_id, action, details, created_at) 
             VALUES (?, 'settings_update', ?, ?)",
            [Auth::getUserId(), 'General settings updated', $current_time]
        );

        $db->commit();
        setMessage('Settings updated successfully', 'success');
        redirect('index.php?page=settings');

    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
        // Restore previous settings
        $result = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        foreach ($result as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} else {
    // Fetch current settings
    $result = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Generate new CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>General Settings</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="settings-section">
                <h2>Site Information</h2>

                <div class="form-group">
                    <label for="site_name">Site Name:</label>
                    <input type="text" id="site_name" name="site_name" 
                           value="<?php echo sanitize($settings['site_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="site_url">Site URL:</label>
                    <input type="url" id="site_url" name="site_url" 
                           value="<?php echo sanitize($settings['site_url'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="site_email">Site Email:</label>
                    <input type="email" id="site_email" name="site_email" 
                           value="<?php echo sanitize($settings['site_email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="timezone">Timezone:</label>
                    <select id="timezone" name="timezone">
                        <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                            <option value="<?php echo sanitize($tz); ?>"
                                <?php echo ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : ''; ?>>
                                <?php echo sanitize($tz); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="settings-section">
                <h2>Site Assets</h2>
                
                <div class="form-group">
                    <label>Site Logo:</label>
                    <?php if (!empty($settings['site_logo'])): ?>
                        <div class="current-image">
                            <img src="../../assets/uploads/logos/<?php echo sanitize($settings['site_logo']); ?>" 
                                 alt="Current Logo" height="50">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" accept="image/jpeg,image/png,image/gif">
                    <input type="hidden" name="current_logo" 
                           value="<?php echo sanitize($settings['site_logo'] ?? ''); ?>">
                    <small>Max size: 5MB. Allowed types: JPG, PNG, GIF</small>
                </div>

                <div class="form-group">
                    <label>Favicon:</label>
                    <?php if (!empty($settings['site_favicon'])): ?>
                        <div class="current-image">
                            <img src="../../assets/uploads/favicon/<?php echo sanitize($settings['site_favicon']); ?>" 
                                 alt="Current Favicon" height="16">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" accept="image/x-icon,image/png">
                    <input type="hidden" name="current_favicon" 
                           value="<?php echo sanitize($settings['site_favicon'] ?? ''); ?>">
                    <small>Max size: 1MB. Allowed types: ICO, PNG</small>
                </div>
            </div>

            <div class="settings-section">
                <h2>Site Configuration</h2>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="maintenance_mode" 
                               <?php echo (!empty($settings['maintenance_mode'])) ? 'checked' : ''; ?>>
                        Enable Maintenance Mode
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allow_registration" 
                               <?php echo (!empty($settings['allow_registration'])) ? 'checked' : ''; ?>>
                        Allow New User Registration
                    </label>
                </div>
            </div>

            <div class="settings-section">
                <h2>System Information</h2>
                <div class="info-group">
                    <p><strong>Last Updated:</strong> 
                        <?php echo sanitize($settings['last_updated'] ?? 'Never'); ?>
                    </p>
                    <p><strong>Last Updated By:</strong> 
                        <?php echo sanitize($settings['last_updated_by'] ?? 'Unknown'); ?>
                    </p>
                    <p><strong>Current Time (UTC):</strong> 
                        <?php echo sanitize($current_time); ?>
                    </p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="../../assets/js/script.js"></script>
    <script>
        // Confirm before enabling maintenance mode
        document.querySelector('input[name="maintenance_mode"]').addEventListener('change', function(e) {
            if (this.checked && !confirm('Enabling maintenance mode will make the site inaccessible to regular users. Continue?')) {
                e.preventDefault();
                this.checked = false;
            }
        });

        // Preview uploaded images
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    const preview = this.parentElement.querySelector('.current-image img');
                    
                    reader.onload = function(e) {
                        if (preview) {
                            preview.src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.height = input.name === 'site_logo' ? 50 : 16;
                            const div = document.createElement('div');
                            div.className = 'current-image';
                            div.appendChild(img);
                            input.parentElement.insertBefore(div, input);
                        }
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
