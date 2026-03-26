<?php
require_once 'db.php';

// Handle search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$order_by = '';

switch($sort) {
    case 'name_asc':
        $order_by = "ORDER BY first_name ASC, last_name ASC";
        break;
    case 'name_desc':
        $order_by = "ORDER BY first_name DESC, last_name DESC";
        break;
    case 'latest':
    default:
        $order_by = "ORDER BY created_at DESC";
        break;
}

$sql = "SELECT * FROM customers";
if (!empty($search)) {
    $searchEsc = $conn->real_escape_string($search);
    $sql .= " WHERE first_name LIKE '%$searchEsc%' 
              OR last_name LIKE '%$searchEsc%' 
              OR email LIKE '%$searchEsc%' 
              OR contact_no LIKE '%$searchEsc%'";
}
$sql .= " " . $order_by;
$result = $conn->query($sql);
?>

<?php include("includes/header.php"); ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-teal text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">📋<strong> Customer Information System</strong></h4>
            <a href="add.php" class="btn btn-light btn-sm">+Add New Customer</a>
        </div>
        <div class="card-body">

            <!-- Search Form -->
            <form method="GET" action="index.php" class="row g-2 mb-4">
                <div class="col-md-10">
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email or contact..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-teal">🔍 Search</button>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="col-12 text-end">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Sorting Options Below Search Bar -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Sort by:</span>
                        <a href="?sort=latest<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" 
                           class="btn btn-sm <?= $sort == 'latest' ? 'btn-teal' : 'btn-outline-secondary' ?>">
                            📅 Latest
                        </a>
                        <a href="?sort=name_asc<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" 
                           class="btn btn-sm <?= $sort == 'name_asc' ? 'btn-teal' : 'btn-outline-secondary' ?>">
                            🔤 Name A-Z
                        </a>
                        <a href="?sort=name_desc<?= !empty($search) ? '&q='.urlencode($search) : '' ?>" 
                           class="btn btn-sm <?= $sort == 'name_desc' ? 'btn-teal' : 'btn-outline-secondary' ?>">
                            🔤 Name Z-A
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
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

            <!-- Results count -->
            <div class="mb-3">
                <small class="text-muted">
                    📊 Showing <?= $result->num_rows ?> customer(s)
                </small>
            </div>

            <!-- Customers Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-header-teal">
                        <tr>
                            <th>Profile</th>
                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                // Find profile image
                                $profile_image = null;
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                                foreach ($image_extensions as $ext) {
                                    if (file_exists('uploads/' . $row['id'] . '.' . $ext)) {
                                        $profile_image = $row['id'] . '.' . $ext;
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($profile_image): ?>
                                            <img src="uploads/<?= $profile_image ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" class="border">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;" class="border">
                                                <span class="text-muted" style="font-size: 10px;">No img</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['customer_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                    <td><?= htmlspecialchars(substr($row['address'], 0, 30)) . (strlen($row['address']) > 30 ? '…' : '') ?></td>
                                    <td>
                                        <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white" style="background-color: #0dcaf0;">👤 View</a>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"> Edit</a>
                                        <!-- Confirmation prompt  -->
                                        <a href="delete.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')"> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include("includes/footer.php"); ?>