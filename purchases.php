<?php
session_start();

// Ensure inventory, sales, purchases exist
if (!isset($_SESSION['inventory'])) $_SESSION['inventory'] = [];
if (!isset($_SESSION['sales'])) $_SESSION['sales'] = [];
if (!isset($_SESSION['purchases'])) $_SESSION['purchases'] = [];

// Helper to create purchase IDs
function next_purchase_id() {
    $n = count($_SESSION['purchases']) + 1;
    return 'P' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $productId = $_POST['product'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        foreach ($_SESSION['inventory'] as &$invItem) {
            if ($invItem['id'] === $productId) {
                if ($quantity > 0) {
                    $invItem['stock'] += $quantity;
                    $_SESSION['purchases'][] = [
                        "purchase_id" => next_purchase_id(),
                        "product_id" => $invItem['id'],
                        "product_name" => $invItem['name'],
                        "quantity" => $quantity,
                        "price" => $price,
                        "total" => round($price * $quantity,2),
                        "date" => date("Y-m-d H:i:s")
                    ];
                }
                break;
            }
        }
        unset($invItem);
    } elseif ($action === 'delete') {
        $purchaseId = $_POST['purchase_id'] ?? '';
        $_SESSION['purchases'] = array_values(array_filter($_SESSION['purchases'], fn($p) => $p['purchase_id'] !== $purchaseId));
    }
}

