<?php
// Get statistics
$db = Database::getInstance();

// Total files
$total_files = $db->fetch("SELECT COUNT(*) as total FROM files")['total'];

// Today's views
$today_views = $db->fetch("
    SELECT SUM(views) as total 
    FROM files 
    WHERE DATE(created_at) = CURDATE()
")['total'] ?? 0;

// Top 10 files
$top_files = $db->fetchAll("
    SELECT title, views 
    FROM files 
    ORDER BY views DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="stats">
            <div class="stat-box">
                <h3>Total Files</h3>
                <p><?php echo $total_files; ?></p>
            </div>
            <div class="stat-box">
                <h3>Today's Views</h3>
                <p><?php echo $today_views; ?></p>
            </div>
        </div>
        
        <div class="top-files">
            <h3>Top 10 Files</h3>
            <ul>
                <?php foreach ($top_files as $file): ?>
                    <li>
                        <?php echo sanitize($file['title']); ?> 
                        (<?php echo $file['views']; ?> views)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
