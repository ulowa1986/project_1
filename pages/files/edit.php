<?php
if (!Auth::isLoggedIn()) {
    redirect('index.php');
}

$db = Database::getInstance();
$file_id = (int)($_GET['id'] ?? 0);

// Get file data
$file = $db->fetch("SELECT * FROM files WHERE id = ?", [$file_id]);

// Check if file exists and user has permission to edit
if (!$file || (!Auth::isAdmin() && $file['user_id'] != $_SESSION['user_id'])) {
    setMessage('Permission denied or file not found', 'error');
    redirect('index.php?page=files');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $slug = createSlug($_POST['slug'] ?: $title);
    $contents = [];
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    // Check if slug is unique (excluding current file)
    $existing = $db->fetch(
        "SELECT id FROM files WHERE slug = ? AND id != ?", 
        [$slug, $file_id]
    );
    
    if ($existing) {
        $errors[] = 'A file with this slug already exists';
    }
    
    // Process content sections
    if (isset($_POST['content']) && is_array($_POST['content'])) {
        foreach ($_POST['content'] as $content) {
            if (trim($content) !== '') {
                $contents[] = trim($content);
            }
        }
    }
    
    if (empty($contents)) {
        $errors[] = 'At least one content section is required';
    }
    
    // If no errors, update the file
    if (empty($errors)) {
        try {
            $db->query(
                "UPDATE files SET 
                    title = ?, 
                    slug = ?, 
                    content = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?",
                [
                    $title,
                    $slug,
                    json_encode($contents),
                    $file_id
                ]
            );
            
            // Log the edit
            $current_time = '2025-03-16 14:59:32'; // Using provided timestamp
            $current_user = 'ulowa1986';           // Using provided username
            
            $db->query(
                "INSERT INTO file_edits (file_id, user_id, edit_time, description) 
                 VALUES (?, ?, ?, ?)",
                [
                    $file_id,
                    $_SESSION['user_id'],
                    $current_time,
                    "File edited by {$current_user}"
                ]
            );
            
            setMessage('File updated successfully');
            redirect('index.php?page=files');
        } catch (Exception $e) {
            $errors[] = 'Error updating file: ' . $e->getMessage();
        }
    }
}

// Decode existing content
$file_contents = json_decode($file['content'], true) ?: [''];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit File</title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 0 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea { min-height: 100px; margin-bottom: 10px; }
        .content-section { margin-bottom: 15px; position: relative; }
        .remove-section {
            position: absolute;
            right: 0;
            top: 0;
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .add-section {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-left: 10px;
        }
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-list ul { margin: 0; padding-left: 20px; }
        .preview { margin-top: 20px; }
        .preview-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .meta-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit File</h1>
        
        <div class="meta-info">
            <p>Created: <?php echo date('Y-m-d H:i:s', strtotime($file['created_at'])); ?></p>
            <p>Last Updated: <?php echo date('Y-m-d H:i:s', strtotime($file['updated_at'])); ?></p>
            <p>Views: <?php echo number_format($file['views']); ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="editForm">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required
                       value="<?php echo sanitize($file['title']); ?>">
            </div>

            <div class="form-group">
                <label for="slug">Slug (optional):</label>
                <input type="text" id="slug" name="slug"
                       value="<?php echo sanitize($file['slug']); ?>">
                <small>Leave empty to generate from title</small>
            </div>

            <div id="contentSections">
                <?php foreach ($file_contents as $index => $content): ?>
                    <div class="content-section">
                        <label>Content Section <?php echo $index + 1; ?>:</label>
                        <textarea name="content[]" required><?php echo sanitize($content); ?></textarea>
                        <?php if ($index > 0): ?>
                            <button type="button" class="remove-section" 
                                    onclick="this.parentElement.remove()">Remove</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="add-section" onclick="addContentSection()">
                Add Content Section
            </button>

            <div class="form-group">
                <button type="submit" class="submit-btn">Update File</button>
                <a href="index.php?page=files" class="cancel-btn">Cancel</a>
            </div>
        </form>

        <div class="preview">
            <h3>Preview:</h3>
            <div class="preview-box" id="preview"></div>
        </div>
    </div>

    <script>
        function addContentSection() {
            const container = document.getElementById('contentSections');
            const sections = container.getElementsByClassName('content-section');
            const newSection = document.createElement('div');
            newSection.className = 'content-section';
            newSection.innerHTML = `
                <label>Content Section ${sections.length + 1}:</label>
                <textarea name="content[]" required></textarea>
                <button type="button" class="remove-section" 
                        onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(newSection);
        }

        // Live preview
        document.querySelectorAll('textarea[name="content[]"]').forEach(textarea => {
            textarea.addEventListener('input', updatePreview);
        });
        document.getElementById('title').addEventListener('input', updatePreview);

        function updatePreview() {
            const title = document.getElementById('title').value;
            const contents = Array.from(document.querySelectorAll('textarea[name="content[]"]'))
                                .map(ta => ta.value);
            
            const preview = document.getElementById('preview');
            preview.innerHTML = `
                <h2>${escapeHtml(title)}</h2>
                ${contents.map(content => `<p>${escapeHtml(content)}</p>`).join('')}
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial preview
        updatePreview();
    </script>
</body>
</html>
