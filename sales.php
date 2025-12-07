<?php
session_start();

// Ensure inventory, sales, purchases exist
if (!isset($_SESSION['inventory'])) $_SESSION['inventory'] = [];
if (!isset($_SESSION['sales'])) $_SESSION['sales'] = [];
if (!isset($_SESSION['purchases'])) $_SESSION['purchases'] = [];

// Helper: next sale ID
function next_sale_id() {
    $n = count($_SESSION['sales']) + 1;
    return 'S' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $productId = $_POST['product'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);

        foreach ($_SESSION['inventory'] as &$invItem) {
            if ($invItem['id'] === $productId) {
                if ($quantity > 0 && $quantity <= $invItem['stock']) {
                    $invItem['stock'] -= $quantity;
                    $price = (float)$invItem['price'];
                    $_SESSION['sales'][] = [
                        "sale_id" => next_sale_id(),
                        "product_id" => $invItem['id'],
                        "product_name" => $invItem['name'],
                        "quantity" => $quantity,
                        "price" => $price,
                        "total" => round($price * $quantity, 2),
                        "date" => date("Y-m-d H:i:s")
                    ];
                }
                break;
            }
        }
        unset($invItem);
    } elseif ($action === 'delete') {
        $saleId = $_POST['sale_id'] ?? '';
        $_SESSION['sales'] = array_values(array_filter($_SESSION['sales'], fn($s) => $s['sale_id'] !== $saleId));
    }
}

$inventory = $_SESSION['inventory'];
$sales = $_SESSION['sales'];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }

// Quick stats
$totalSales = array_sum(array_column($sales, 'total'));
$countSales = count($sales);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Dashboard</title>
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
    <a href="inventory.php"><i class="bi bi-box"></i><span class="nav-text">Inventory</span></a>
    <a class="active" href="sales.php"><i class="bi bi-cart"></i><span class="nav-text">Sales</span></a>
    <a href="purchases.php"><i class="bi bi-truck"></i><span class="nav-text">Purchases</span></a>
    <a href="reports.php"><i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span></a>
    <a href="index.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <h5 class="m-0">Sales Dashboard</h5>
    <small class="text-muted">Record and track sales</small>
</div>

<!-- MAIN CONTENT -->
<div class="content container-fluid">
    <!-- Metric cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-sales">
                <h6>Total Sales</h6>
                <h2><?= $countSales ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-total">
                <h6>Total Revenue (RM)</h6>
                <h2><?= number_format($totalSales,2) ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sales Table -->
        <div class="col-lg-8">
            <div class="table-container">
                <h5 class="mb-3">All Sales</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sale ID</th><th>Product</th><th>Qty</th><th>Price (RM)</th><th>Total (RM)</th><th>Date</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($sales)): ?>
                                <tr><td colspan="7" class="text-center text-muted">No sales yet.</td></tr>
                            <?php else: foreach(array_reverse($sales) as $s): ?>
                                <tr>
                                    <td><?= h($s['sale_id']) ?></td>
                                    <td><?= h($s['product_name']) ?></td>
                                    <td><?= h($s['quantity']) ?></td>
                                    <td><?= number_format($s['price'],2) ?></td>
                                    <td><?= number_format($s['total'],2) ?></td>
                                    <td><?= h($s['date']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete <?= h($s['sale_id']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="sale_id" value="<?= h($s['sale_id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Sale Form -->
        <div class="col-lg-4">
            <div class="card">
                <h5 class="mb-3">Add New Sale</h5>
                <form method="POST" id="addSaleForm" onsubmit="return confirmAddSale();">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product" id="productSelect" class="form-select" required onchange="onProductChange()">
                            <option value="">-- Select product --</option>
                            <?php foreach ($inventory as $it): ?>
                                <option value="<?= h($it['id']) ?>" data-price="<?= h(number_format((float)$it['price'],2,'.','')) ?>" data-stock="<?= (int)$it['stock'] ?>">
                                    <?= h($it['name']) ?> (Stock: <?= (int)$it['stock'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" id="quantityInput" name="quantity" class="form-control" min="1" required disabled>
                        <div id="stockHelp" class="form-text text-muted">Select a product to set max quantity.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (RM)</label>
                        <input type="text" id="priceInput" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total (RM)</label>
                        <input type="text" id="totalInput" class="form-control" readonly>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Add Sale</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const productSelect = document.getElementById('productSelect');
const priceInput = document.getElementById('priceInput');
const totalInput = document.getElementById('totalInput');
const quantityInput = document.getElementById('quantityInput');
const stockHelp = document.getElementById('stockHelp');

function onProductChange(){
    const opt = productSelect.options[productSelect.selectedIndex];
    if (!opt || !opt.value){
        priceInput.value=''; totalInput.value=''; quantityInput.value=''; quantityInput.disabled=true; quantityInput.removeAttribute('max'); stockHelp.textContent='Select a product to set max quantity.'; return;
    }
    const price = parseFloat(opt.dataset.price)||0;
    const stock = parseInt(opt.dataset.stock)||0;
    priceInput.value=price.toFixed(2); totalInput.value=''; quantityInput.value=''; quantityInput.disabled=false; quantityInput.setAttribute('max',stock); quantityInput.placeholder='Max: '+stock; stockHelp.textContent='Available stock: '+stock;
}

quantityInput.addEventListener('input', function(){
    const qty=parseInt(quantityInput.value)||0;
    const price=parseFloat(priceInput.value)||0;
    const max=parseInt(quantityInput.getAttribute('max'))||0;
    if(qty>max){ totalInput.value='Exceeds stock'; }
    else if(qty<=0){ totalInput.value=''; }
    else{ totalInput.value=(qty*price).toFixed(2); }
});

function confirmAddSale(){
    const opt = productSelect.options[productSelect.selectedIndex];
    if(!opt||!opt.value){ alert('Please select a product.'); return false; }
    const stock = parseInt(opt.dataset.stock)||0;
    const qty = parseInt(quantityInput.value)||0;
    if(qty<=0){ alert('Quantity must be at least 1.'); return false; }
    if(qty>stock){ alert('Quantity exceeds stock ('+stock+').'); return false; }
    return true;
}
</script>
</body>
</html>