$inventory = $_SESSION['inventory'];
$purchases = $_SESSION['purchases'];
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }
$purchaseDataForJs = json_encode($purchases, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Purchases Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {margin:0; font-family:'Segoe UI',sans-serif; background:#f0f2f5;}

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
.topbar{margin-left:70px; transition:.3s; background:#fff; padding:14px 20px; box-shadow:0 2px 10px rgba(0,0,0,.1); display:flex; justify-content:space-between; align-items:center; z-index:900;}
.sidebar:hover ~ .topbar{margin-left:220px;}

/* Content */
.content{margin-left:70px;transition:.3s;padding:20px;}
.sidebar:hover ~ .content{margin-left:220px;}

/* Cards / Table */
.card,.table-container{background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,.05);}
.table td,.table th{vertical-align:middle;}
.btn-add{background:#198754;color:#fff;}
.btn-add:hover{background:#157347;}
.btn-delete{background:#dc3545;color:#fff;}
.btn-delete:hover{background:#b02a37;}

/* Stats cards */
.stat-card{border-radius:12px; padding:20px; color:#fff;}
.stat-purchases{background:linear-gradient(45deg,#0d6efd,#6610f2);}
.stat-total{background:linear-gradient(45deg,#198754,#20c997);}

/* Charts */
.chart-card canvas{width:100%!important; height:280px!important;}
@media(max-width:576px){.chart-card canvas{height:220px!important;}}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="dashboard.php"><i class="bi bi-grid"></i><span class="nav-text">Dashboard</span></a>
    <a href="inventory.php"><i class="bi bi-box"></i><span class="nav-text">Inventory</span></a>
    <a href="sales.php"><i class="bi bi-cart"></i><span class="nav-text">Sales</span></a>
    <a class="active" href="purchases.php"><i class="bi bi-truck"></i><span class="nav-text">Purchases</span></a>
    <a href="reports.php"><i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span></a>
    <a href="index.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a>
</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <h5 class="m-0">Purchases Management</h5>
        <small class="text-muted">Track and add purchases</small>
    </div>
    <div class="d-flex gap-3">
        <?php
        $totalPurchases = array_reduce($purchases, fn($c,$p)=>$c+$p['total'],0);
        $countPurchases = count($purchases);
        ?>
        <div class="stat-card stat-purchases text-center">
            <div>Purchases</div>
            <strong style="font-size:1.3rem;"><?= $countPurchases ?></strong>
        </div>
        <div class="stat-card stat-total text-center">
            <div>Total Cost</div>
            <strong style="font-size:1.3rem;">RM <?= number_format($totalPurchases,2) ?></strong>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
<div class="container-fluid">
<div class="row g-4">

<!-- Purchases Table -->
<div class="col-lg-8">
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">All Purchases</h5>
            <small class="text-muted">Most recent first</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Purchase ID</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price (RM)</th>
                        <th>Total (RM)</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($purchases)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No purchases recorded yet.</td></tr>
                    <?php else: foreach(array_reverse($purchases) as $p): ?>
                        <tr>
                            <td><?= h($p['purchase_id']) ?></td>
                            <td><?= h($p['product_name']) ?></td>
                            <td><?= h($p['quantity']) ?></td>
                            <td><?= number_format($p['price'],2) ?></td>
                            <td><strong class="text-primary"><?= number_format($p['total'],2) ?></strong></td>
                            <td><?= h($p['date']) ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete purchase <?= h($p['purchase_id']) ?>?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="purchase_id" value="<?= h($p['purchase_id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Purchase Form -->
<div class="col-lg-4">
    <div class="card">
        <h5 class="mb-3">Add New Purchase</h5>
        <form method="POST" id="addPurchaseForm" onsubmit="return confirmAddPurchase();">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product" id="productSelect" class="form-select" required onchange="onProductChange()">
                    <option value="">-- Select product --</option>
                    <?php foreach($inventory as $it): ?>
                        <option value="<?= h($it['id']) ?>" data-price="<?= h(number_format($it['price'],2,'.','')) ?>">
                            <?= h($it['name']) ?> (Stock: <?= (int)$it['stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" id="quantityInput" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Price per Unit (RM)</label>
                <input type="text" id="priceInput" name="price" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Total (RM)</label>
                <input type="text" id="totalInput" class="form-control" readonly>
            </div>
            <button type="submit" class="btn btn-add w-100">Add Purchase</button>
        </form>
        <hr>
        <small class="text-muted">Purchases increase stock. Deleting a purchase does not restore stock.</small>
    </div>
</div>

</div>

<!-- Charts -->
<div class="row g-4 mt-4">
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center mb-3">Total Purchases per Product (RM)</h6>
            <canvas id="productPurchaseChart"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center mb-3">Purchase Cost Over Time</h6>
            <canvas id="purchaseTrendChart"></canvas>
        </div>
    </div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const productSelect = document.getElementById('productSelect');
const priceInput = document.getElementById('priceInput');
const quantityInput = document.getElementById('quantityInput');
const totalInput = document.getElementById('totalInput');

function onProductChange(){
    const opt = productSelect.options[productSelect.selectedIndex];
    if(!opt||!opt.value){ priceInput.value=''; totalInput.value=''; return; }
    priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
    totalInput.value='';
}

quantityInput.addEventListener('input', ()=>{
    const qty = parseInt(quantityInput.value)||0;
    const price = parseFloat(priceInput.value)||0;
    totalInput.value = qty>0?(qty*price).toFixed(2):'';
});

function confirmAddPurchase(){
    if(!productSelect.value){ alert('Select a product'); return false; }
    const qty = parseInt(quantityInput.value)||0;
    if(qty<=0){ alert('Quantity must be at least 1'); return false; }
    return true;
}

// Chart Data
const purchaseData = <?= $purchaseDataForJs ?>||[];

function groupSum(arr,key,sumKey){ return arr.reduce((acc,cur)=>{ const k=cur[key]||''; const v=parseFloat(cur[sumKey])||0; acc[k]=(acc[k]||0)+v; return acc; },{}); }

const productSums = groupSum(purchaseData,'product_name','total');
const prodLabels = Object.keys(productSums);
const prodTotals = Object.values(productSums);

const daily = {};
purchaseData.forEach(p=>{
    const day = (p.date||'').slice(0,10);
    if(!day) return;
    daily[day]=(daily[day]||0)+parseFloat(p.total||0);
});
const trendLabels = Object.keys(daily).sort();
const trendTotals = trendLabels.map(d=>daily[d]);

new Chart(document.getElementById('productPurchaseChart').getContext('2d'),{
    type:'bar',
    data:{ labels:prodLabels, datasets:[{ label:'Total Purchases', data:prodTotals, backgroundColor:['#0d6efd','#6610f2','#0dcaf0','#fd7e14','#198754','#dc3545'] }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

new Chart(document.getElementById('purchaseTrendChart').getContext('2d'),{
    type:'line',
    data:{ labels:trendLabels, datasets:[{ label:'Total Cost', data:trendTotals, borderColor:'#198754', backgroundColor:'rgba(25,135,84,0.12)', fill:true, tension:0.3 }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});
</script>
</body>
</html>
