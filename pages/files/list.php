<?php
if (!Auth::isLoggedIn()) {
    redirect('index.php');
}

// Initialize database
$db = Database::getInstance();

// Get current page and search parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = sanitize($_GET['search'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'created_at');
$order = sanitize($_GET['order'] ?? 'desc');
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Validate sort field to prevent SQL injection
$allowed_sort_fields = ['title', 'views', 'created_at', 'updated_at'];
if (!in_array($sort, $allowed_sort_fields)) {
    $sort = 'created_at';
}

// Build query
$params = [];
$where = '';
if ($search) {
    $where = "WHERE title LIKE ? OR slug LIKE ?";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Count total files for pagination
$count_query = "SELECT COUNT(*) as total FROM files " . $where;
$total_files = $db->fetch($count_query, $params)['total'];
$total_pages = ceil($total_files / $per_page);

// Get files
$query = "SELECT f.*, u.username 
          FROM files f 
          LEFT JOIN users u ON f.user_id = u.id 
          {$where} 
          ORDER BY {$sort} {$order} 
          LIMIT {$offset}, {$per_page}";

$files = $db->fetchAll($query, $params);

// Handle file deletion
if (isset($_POST['delete']) && isset($_POST['file_id'])) {
    $file_id = (int)$_POST['file_id'];
    
    // Check if user has permission to delete
    $file = $db->fetch("SELECT * FROM files WHERE id = ?", [$file_id]);
    if ($file && (Auth::isAdmin() || $file['user_id'] == $_SESSION['user_id'])) {
        try {
            $db->query("DELETE FROM files WHERE id = ?", [$file_id]);
            setMessage('File deleted successfully');
        } catch (Exception $e) {
            setMessage('Error deleting file', 'error');
        }
    } else {
        setMessage('Permission denied', 'error');
    }
    redirect('index.php?page=files');
}

// Generate sort URL helper
function getSortUrl($field) {
    global $sort, $order, $search;
    $new_order = ($sort === $field && $order === 'asc') ? 'desc' : 'asc';
    $params = [
        'page' => 'files',
        'sort' => $field,
        'order' => $new_order
    ];
    if ($search) {
        $params['search'] = $search;
    }
    return 'index.php?' . http_build_query($params);
}

// Get sort indicator
function getSortIndicator($field) {
    global $sort, $order;
    if ($sort === $field) {
        return $order === 'asc' ? ' ↑' : ' ↓';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files Management</title>
    <style>
        .files-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .files-table th, .files-table td { padding: 10px; border: 1px solid #ddd; }
        .files-table th { background: #f5f5f5; }
        .files-table tr:hover { background: #f9f9f9; }
        .pagination { margin: 20px 0; }
        .pagination a, .pagination span { 
            padding: 5px 10px; 
            margin: 0 5px; 
            border: 1px solid #ddd;
            text-decoration: none;
        }
        .pagination span { background: #f5f5f5; }
        .actions { display: flex; gap: 10px; }
        .search-box { margin: 20px 0; }
        .btn-add { 
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-edit {
            background: #007bff;
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .btn-view {
            background: #17a2b8;
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Files Management</h1>
        
        <?php $message = getMessage(); ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <a href="index.php?page=files&action=add" class="btn-add">Add New File</a>

        <form method="GET" class="search-box">
            <input type="hidden" name="page" value="files">
            <input type="search" name="search" value="<?php echo sanitize($search); ?>" 
                   placeholder="Search files...">
            <button type="submit">Search</button>
            <?php if ($search): ?>
                <a href="index.php?page=files">Clear</a>
            <?php endif; ?>
        </form>

        <table class="files-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>
                        <a href="<?php echo getSortUrl('title'); ?>">
                            Title<?php echo getSortIndicator('title'); ?>
                        </a>
                    </th>
                    <th>Created By</th>
                    <th>
                        <a href="<?php echo getSortUrl('views'); ?>">
                            Views<?php echo getSortIndicator('views'); ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo getSortUrl('created_at'); ?>">
                            Created<?php echo getSortIndicator('created_at'); ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <tr>
                    <td><?php echo $file['id']; ?></td>
                    <td><?php echo sanitize($file['title']); ?></td>
                    <td><?php echo sanitize($file['username']); ?></td>
                    <td><?php echo number_format($file['views']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($file['created_at'])); ?></td>
                    <td class="actions">
                        <a href="file/<?php echo $file['slug']; ?>" class="btn-view" target="_blank">View</a>
                        <?php if (Auth::isAdmin() || $file['user_id'] == $_SESSION['user_id']): ?>
                            <a href="index.php?page=files&action=edit&id=<?php echo $file['id']; ?>" 
                               class="btn-edit">Edit</a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this file?');">
                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                <button type="submit" name="delete" class="btn-delete">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($files)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No files found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index.php?page=files&<?php echo http_build_query(['search' => $search, 'sort' => $sort, 'order' => $order, 'page' => $page - 1]); ?>">
                    Previous
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="index.php?page=files&<?php echo http_build_query(['search' => $search, 'sort' => $sort, 'order' => $order, 'page' => $i]); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="index.php?page=files&<?php echo http_build_query(['search' => $search, 'sort' => $sort, 'order' => $order, 'page' => $page + 1]); ?>">
                    Next
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
