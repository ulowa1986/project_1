<?php
if (!Auth::isAdmin()) {
    redirect('index.php');
}

$db = Database::getInstance();
$users = $db->fetchAll("SELECT id, username, email, created_at FROM users");
?>

<div class="users-list">
    <h2>User Management</h2>
    <a href="index.php?page=users&action=add" class="btn">Add New User</a>
    
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo sanitize($user['username']); ?></td>
                <td><?php echo sanitize($user['email']); ?></td>
                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                <td>
                    <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>">Edit</a>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <a href="index.php?page=users&action=delete&id=<?php echo $user['id']; ?>" 
                       onclick="return confirm('Are you sure?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
