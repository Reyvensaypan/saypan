<?php
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch customer data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    $_SESSION['error'] = "Customer not found.";
    header("Location: index.php");
    exit;
}

// Find current profile image
$current_image = null;
$image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
foreach ($image_extensions as $ext) {
    if (file_exists('uploads/' . $id . '.' . $ext)) {
        $current_image = $id . '.' . $ext;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $contact    = trim($_POST['contact_no'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    $errors = [];

    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($contact)) $errors[] = "Contact number is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE customers SET first_name=?, last_name=?, email=?, contact_no=?, address=? WHERE id=?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $contact, $address, $id);

        if ($stmt->execute()) {
            // Handle image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_image']['type'];
                $file_size = $_FILES['profile_image']['size'];
                
                // Check file size (max 2MB)
                if ($file_size > 2 * 1024 * 1024) {
                    $_SESSION['error'] = "File too large. Max size is 2MB.";
                    header("Location: edit.php?id=$id");
                    exit;
                }
                
                if (in_array($file_type, $allowed_types)) {
                    // Get file extension
                    if ($file_type == 'image/jpeg') $extension = 'jpg';
                    elseif ($file_type == 'image/png') $extension = 'png';
                    elseif ($file_type == 'image/gif') $extension = 'gif';
                    else $extension = 'jpg';
                    
                    $filename = $id . '.' . $extension;
                    $upload_path = 'uploads/' . $filename;
                    
                    // Create uploads folder if it doesn't exist
                    if (!file_exists('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    
                    // Delete old image (all extensions)
                    foreach ($image_extensions as $ext) {
                        $old_file = 'uploads/' . $id . '.' . $ext;
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $_SESSION['success'] = "Customer updated successfully with new profile image!";
                    } else {
                        $_SESSION['success'] = "Customer updated but image upload failed.";
                    }
                } else {
                    $_SESSION['success'] = "Customer updated but invalid image type. Please use JPG, PNG, or GIF.";
                }
            } else {
                $_SESSION['success'] = "Customer updated successfully!";
            }
            
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: edit.php?id=$id");
        exit;
    }
}
?>

<?php include("includes/header.php"); ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-teal text-white">
        <h4 class="mb-0"> Edit Customer (<?= htmlspecialchars($customer['customer_code']) ?>)</h4>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3 text-center mb-3">
                <?php if ($current_image): ?>
                    <div class="position-relative d-inline-block">
                        <img src="uploads/<?= $current_image ?>" alt="Profile" class="img-fluid rounded-circle border" style="width: 150px; height: 150px; object-fit: cover;">
                        <br>
                        <small class="text-muted">Current Image</small>
                    </div>
                <?php else: ?>
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto border" style="width: 150px; height: 150px;">
                        <span class="text-muted">No Image</span>
                    </div>
                    <small class="text-muted">No profile image</small>
                <?php endif; ?>
            </div>
            <div class="col-md-9">
                <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_no" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?= htmlspecialchars($customer['contact_no']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                        </div>
                        
                        <!-- Image Upload Field -->
                        <div class="col-12">
                            <label for="profile_image" class="form-label">Change Profile Image (Optional)</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                            <div class="form-text text-muted">
                                <small>📸 <strong>Leave empty to keep current image.</strong> Allowed: JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-teal">Update Customer</button>
                            <a href="index.php" class="btn btn-secondary">↩️ Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>