<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $slug = createSlug($_POST['slug'] ?: $title);
    
    $db = Database::getInstance();
    
    try {
        $db->query(
            "INSERT INTO files (title, slug, content, user_id) VALUES (?, ?, ?, ?)",
            [$title, $slug, $_POST['content'], $_SESSION['user_id']]
        );
        setMessage('File created successfully');
        redirect('index.php?page=files');
    } catch (Exception $e) {
        setMessage($e->getMessage(), 'error');
    }
}
?>

<form method="POST" class="file-form">
    <div>
        <label>Title:</label>
        <input type="text" name="title" required>
    </div>
    
    <div>
        <label>Slug (optional):</label>
        <input type="text" name="slug">
    </div>
    
    <div id="content-fields">
        <div class="content-field">
            <label>Content:</label>
            <textarea name="content[]"></textarea>
            <button type="button" onclick="addContentField()">Add More</button>
        </div>
    </div>
    
    <button type="submit">Save File</button>
</form>

<script>
function addContentField() {
    const container = document.getElementById('content-fields');
    const field = document.createElement('div');
    field.className = 'content-field';
    field.innerHTML = `
        <label>Additional Content:</label>
        <textarea name="content[]"></textarea>
        <button type="button" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(field);
}
</script>
