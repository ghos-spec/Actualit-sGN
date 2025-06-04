<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

/**
 * Generate a URL friendly slug from a string.
 * @param string $string The input string.
 * @return string The generated slug.
 */
function createSlug($string) {
    // Convert to lowercase
    $slug = strtolower($string);
    // Remove accents
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
    // Remove unwanted characters, leave only letters, numbers, and spaces
    $slug = preg_replace('/[^\pL\pN\s]/', '', $slug);
    // Replace spaces with hyphens
    $slug = preg_replace('/\s+/', '-', $slug);
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    return $slug;
}

try {
    // Get all categories
    $stmt = $conn->query("SELECT id, name, slug FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated_count = 0;

    foreach ($categories as $category) {
        // Generate new slug from name
        $new_slug = createSlug($category['name']);

        // Update the category with the new slug
        if (!empty($new_slug)) {
            $update_stmt = $conn->prepare("UPDATE categories SET slug = ? WHERE id = ?");
            $update_stmt->execute([$new_slug, $category['id']]);
            $updated_count++;
            echo "Updated slug for category ID: " . $category['id'] . " (Name: " . htmlspecialchars($category['name']) . ") to " . htmlspecialchars($new_slug) . "<br>";
        } else {
            echo "Could not generate slug for category ID: " . $category['id'] . " (Name: " . htmlspecialchars($category['name']) . ") because the name is empty or invalid.<br>";
        }
    }

    if ($updated_count > 0) {
        echo "<br><b>Finished updating slugs. Total updated: " . $updated_count . "</b>";
    } else {
        echo "<br><b>No slugs were updated.</b>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 