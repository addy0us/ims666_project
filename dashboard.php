<?php
session_start();

// Ensure inventory, sales, purchases exist
if (!isset($_SESSION['inventory'])) $_SESSION['inventory'] = [];
if (!isset($_SESSION['sales'])) $_SESSION['sales'] = [];
if (!isset($_SESSION['purchases'])) $_SESSION['purchases'] = [];

// Escape helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }

// Metrics
$inventory = $_SESSION['inventory'];
$sales = $_SESSION['sales'];
$purchases = $_SESSION['purchases'];

$totalProducts = count($inventory);
$totalStock = array_sum(array_column($inventory,'stock'));
$lowStockItems = count(array_filter($inventory, fn($i)=>$i['stock']<5));
$totalSalesAmount = array_sum(array_column($sales,'total'));
$totalPurchasesAmount = array_sum(array_column($purchases,'total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard | Inventory System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    <a class="active" href="dashboard.php"><i class="bi bi-grid"></i><span class="nav-text">Dashboard</span></a>
    <a href="inventory.php"><i class="bi bi-box"></i><span class="nav-text">Inventory</span></a>
    <a href="sales.php"><i class="bi bi-cart"></i><span class="nav-text">Sales</span></a>
    <a href="purchases.php"><i class="bi bi-truck"></i><span class="nav-text">Purchases</span></a>
    <a href="reports.php"><i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span></a>
    <a href="index.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <h5 class="m-0">Dashboard Overview</h5>
    <small class="text-muted">Quick view of the whole system</small>
</div>

<!-- MAIN CONTENT -->
<div class="content">
<div class="container-fluid">

<!-- STAT BOXES -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-primary text-center"><h6>Total Products</h6><h2><?= $totalProducts ?></h2></div></div>
    <div class="col-md-3"><div class="card card-success text-center"><h6>Total Stock Units</h6><h2><?= $totalStock ?></h2></div></div>
    <div class="col-md-3"><div class="card card-warning text-center"><h6>Low Stock Items</h6><h2><?= $lowStockItems ?></h2></div></div>
    <div class="col-md-3"><div class="card card-danger text-center"><h6>Total Sales (RM)</h6><h2><?= number_format($totalSalesAmount,2) ?></h2></div></div>
</div>

<!-- CHARTS -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <h6 class="text-center mb-2">Stock Distribution</h6>
            <div class="chart-container">
                <canvas id="stockChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <h6 class="text-center mb-2">Category Breakdown</h6>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-3">
    <div class="col-lg-6">
        <div class="card">
            <h6 class="text-center mb-2">Sales Overview</h6>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <h6 class="text-center mb-2">Purchases Overview</h6>
            <div class="chart-container">
                <canvas id="purchasesChart"></canvas>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<!-- Scripts -->
<script>
const inventoryData = <?= json_encode($inventory) ?>;
const salesData = <?= json_encode($sales) ?>;
const purchasesData = <?= json_encode($purchases) ?>;

// Stock Chart
new Chart(document.getElementById('stockChart'), {
    type:'bar',
    data:{
        labels: inventoryData.map(i=>i.name),
        datasets:[{label:'Stock Units', data: inventoryData.map(i=>i.stock), backgroundColor:'#457b9d'}]
    },
    options:{responsive:true, maintainAspectRatio:false}
});

// Category Pie Chart
const categoryCounts = {};
inventoryData.forEach(i=>{ categoryCounts[i.category]=(categoryCounts[i.category]||0)+1; });
new Chart(document.getElementById('categoryChart'), {
    type:'pie',
    data:{labels:Object.keys(categoryCounts), datasets:[{data:Object.values(categoryCounts), backgroundColor:['#e63946','#457b9d','#2a9d8f','#f4a261','#8d99ae']}]},
    options:{responsive:true, maintainAspectRatio:false}
});

// Sales Chart
const salesLabels = salesData.map(s=>s.product_name);
const salesTotals = salesData.map(s=>s.total);
new Chart(document.getElementById('salesChart'), {
    type:'bar',
    data:{labels:salesLabels, datasets:[{label:'Sales Total (RM)', data:salesTotals, backgroundColor:'#2a9d8f'}]},
    options:{responsive:true, maintainAspectRatio:false}
});

// Purchases Chart
const purchaseLabels = purchasesData.map(p=>p.product_name);
const purchaseTotals = purchasesData.map(p=>p.total);
new Chart(document.getElementById('purchasesChart'), {
    type:'bar',
    data:{labels:purchaseLabels, datasets:[{label:'Purchases Total (RM)', data:purchaseTotals, backgroundColor:'#f4a261'}]},
    options:{responsive:true, maintainAspectRatio:false}
});
</script>

</body>
</html>
