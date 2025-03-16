<?php
// Check if user is admin
if (!Auth::isAdmin()) {
    setMessage('Access denied', 'error');
    redirect('index.php');
}

$db = Database::getInstance();
$current_time = '2025-03-16 15:03:59';
$current_user = 'ulowa1986';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, underscores, and hyphens';
    }
    
    // Check if username exists
    $existing_user = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing_user) {
        $errors[] = 'Username already exists';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email exists
    $existing_email = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing_email) {
        $errors[] = 'Email already exists';
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password) || 
              !preg_match('/[a-z]/', $password) || 
              !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    }
    
    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $db->query(
                "INSERT INTO users (username, email, password, is_admin, created_at) 
                 VALUES (?, ?, ?, ?, ?)",
                [$username, $email, $hashed_password, $is_admin, $current_time]
            );
            
            $new_user_id = $db->lastInsertId();
            
            // Log the action
            $db->query(
                "INSERT INTO user_actions_log 
                (user_id, action_type, action_time, performed_by, details) 
                VALUES (?, ?, ?, ?, ?)",
                [
                    $new_user_id,
                    'create',
                    $current_time,
                    $_SESSION['user_id'],
                    json_encode([
                        'created_by' => $current_user,
                        'username' => $username,
                        'email' => $email,
                        'is_admin' => $is_admin
                    ])
                ]
            );
            
            // Commit transaction
            $db->commit();
            
            setMessage('User created successfully');
            redirect('index.php?page=users');
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            $errors[] = 'Error creating user: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-group {
            margin-top: 10px;
        }
        .checkbox-group label {
            display: inline;
            font-weight: normal;
        }
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 0.9em;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New User</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="addUserForm">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>"
                       pattern="[a-zA-Z0-9_-]{3,20}">
                <small>3-20 characters, letters, numbers, underscores, and hyphens only</small>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required
                       minlength="8">
                <div id="passwordStrength" class="password-strength"></div>
                <small>Minimum 8 characters, must include uppercase, lowercase, and number</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div id="passwordMatch"></div>
            </div>

            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="is_admin" value="1"
                           <?php echo isset($_POST['is_admin']) ? 'checked' : ''; ?>>
                    Grant admin privileges
                </label>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="index.php?page=users" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let message = '';

            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;

            switch(strength) {
                case 0:
                case 1:
                    message = '<span class="strength-weak">Weak</span>';
                    break;
                case 2:
                case 3:
                    message = '<span class="strength-medium">Medium</span>';
                    break;
                case 4:
                case 5:
                    message = '<span class="strength-strong">Strong</span>';
                    break;
            }

            strengthDiv.innerHTML = message;
        });

        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (this.value === password) {
                matchDiv.innerHTML = '<span class="strength-strong">Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak">Passwords do not match</span>';
            }
        });

        // Form validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html>
