<?php
if (!Auth::isLoggedIn()) {
    redirect('index.php');
}

$db = Database::getInstance();
$file_id = (int)($_GET['id'] ?? 0);
$current_user = 'ulowa1986'; // Current user from context
$current_time = '2025-03-16 15:00:55'; // Current timestamp from context

// Get file data
$file = $db->fetch(
    "SELECT f.*, u.username as creator 
     FROM files f 
     LEFT JOIN users u ON f.user_id = u.id 
     WHERE f.id = ?", 
    [$file_id]
);

// Check if file exists and user has permission to delete
if (!$file) {
    setMessage('File not found', 'error');
    redirect('index.php?page=files');
}

if (!Auth::isAdmin() && $file['user_id'] != $_SESSION['user_id']) {
    setMessage('Permission denied', 'error');
    redirect('index.php?page=files');
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        try {
            // Start transaction
            $db->beginTransaction();

            // Log the deletion
            $db->query(
                "INSERT INTO file_actions_log 
                (file_id, user_id, action_type, action_time, file_data) 
                VALUES (?, ?, ?, ?, ?)",
                [
                    $file_id,
                    $_SESSION['user_id'],
                    'delete',
                    $current_time,
                    json_encode([
                        'title' => $file['title'],
                        'slug' => $file['slug'],
                        'content' => $file['content'],
                        'views' => $file['views'],
                        'deleted_by' => $current_user,
                        'deletion_time' => $current_time
                    ])
                ]
            );

            // Delete file
            $db->query("DELETE FROM files WHERE id = ?", [$file_id]);

            // Commit transaction
            $db->commit();

            setMessage('File deleted successfully');
            redirect('index.php?page=files');
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            setMessage('Error deleting file: ' . $e->getMessage(), 'error');
            redirect('index.php?page=files');
        }
    } else {
        // User clicked "No"
        redirect('index.php?page=files');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete File - <?php echo sanitize($file['title']); ?></title>
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .delete-confirmation {
            background: #fff3f3;
            border: 1px solid #ffd7d7;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .file-info p {
            margin: 5px 0;
        }
        .buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        .warning-icon {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="delete-confirmation">
            <div class="warning-icon">⚠️</div>
            <h2>Delete File</h2>
            <p>Are you sure you want to delete this file? This action cannot be undone.</p>
            
            <div class="file-info">
                <p><strong>Title:</strong> <?php echo sanitize($file['title']); ?></p>
                <p><strong>Created by:</strong> <?php echo sanitize($file['creator']); ?></p>
                <p><strong>Created at:</strong> <?php echo date('Y-m-d H:i:s', strtotime($file['created_at'])); ?></p>
                <p><strong>Views:</strong> <?php echo number_format($file['views']); ?></p>
                <p><strong>Last updated:</strong> <?php echo date('Y-m-d H:i:s', strtotime($file['updated_at'])); ?></p>
            </div>

            <form method="POST" class="buttons">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-delete">Yes, Delete File</button>
                <a href="index.php?page=files" class="btn btn-cancel">No, Keep File</a>
            </form>
        </div>

        <?php if (Auth::isAdmin()): ?>
        <div class="admin-info">
            <small>
                Admin Note: A log entry will be created with the following information:
                <ul>
                    <li>Deletion time: <?php echo $current_time; ?></li>
                    <li>Deleted by: <?php echo $current_user; ?></li>
                    <li>File ID: <?php echo $file_id; ?></li>
                </ul>
            </small>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
