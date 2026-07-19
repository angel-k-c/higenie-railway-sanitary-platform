<?php 
session_start();
require __DIR__ . '/../db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

function buildDateSql($period, $start_date, $end_date, $date_col = 'order_date') {
    $where = [];
    $params = [];
    if ($start_date) { $where[] = "$date_col >= :start_date"; $params[':start_date'] = $start_date; }
    if ($end_date) { $where[] = "$date_col <= :end_date"; $params[':end_date'] = $end_date; }

    switch ($period) {
        case 'daily': $groupExpr = $labelExpr = "DATE($date_col)"; break;
        case 'monthly': $groupExpr = $labelExpr = "DATE_FORMAT($date_col, '%Y-%m')"; break;
        case 'halfyearly': $groupExpr = $labelExpr = "CONCAT(YEAR($date_col), '-H', IF(MONTH($date_col)>6,2,1))"; break;
        case 'yearly': $groupExpr = $labelExpr = "YEAR($date_col)"; break;
        default: $groupExpr = $labelExpr = "DATE($date_col)";
    }

    $where_sql = empty($where) ? "1=1" : implode(" AND ", $where);
    return [$where_sql, $params, $groupExpr, $labelExpr];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_report') {
    header('Content-Type: application/json; charset=utf-8');
    $report = $_POST['report'] ?? 'sales';
    $period = $_POST['period'] ?? 'daily';
    $start_date = $_POST['start_date'] ?: null;
    $end_date   = $_POST['end_date'] ?: null;

    try {
        switch ($report) {
            case 'sales':
                [$where_sql, $params, $groupExpr, $labelExpr] = buildDateSql($period, $start_date, $end_date, 'order_date');
                $sql = "SELECT $labelExpr AS label, SUM(total_amount) AS value
                        FROM orders
                        WHERE payment_status='Paid' AND $where_sql
                        GROUP BY $groupExpr
                        ORDER BY $groupExpr ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('floatval', array_column($rows,'value')),
                    'title'=>'Sales Trend ',
                    'chart_type' => 'area_green'
                ]);
                break;

            case 'new_user_signups':
                [$where_sql, $params, $groupExpr, $labelExpr] = buildDateSql($period, $start_date, $end_date, 'created_at');
                $sql = "SELECT $labelExpr AS label, COUNT(id) AS value
                        FROM users
                        WHERE role='customer' AND $where_sql
                        GROUP BY $groupExpr
                        ORDER BY $groupExpr ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('intval', array_column($rows,'value')),
                    'title'=>'New User Signups',
                    'chart_type' => 'area_red'
                ]);
                break;

            case 'refund_history':
    $sql = "SELECT 
                DATE(created_at) AS label,
                SUM(refund_amount) AS value,
                COUNT(*) AS total_refunds
            FROM refunds
            WHERE created_at BETWEEN 
                COALESCE(:start_date, '1970-01-01') 
                AND 
                COALESCE(:end_date, '9999-12-31')
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $start_date ?? '1970-01-01',
        ':end_date'   => $end_date ?? '9999-12-31'
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('floatval', array_column($rows, 'value')),
        'counts' => array_map('intval', array_column($rows, 'total_refunds')),
        'title'  => 'Refund Amount by Day',
        'chart_type' => 'area_red'
    ]);
    break;


            case 'sales_by_category':
                $sql = "SELECT c.name AS label, SUM(oi.quantity*oi.price) AS value
                        FROM order_items oi
                        JOIN products p ON oi.product_id=p.id
                        JOIN categories c ON p.category_id=c.id
                        JOIN orders o ON oi.order_id=o.id
                        WHERE o.order_date BETWEEN COALESCE(:start_date,'1970-01-01') AND COALESCE(:end_date,'9999-12-31')
                        GROUP BY c.id
                        ORDER BY value DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start_date'=>$start_date??'1970-01-01',':end_date'=>$end_date??'9999-12-31']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('floatval', array_column($rows,'value')),
                    'title'=>'Sales by Category '
                ]);
                break;

            case 'sales_by_subcategory':
                $sql = "SELECT sc.name AS label, SUM(oi.quantity*oi.price) AS value
                        FROM order_items oi
                        JOIN products p ON oi.product_id=p.id
                        JOIN subcategories sc ON p.subcategory_id=sc.id
                        JOIN orders o ON oi.order_id=o.id
                        WHERE o.order_date BETWEEN COALESCE(:start_date,'1970-01-01') AND COALESCE(:end_date,'9999-12-31')
                        GROUP BY sc.id
                        ORDER BY value DESC
                        LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start_date'=>$start_date??'1970-01-01',':end_date'=>$end_date??'9999-12-31']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('floatval', array_column($rows,'value')),
                    'title'=>'Sales by Sub-Category'
                ]);
                break;

            case 'top_products':
                $sql = "SELECT p.product_name AS label, SUM(oi.quantity) AS value
                        FROM order_items oi
                        JOIN orders o ON oi.order_id=o.id
                        JOIN products p ON oi.product_id=p.id
                        WHERE o.order_date BETWEEN COALESCE(:start_date,'1970-01-01') AND COALESCE(:end_date,'9999-12-31')
                        GROUP BY p.id
                        ORDER BY value DESC
                        LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start_date'=>$start_date??'1970-01-01',':end_date'=>$end_date??'9999-12-31']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('intval', array_column($rows,'value')),
                    'title'=>'Top Selling Products'
                ]);
                break;

            case 'frequent_customers':
                $sql = "SELECT CONCAT(u.first_name,' ',u.last_name) AS label, COUNT(o.id) AS value
                        FROM users u
                        JOIN orders o ON u.id=o.user_id
                        WHERE u.role='customer'
                          AND o.order_date BETWEEN COALESCE(:start_date,'1970-01-01') AND COALESCE(:end_date,'9999-12-31')
                        GROUP BY u.id
                        ORDER BY value DESC
                        LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start_date'=>$start_date??'1970-01-01',':end_date'=>$end_date??'9999-12-31']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('intval', array_column($rows,'value')),
                    'title'=>'Frequent Buyers'
                ]);
                break;

            case 'payment_summary':
                $sql = "SELECT payment_status AS label, COUNT(*) AS value, SUM(total_amount) AS total
                        FROM orders
                        WHERE order_date BETWEEN COALESCE(:start_date,'1970-01-01') AND COALESCE(:end_date,'9999-12-31')
                        GROUP BY payment_status";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start_date'=>$start_date??'1970-01-01',':end_date'=>$end_date??'9999-12-31']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode([
                    'labels'=>array_column($rows,'label'),
                    'data'=>array_map('intval', array_column($rows,'value')),
                    'totals'=>array_map('floatval', array_column($rows,'total')),
                    'title'=>'Payment Summary',
                    'chart_type' => 'polarArea'
                ]);
                break;

            default:
                echo json_encode(['error'=>'Unknown report']);
        }
    } catch (PDOException $ex) {
        echo json_encode(['error'=>$ex->getMessage()]);
    }
    exit;
}

