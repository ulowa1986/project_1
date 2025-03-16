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
$current_time = '2025-03-16 15:12:24';
$current_user = 'ulowa1986';

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
            'ads_enabled' => isset($_POST['ads_enabled']) ? '1' : '0',
            'ads_header_enabled' => isset($_POST['ads_header_enabled']) ? '1' : '0',
            'ads_sidebar_enabled' => isset($_POST['ads_sidebar_enabled']) ? '1' : '0',
            'ads_footer_enabled' => isset($_POST['ads_footer_enabled']) ? '1' : '0',
            'ads_inpost_enabled' => isset($_POST['ads_inpost_enabled']) ? '1' : '0',
            'ads_header_code' => trim($_POST['ads_header_code'] ?? ''),
            'ads_sidebar_code' => trim($_POST['ads_sidebar_code'] ?? ''),
            'ads_footer_code' => trim($_POST['ads_footer_code'] ?? ''),
            'ads_inpost_code' => trim($_POST['ads_inpost_code'] ?? ''),
            'ads_custom_css' => trim($_POST['ads_custom_css'] ?? ''),
            'ads_excluded_pages' => trim($_POST['ads_excluded_pages'] ?? ''),
            'ads_user_roles_excluded' => isset($_POST['ads_user_roles_excluded']) ? 
                implode(',', $_POST['ads_user_roles_excluded']) : '',
            'last_updated' => $current_time,
            'last_updated_by' => $current_user
        ];

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
            [Auth::getUserId(), 'Advertisement settings updated', $current_time]
        );

        $db->commit();
        setMessage('Advertisement settings updated successfully', 'success');
        redirect('index.php?page=settings/ads');

    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
} else {
    // Fetch current settings
    $result = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ads_%'");
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Generate new CSRF token
$csrf_token = generateCSRFToken();

// Available user roles for exclusion
$available_roles = ['subscriber', 'editor', 'admin', 'premium'];
$excluded_roles = explode(',', $settings['ads_user_roles_excluded'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Settings</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .code-editor {
            font-family: monospace;
            height: 150px;
            width: 100%;
            padding: 10px;
            margin: 5px 0;
        }
        .preview-frame {
            width: 100%;
            height: 150px;
            border: 1px solid #ddd;
            margin: 10px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Advertisement Settings</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="settings-section">
                <h2>General Advertisement Settings</h2>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ads_enabled" 
                               <?php echo (!empty($settings['ads_enabled'])) ? 'checked' : ''; ?>>
                        Enable Advertisements Globally
                    </label>
                </div>

                <div class="form-group">
                    <label>Excluded Pages (one URL per line):</label>
                    <textarea name="ads_excluded_pages" rows="4" class="code-editor"
                    ><?php echo sanitize($settings['ads_excluded_pages'] ?? ''); ?></textarea>
                    <small>Enter page URLs to exclude from showing ads (e.g., /contact, /about)</small>
                </div>

                <div class="form-group">
                    <label>Exclude Advertisements for User Roles:</label>
                    <?php foreach ($available_roles as $role): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="ads_user_roles_excluded[]" 
                                   value="<?php echo sanitize($role); ?>"
                                   <?php echo in_array($role, $excluded_roles) ? 'checked' : ''; ?>>
                            <?php echo ucfirst(sanitize($role)); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="settings-section">
                <h2>Advertisement Locations</h2>

                <!-- Header Ads -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ads_header_enabled" 
                               <?php echo (!empty($settings['ads_header_enabled'])) ? 'checked' : ''; ?>>
                        Enable Header Advertisement
                    </label>
                    <textarea name="ads_header_code" class="code-editor" 
                              placeholder="Enter header advertisement code here..."
                    ><?php echo sanitize($settings['ads_header_code'] ?? ''); ?></textarea>
                    <button type="button" class="btn btn-secondary preview-btn" 
                            data-target="header-preview">Preview</button>
                    <iframe id="header-preview" class="preview-frame"></iframe>
                </div>

                <!-- Sidebar Ads -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ads_sidebar_enabled" 
                               <?php echo (!empty($settings['ads_sidebar_enabled'])) ? 'checked' : ''; ?>>
                        Enable Sidebar Advertisement
                    </label>
                    <textarea name="ads_sidebar_code" class="code-editor" 
                              placeholder="Enter sidebar advertisement code here..."
                    ><?php echo sanitize($settings['ads_sidebar_code'] ?? ''); ?></textarea>
                    <button type="button" class="btn btn-secondary preview-btn" 
                            data-target="sidebar-preview">Preview</button>
                    <iframe id="sidebar-preview" class="preview-frame"></iframe>
                </div>

                <!-- In-post Ads -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ads_inpost_enabled" 
                               <?php echo (!empty($settings['ads_inpost_enabled'])) ? 'checked' : ''; ?>>
                        Enable In-post Advertisement
                    </label>
                    <textarea name="ads_inpost_code" class="code-editor" 
                              placeholder="Enter in-post advertisement code here..."
                    ><?php echo sanitize($settings['ads_inpost_code'] ?? ''); ?></textarea>
                    <button type="button" class="btn btn-secondary preview-btn" 
                            data-target="inpost-preview">Preview</button>
                    <iframe id="inpost-preview" class="preview-frame"></iframe>
                </div>

                <!-- Footer Ads -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ads_footer_enabled" 
                               <?php echo (!empty($settings['ads_footer_enabled'])) ? 'checked' : ''; ?>>
                        Enable Footer Advertisement
                    </label>
                    <textarea name="ads_footer_code" class="code-editor" 
                              placeholder="Enter footer advertisement code here..."
                    ><?php echo sanitize($settings['ads_footer_code'] ?? ''); ?></textarea>
                    <button type="button" class="btn btn-secondary preview-btn" 
                            data-target="footer-preview">Preview</button>
                    <iframe id="footer-preview" class="preview-frame"></iframe>
                </div>
            </div>

            <div class="settings-section">
                <h2>Custom CSS for Advertisements</h2>
                <div class="form-group">
                    <textarea name="ads_custom_css" class="code-editor" 
                              placeholder="Enter custom CSS for advertisements..."
                    ><?php echo sanitize($settings['ads_custom_css'] ?? ''); ?></textarea>
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
        // Preview functionality for ad codes
        document.querySelectorAll('.preview-btn').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const iframe = document.getElementById(targetId);
                const codeArea = this.previousElementSibling;
                const customCSS = document.querySelector('[name="ads_custom_css"]').value;
                
                iframe.style.display = iframe.style.display === 'none' ? 'block' : 'none';
                
                if (iframe.style.display === 'block') {
                    const html = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>${customCSS}</style>
                        </head>
                        <body>
                            ${codeArea.value}
                        </body>
                        </html>
                    `;
                    iframe.srcdoc = html;
                }
            });
        });

        // Toggle global ads enable/disable
        document.querySelector('input[name="ads_enabled"]').addEventListener('change', function() {
            const adInputs = document.querySelectorAll('input[name$="_enabled"]');
            adInputs.forEach(input => {
                if (input !== this) {
                    input.disabled = !this.checked;
                }
            });
        });

        // Initialize global ads toggle state
        const globalAdsEnabled = document.querySelector('input[name="ads_enabled"]').checked;
        const adInputs = document.querySelectorAll('input[name$="_enabled"]');
        adInputs.forEach(input => {
            if (input.name !== 'ads_enabled') {
                input.disabled = !globalAdsEnabled;
            }
        });
    </script>
</body>
</html>
