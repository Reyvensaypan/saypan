<?php
require_once 'db.php';

// Delete record based on id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // First delete the profile image if exists
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($image_extensions as $ext) {
        $image_file = 'uploads/' . $id . '.' . $ext;
        if (file_exists($image_file)) {
            unlink($image_file);
        }
    }
    
    // Then delete the customer record
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Customer and associated image deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting customer: " . $conn->error;
    }
} else {
    $_SESSION['error'] = "Invalid customer ID.";
}
// Redirect to index.php after deletion 
header("Location: index.php");
exit;
?>