$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end   = date('Y-m-d');
$reports = [
    'sales'=>'Sales Trends',
    'new_user_signups'=>'New User Signups',
    'refund_history'=>'Refund History',
    'sales_by_category'=>'Sales by Category',
    'sales_by_subcategory'=>'Sales by Sub-Category',
    'top_products'=>'Top Selling Products',
    'frequent_customers'=>'Frequent Buyers',
    'payment_summary'=>'Financial Summary'
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Reports - Hi.Genie</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<style>
body { font-family:'Segoe UI',sans-serif; background:#f8f9fa; }
.container-fluid { padding:0; }
.sidebar { width:220px; height:100vh; position:fixed; background:#fff; border-right:1px solid #e0e0e0; overflow-y:auto; }
.sidebar .nav-link { color:#333; padding:12px 20px; display:block; border-left:4px solid transparent; transition:0.2s; }
.sidebar .nav-link.active { background:#f5f5f5; border-left:4px solid #c0392b; font-weight:600; }
.content { margin-left:220px; padding:30px 20px; }
.card { box-shadow:0 2px 6px rgba(0,0,0,0.05); border:none; margin-bottom:20px; }
.card-header { background:#fff; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
.canvas-wrap { position:relative; height:350px; }
.controls { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
</style>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="container-fluid">
    <div class="sidebar">
        <h5 class="text-center py-3 border-bottom">Reports</h5>
        <nav class="nav flex-column">
            <?php foreach($reports as $key=>$label): ?>
                <a href="#" class="nav-link <?= $key==='sales'?'active':'' ?>" data-report="<?= $key ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="content">
        <h2>Analytics Dashboard</h2>
        <div class="card p-3 mb-4">
            <div class="controls">
                <label>Period</label>
                <select id="global-period" class="form-select form-select-sm" style="width:120px">
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <label>From</label>
                <input id="global-start" type="date" class="form-control form-control-sm" style="width:140px" value="<?= $default_start ?>">
                <label>To</label>
                <input id="global-end" type="date" class="form-control form-control-sm" style="width:140px" value="<?= $default_end ?>">
                <button id="apply-global" class="btn btn-sm btn-primary">Apply</button>
            </div>
        </div>

        <?php foreach($reports as $key=>$label): ?>
        <div class="card report-card" id="card-<?= $key ?>" style="<?= $key==='sales'?'':'display:none' ?>">
            <div class="card-header"><h6><?= $label ?></h6></div>
            <div class="card-body">
                <div class="canvas-wrap"><canvas id="chart-<?= $key ?>"></canvas></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const charts = {};
const reports = <?= json_encode(array_keys($reports)) ?>;

async function fetchReport(report, period, start, end){
    const form = new FormData();
    form.append('action','get_report');
    form.append('report', report);
    form.append('period', period);
    form.append('start_date', start);
    form.append('end_date', end);
    const res = await fetch('', { method:'POST', body: form });
    return res.json();
}

function generateColors(n){
    const base = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#5a5c69','#2c9faf','#f8f9fc','#2e59d9'];
    while(base.length<n) base.push('#'+Math.floor(Math.random()*16777215).toString(16));
    return base.slice(0,n);
}

function renderChart(canvasId, reportType, payload){
    const ctx = document.getElementById(canvasId).getContext('2d');
    if(charts[canvasId]) charts[canvasId].destroy();
    let chartType='bar', datasets=[], options={maintainAspectRatio:false,responsive:true,plugins:{legend:{position:'top'},title:{display:false}},scales:{y:{beginAtZero:true}}};

    if(payload.chart_type==='area_green'||payload.chart_type==='area_red'){
        chartType='line';
        datasets.push({label:payload.title,data:payload.data,borderColor:payload.chart_type==='area_green'?'#1cc88a':'#e74a3b',backgroundColor:payload.chart_type==='area_green'?'rgba(28,200,138,0.1)':'rgba(231,74,59,0.1)',fill:true,tension:0.3});
    } else if(payload.chart_type==='pie' || reportType==='sales_by_category'){
        chartType='doughnut';
        datasets.push({label:payload.title,data:payload.data,backgroundColor:generateColors(payload.data.length),borderColor:'#fff',borderWidth:2});
        options.scales={};
    } else if(reportType==='sales_by_subcategory'||reportType==='frequent_customers'){
        chartType='bar'; datasets.push({label:payload.title,data:payload.data,backgroundColor:reportType==='frequent_customers'?'#f6c23e':'#36b9cc',borderRadius:3});
        options.indexAxis='y'; options.plugins.legend.display=false;
    } else if(payload.chart_type==='polarArea'){
        chartType='polarArea'; datasets.push({label:payload.title,data:payload.data,backgroundColor:generateColors(payload.data.length),borderColor:'#fff',borderWidth:2}); options.scales={};
    } else {
        datasets.push({label:payload.title,data:payload.data,backgroundColor:'#4e73df',borderRadius:3});
    }
    charts[canvasId]=new Chart(ctx,{type:chartType,data:{labels:payload.labels||[],datasets:datasets},options:options});
}

async function loadReport(report){
    const period=document.getElementById('global-period').value;
    const start=document.getElementById('global-start').value;
    const end=document.getElementById('global-end').value;
    const payload = await fetchReport(report,period,start,end);
    renderChart('chart-'+report, report, payload);
}

document.querySelectorAll('.sidebar .nav-link').forEach(link=>{
    link.addEventListener('click', function(e){
        e.preventDefault();
        document.querySelectorAll('.sidebar .nav-link').forEach(l=>l.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.report-card').forEach(c=>c.style.display='none');
        document.getElementById('card-'+this.dataset.report).style.display='block';
        loadReport(this.dataset.report);
    });
});

document.getElementById('apply-global').addEventListener('click', ()=>{
    const activeReport=document.querySelector('.sidebar .nav-link.active').dataset.report;
    loadReport(activeReport);
});

window.addEventListener('DOMContentLoaded', ()=>{ loadReport('sales'); });
</script>

</body>
</html>
