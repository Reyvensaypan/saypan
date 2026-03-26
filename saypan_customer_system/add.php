<?php
require_once 'db.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      
    // Get and sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $contact    = trim($_POST['contact_no'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    $errors = [];

    // Validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($contact)) $errors[] = "Contact number is required.";

    // Check if email already exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        if ($check) {
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $errors[] = "Email already exists in the system.";
            }
            $check->close();
        } else {
            $errors[] = "Database prepare error: " . $conn->error;
        }
    }

    if (empty($errors)) {
        // Generate customer code (CUST-xxxx)
        $result = $conn->query("SELECT MAX(id) AS max_id FROM customers");
        if ($result) {
            $row = $result->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $customer_code = 'CUST-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
        } else {
            $customer_code = 'CUST-0001'; // Fallback
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO customers (customer_code, first_name, last_name, email, contact_no, address) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssssss", $customer_code, $first_name, $last_name, $email, $contact, $address);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                
                // Handle image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['profile_image']['type'];
                    $file_size = $_FILES['profile_image']['size'];
                    $tmp_name = $_FILES['profile_image']['tmp_name'];
                    
                    // Check file size (max 2MB)
                    if ($file_size > 2 * 1024 * 1024) {
                        $_SESSION['error'] = "File too large. Max size is 2MB.";
                        header("Location: add.php");
                        exit;
                    }
                    
                    if (in_array($file_type, $allowed_types)) {
                        // Get file extension
                        $extension = '';
                        if ($file_type == 'image/jpeg') $extension = 'jpg';
                        elseif ($file_type == 'image/png') $extension = 'png';
                        elseif ($file_type == 'image/gif') $extension = 'gif';
                        else $extension = 'jpg';
                        
                        $filename = $new_id . '.' . $extension;
                        $upload_path = 'uploads/' . $filename;
                        
                        // Create uploads folder if it doesn't exist
                        if (!file_exists('uploads')) {
                            mkdir('uploads', 0755, true);
                        }
                        
                        if (move_uploaded_file($tmp_name, $upload_path)) {
                            $_SESSION['success'] = "Customer added successfully with profile image!";
                        } else {
                            $_SESSION['success'] = "Customer added but image upload failed. Please check folder permissions.";
                        }
                    } else {
                        $_SESSION['success'] = "Customer added but invalid image type. Please use JPG, PNG, or GIF.";
                    }
                } else {
                    $_SESSION['success'] = "Customer added successfully!";
                }
                
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database prepare error: " . $conn->error;
        }
    }

    // If errors, store them
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: add.php");
        exit;
    }
}

// If not POST, include the form
include("includes/header.php");
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-teal text-white">
        <h4 class="mb-0">+Add New Customer</h4>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="add.php" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="contact_no" class="form-label">Contact Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="contact_no" name="contact_no" value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                
                <!-- Image Upload Field -->
                <div class="col-12">
                    <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                    <div class="form-text text-muted">
                        <small>📸 <strong>Allowed:</strong> JPG, PNG, GIF (Max 2MB)</small>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-teal">Save Customer</button>
                    <a href="index.php" class="btn btn-secondary">↩️ Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include("includes/footer.php"); ?>