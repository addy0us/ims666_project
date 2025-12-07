<?php
session_start();

// Ensure sessions exist
if (!isset($_SESSION['inventory'])) $_SESSION['inventory'] = [];
if (!isset($_SESSION['sales'])) $_SESSION['sales'] = [];
if (!isset($_SESSION['purchases'])) $_SESSION['purchases'] = [];

$inventory = $_SESSION['inventory'];
$sales = $_SESSION['sales'];
$purchases = $_SESSION['purchases'];

// Escape helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }

// Inventory metrics
$totalProducts = count($inventory);
$totalStock = array_sum(array_column($inventory,'stock'));
$lowStockItems = array_filter($inventory, fn($i)=>$i['stock']<5);

// Sales metrics
$totalSales = array_reduce($sales, fn($c,$s)=>$c+$s['total'],0);
$countSales = count($sales);

// Purchases metrics
$totalPurchases = array_reduce($purchases, fn($c,$p)=>$c+$p['total'],0);
$countPurchases = count($purchases);

// JSON for charts
$inventoryJson = json_encode($inventory);
$salesJson = json_encode($sales);
$purchasesJson = json_encode($purchases);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Reports Dashboard</title>

<!-- Bootstrap + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f4f6f8; }

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

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="dashboard.php"><i class="bi bi-grid"></i><span class="nav-text">Dashboard</span></a>
    <a href="inventory.php"><i class="bi bi-box"></i><span class="nav-text">Inventory</span></a>
    <a href="sales.php"><i class="bi bi-cart"></i><span class="nav-text">Sales</span></a>
    <a href="purchases.php"><i class="bi bi-truck"></i><span class="nav-text">Purchases</span></a>
    <a href="reports.php"><i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span></a>
    <a href="index.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a>
</div>


<!-- TOPBAR -->
<div class="topbar">
    <div>
        <h5 class="m-0">Reports Dashboard</h5>
        <small class="text-muted">Overview of Inventory, Sales, and Purchases</small>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="content">
<div class="container-fluid">

<!-- METRICS -->
<div class="row g-4">
    <div class="col-md-3"><div class="card card-blue text-center"><h6>Total Products</h6><h2><?= $totalProducts ?></h2></div></div>
    <div class="col-md-3"><div class="card card-green text-center"><h6>Total Stock Units</h6><h2><?= $totalStock ?></h2></div></div>
    <div class="col-md-3"><div class="card card-red text-center"><h6>Low Stock (&lt;5)</h6><h2><?= count($lowStockItems) ?></h2></div></div>
    <div class="col-md-3"><div class="card card-orange text-center"><h6>Total Sales (RM)</h6><h2><?= number_format($totalSales,2) ?></h2></div></div>
    <div class="col-md-3"><div class="card card-blue text-center"><h6>Sales Count</h6><h2><?= $countSales ?></h2></div></div>
    <div class="col-md-3"><div class="card card-green text-center"><h6>Total Purchases (RM)</h6><h2><?= number_format($totalPurchases,2) ?></h2></div></div>
    <div class="col-md-3"><div class="card card-red text-center"><h6>Purchases Count</h6><h2><?= $countPurchases ?></h2></div></div>
</div>

<!-- CHARTS -->
<div class="row g-4 mt-4">
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center">Inventory Stock Distribution</h6>
            <canvas id="stockChart"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center">Sales Revenue by Product</h6>
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center">Purchases Cost by Product</h6>
            <canvas id="purchasesChart"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card chart-card">
            <h6 class="text-center">Inventory Category Breakdown</h6>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

</div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// PHP → JS data
const inventoryData = <?= $inventoryJson ?>;
const salesData = <?= $salesJson ?>;
const purchasesData = <?= $purchasesJson ?>;

// Inventory Stock Chart
new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: { labels: inventoryData.map(i=>i.name), datasets:[{label:'Stock Units', data:inventoryData.map(i=>i.stock), backgroundColor:'#457b9d'}]},
    options:{responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
});

// Sales Revenue by Product
const salesSums = {};
salesData.forEach(s=>salesSums[s.product_name]=(salesSums[s.product_name]||0)+parseFloat(s.total));
new Chart(document.getElementById('salesChart'), {
    type:'bar', data:{labels:Object.keys(salesSums), datasets:[{label:'Revenue (RM)', data:Object.values(salesSums), backgroundColor:'#e63946'}]},
    options:{responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
});

// Purchases Cost by Product
const purchaseSums = {};
purchasesData.forEach(p=>purchaseSums[p.product_name]=(purchaseSums[p.product_name]||0)+parseFloat(p.total));
new Chart(document.getElementById('purchasesChart'), {
    type:'bar', data:{labels:Object.keys(purchaseSums), datasets:[{label:'Cost (RM)', data:Object.values(purchaseSums), backgroundColor:'#2a9d8f'}]},
    options:{responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
});

// Inventory Category Breakdown
const categoryCounts = {};
inventoryData.forEach(i=>categoryCounts[i.category]=(categoryCounts[i.category]||0)+1);
new Chart(document.getElementById('categoryChart'), {
    type:'pie', data:{labels:Object.keys(categoryCounts), datasets:[{data:Object.values(categoryCounts), backgroundColor:['#e63946','#457b9d','#2a9d8f','#f4a261','#8d99ae']}]}
});
</script>

</body>
</html>
