<?php
session_start();

// Ensure inventory, sales, purchases exist
if (!isset($_SESSION['inventory'])) $_SESSION['inventory'] = [];
if (!isset($_SESSION['sales'])) $_SESSION['sales'] = [];
if (!isset($_SESSION['purchases'])) $_SESSION['purchases'] = [];


// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action == 'add') {
        $_SESSION['inventory'][] = [
            "id" => $_POST['id'],
            "name" => $_POST['name'],
            "category" => $_POST['category'],
            "stock" => (int)$_POST['stock'],
            "price" => (float)$_POST['price']
        ];
    } elseif ($action == 'edit') {
        foreach ($_SESSION['inventory'] as &$item) {
            if ($item['id'] == $_POST['id']) {
                $item['name'] = $_POST['name'];
                $item['category'] = $_POST['category'];
                $item['stock'] = (int)$_POST['stock'];
                $item['price'] = (float)$_POST['price'];
                break;
            }
        }
    } elseif ($action == 'delete') {
        $_SESSION['inventory'] = array_filter($_SESSION['inventory'], fn($i) => $i['id'] != $_POST['id']);
        $_SESSION['inventory'] = array_values($_SESSION['inventory']);
    }
}

$categories = ["Breakfast", "Lunch", "Ramadhan - Iftar", "Ramadhan - Moreh", "Event"];
$inventoryItems = $_SESSION['inventory'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f0f2f5; }

/* Sidebar */
.sidebar {
    position: fixed; top:0; left:0; height:100vh; width:70px;
    background: linear-gradient(180deg, #000000ff, #000000ff);
    padding-top:18px; overflow-x:hidden;
    transition:width .3s ease; z-index:1000;
}
.sidebar:hover { width:220px; }
.sidebar a {
    display:flex; align-items:center; gap:12px;
    padding:14px 20px; color:#d1d1d1; text-decoration:none; font-size:15px;
    transition:all .3s;
}
.sidebar a i { font-size:18px; }
.sidebar a:hover, .sidebar .active { background:#ff6b6b; color:#fff; }
.sidebar .nav-text { opacity:0; transition:opacity .2s; white-space:nowrap; }
.sidebar:hover .nav-text { opacity:1; }

/* Topbar */
.topbar {
    margin-left:70px; transition:margin-left .3s ease;
    background: linear-gradient(90deg, #ffffff, #e9ecef);
    padding:14px 20px; box-shadow:0 3px 10px rgba(0,0,0,0.08);
    display:flex; justify-content:space-between; align-items:center; z-index:900;
}
.sidebar:hover ~ .topbar { margin-left:220px; }

/* Content */
.content { margin-left:70px; transition:margin-left .3s ease; padding:20px; }
.sidebar:hover ~ .content { margin-left:220px; }

/* Cards */
.card {
    border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08);
    padding:20px; transition:transform .3s, box-shadow .3s;
}
.card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }

/* Card colors */
.card-primary { background:#457b9d; color:#fff; }
.card-success { background:#2a9d8f; color:#fff; }
.card-warning { background:#f4a261; color:#fff; }
.card-danger { background:#e63946; color:#fff; }

/* Chart container */
.chart-container { height:220px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:10px; background:#fff; }

/* Responsive */
@media (max-width:992px) { 
    .sidebar { position:relative; width:220px; height:auto; } 
    .topbar,.content{margin-left:0;} 
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="dashboard.php"><i class="bi bi-grid"></i><span class="nav-text">Dashboard</span></a>
    <a class="active" href="inventory.php"><i class="bi bi-box"></i><span class="nav-text">Inventory</span></a>
    <a href="sales.php"><i class="bi bi-cart"></i><span class="nav-text">Sales</span></a>
    <a href="purchases.php"><i class="bi bi-truck"></i><span class="nav-text">Purchases</span></a>
    <a href="reports.php"><i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span></a>
    <a href="index.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <h5 class="m-0">Inventory Management</h5>
    <small class="text-muted">Manage your products</small>
</div>

<!-- MAIN CONTENT -->
<div class="content">
<div class="container-fluid">
    <div class="row mb-4">
        <!-- Inventory Table -->
        <div class="col-lg-8">
            <div class="table-container">
                <h5 class="mb-3">All Inventory Items</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th><th>Name</th><th>Category</th>
                                <th>Stock</th><th>Price ($)</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($inventoryItems as $item): ?>
                            <tr>
                                <td><?= $item['id'] ?></td>
                                <td><?= $item['name'] ?></td>
                                <td><?= $item['category'] ?></td>
                                <td class="<?= $item['stock']<10?'low-stock':'' ?>"><?= $item['stock'] ?></td>
                                <td><?= number_format($item['price'],2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $item['id'] ?>">Edit</button>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $item['id'] ?>" tabindex="-1">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Edit <?= $item['name'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <div class="mb-2"><input type="text" name="name" class="form-control" value="<?= $item['name'] ?>" required></div>
                                        <div class="mb-2">
                                            <select name="category" class="form-control" required>
                                                <option value="">Select Category</option>
                                                <?php foreach($categories as $cat): ?>
                                                    <option value="<?= $cat ?>" <?= $item['category']==$cat?"selected":"" ?>><?= $cat ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2"><input type="number" name="stock" class="form-control" value="<?= $item['stock'] ?>" required></div>
                                        <div class="mb-2"><input type="number" step="0.01" name="price" class="form-control" value="<?= $item['price'] ?>" required></div>
                                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                                    </form>
                                  </div>
                                </div>
                              </div>
                            </div>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Product Form -->
        <div class="col-lg-4">
            <div class="card p-3">
                <h5 class="mb-3">Add New Product</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2"><input type="text" name="id" class="form-control" placeholder="Item ID" required></div>
                    <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Item Name" required></div>
                    <div class="mb-2">
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="number" name="stock" class="form-control" placeholder="Stock" required></div>
                    <div class="mb-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required></div>
                    <button type="submit" class="btn btn-success w-100">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
