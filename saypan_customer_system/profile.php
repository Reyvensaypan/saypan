<?php
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle image deletion
if (isset($_GET['delete_image']) && $_GET['delete_image'] === 'yes') {
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $deleted = false;
    
    foreach ($image_extensions as $ext) {
        $file = 'uploads/' . $id . '.' . $ext;
        if (file_exists($file)) {
            unlink($file);
            $deleted = true;
        }
    }
    
    if ($deleted) {
        $_SESSION['success'] = "Profile image deleted successfully.";
    } else {
        $_SESSION['error'] = "No profile image found.";
    }
    
    header("Location: profile.php?id=$id");
    exit;
}

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

// Find existing profile image
$image_path = null;
$image_exists = false;
$image_extensions = ['jpg', 'jpeg', 'png', 'gif'];

foreach ($image_extensions as $ext) {
    if (file_exists("uploads/" . $id . "." . $ext)) {
        $image_path = "uploads/" . $id . "." . $ext;
        $image_exists = true;
        break;
    }
}

// If no image found, use placeholder
if (!$image_exists) {
    $image_path = "https://via.placeholder.com/200?text=No+Image";
}
?>

<?php include("includes/header.php"); ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-teal text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0">👤 Customer Profile</h4>
        <a href="index.php" class="btn btn-light btn-sm">← Back to List</a>
    </div>
    <div class="card-body">
    
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="position-relative d-inline-block">
                    <img src="<?= $image_path ?>" alt="Profile Image" class="img-fluid rounded-circle border" style="width: 200px; height: 200px; object-fit: cover;">
                    
                    <?php if ($image_exists): ?>
                        <a href="profile.php?id=<?= $id ?>&delete_image=yes" 
                           class="btn btn-sm btn-danger position-absolute top-0 end-0"
                           onclick="return confirm('Are you sure you want to delete this profile image?')">
                            🗑️
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">Edit Profile</a>
                </div>
                
                <?php if (!$image_exists): ?>
                    <div class="mt-2">
                        <small class="text-muted">
                            ℹ️ No profile image. <a href="edit.php?id=<?= $id ?>">Upload one</a>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Customer Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 35%;">Customer Code</th>
                                <td><span class="badge bg-secondary fs-6"><?= htmlspecialchars($customer['customer_code']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><a href="mailto:<?= htmlspecialchars($customer['email']) ?>"><?= htmlspecialchars($customer['email']) ?></a></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td><?= htmlspecialchars($customer['contact_no']) ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= nl2br(htmlspecialchars($customer['address'] ?: 'No address provided')) ?></td>
                            </tr>
                            <tr>
                                <th>Member Since</th>
                                <td><?= date('F j, Y \a\t g:i A', strtotime($customer['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>