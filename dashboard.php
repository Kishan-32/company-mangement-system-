<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_SESSION['admin_id'];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id='$id'"));

/* ================= FILTER FUNCTION ================= */

function buildFilter($column){
    $where = "";

    if(isset($_GET['filter'])){

        if($_GET['filter']=="today"){
            $where = "DATE($column)=CURDATE()";
        }
        elseif($_GET['filter']=="week"){
            $where = "YEARWEEK($column,1)=YEARWEEK(CURDATE(),1)";
        }
        elseif($_GET['filter']=="month"){
            $where = "MONTH($column)=MONTH(CURDATE()) AND YEAR($column)=YEAR(CURDATE())";
        }
        elseif($_GET['filter']=="year"){
            $where = "YEAR($column)=YEAR(CURDATE())";
        }
        elseif($_GET['filter']=="fy"){
            $where = "$column BETWEEN '2025-04-01' AND '2026-03-31'";
        }
    }

    // Custom date
    if(!empty($_GET['start_date']) && !empty($_GET['end_date'])){
        $s = $_GET['start_date'];
        $e = $_GET['end_date'];
        $where = "DATE($column) BETWEEN '$s' AND '$e'";
    }

    return $where ? "WHERE $where" : "";
}
/* ================= DATE FILTER ================= */

$where = "";

if(isset($_GET['filter'])){

    if($_GET['filter'] == "today"){
        $where = "DATE(created_at)=CURDATE()";
    }

    if($_GET['filter'] == "week"){
        $where = "YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)";
    }

    if($_GET['filter'] == "month"){
        $where = "MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())";
    }

    if($_GET['filter'] == "year"){
        $where = "YEAR(created_at)=YEAR(CURDATE())";
    }

    if($_GET['filter'] == "fy"){
        $where = "created_at BETWEEN '2025-04-01' AND '2026-03-31'";
    }
}

if(!empty($_GET['start_date']) && !empty($_GET['end_date'])){
    $s = $_GET['start_date'];
    $e = $_GET['end_date'];

    $where = "DATE(created_at) BETWEEN '$s' AND '$e'";
}

if($where!=""){
    $where="WHERE ".$where;
}

/* ================= TOTAL REVENUE ================= */

$revenue = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
(SELECT IFNULL(SUM(paid_amount),0) FROM service_orders ".buildFilter('created_at').") +
(SELECT IFNULL(SUM(paid_amount),0) FROM client_subscription ".buildFilter('created_at').") +
(SELECT IFNULL(SUM(paid_amount),0) FROM subscriptions ".buildFilter('created_at').")
AS total_revenue
"));

/* ================= TOTAL EXPENSE ================= */

$expenses = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(expense_amount),0) as total_expenses
FROM expense_item ".buildFilter('created_at')."
"));

/* ================= TOTAL ORDERS ================= */

$total_orders = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM service_orders ".buildFilter('created_at')."
"));

/* ================= TOTAL SUBSCRIPTION ================= */

$total_subscription = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM subscriptions ".buildFilter('created_at')."
"));

/* ================= ACTIVE SUBSCRIPTION ================= */

$filterSub = buildFilter('created_at');

$active_subscription = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM subscriptions 
$filterSub
".($filterSub ? "AND" : "WHERE")." status='active'
"));

/* ================= INACTIVE SUBSCRIPTION ================= */

$inactive_subscription = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total 
FROM subscriptions 
$filterSub
".($filterSub ? "AND" : "WHERE")." status='inactive'
"));

/* ================= CASH BALANCE ================= */

$cash = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(payment_in),0) - IFNULL(SUM(payment_out),0) as balance
FROM cash ".buildFilter('created_at')."
"));

/* ================= BANK BALANCE ================= */

$bank = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(payment_in),0) - IFNULL(SUM(payment_out),0) as balance
FROM bank ".buildFilter('created_at')."
"));

/* ================= TOTAL RECEIVE ================= */

$paid = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
(SELECT IFNULL(SUM(paid_amount),0) FROM service_orders ".buildFilter('created_at').") +
(SELECT IFNULL(SUM(paid_amount),0) FROM client_subscription ".buildFilter('created_at').") +
(SELECT IFNULL(SUM(paid_amount),0) FROM subscriptions ".buildFilter('created_at').")
AS total_paid
"));

/* ================= TOTAL UNPAID ================= */

$unpaid = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(unpaid_amount),0) as total_unpaid
FROM client_subscription ".buildFilter('created_at')."
"));

/* ================= DEADLINE ALERTS ================= */

$company_deadline = mysqli_query($conn,"
SELECT * FROM company_assets
WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");

$client_deadline = mysqli_query($conn,"
SELECT cp.*, 
       c.client_name AS name, 
       c.mobile_number AS phone, 
       c.email AS email 
FROM client_purchases cp
LEFT JOIN client c ON cp.client_id = c.id
WHERE cp.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");

$subscription_deadline = mysqli_query($conn,"
SELECT 
    cp.*, 
    c.client_name AS name, 
    c.mobile_number AS phone, 
    c.email AS email,
    cs.service_type AS plan_name

FROM subscriptions cp

LEFT JOIN client c 
    ON cp.client_id = c.id

LEFT JOIN client_subscription cs 
    ON cp.client_id = cs.client_id   -- ✅ adjust if needed

WHERE cp.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
/* ================= CHART DATA ================= */

$labels = [];
$data = [];

if(isset($_GET['filter']) && $_GET['filter']=="today"){

$q = mysqli_query($conn,"
SELECT HOUR(created_at) as label,
SUM(paid_amount) as total
FROM service_orders
WHERE DATE(created_at)=CURDATE()
GROUP BY HOUR(created_at)
");

while($r=mysqli_fetch_assoc($q)){
$labels[] = $r['label'];
$data[] = $r['total'];
}

}

elseif(isset($_GET['filter']) && $_GET['filter']=="week"){

$q = mysqli_query($conn,"
SELECT DAYNAME(created_at) as label,
SUM(paid_amount) as total
FROM service_orders
WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)
GROUP BY DAYNAME(created_at)
");

while($r=mysqli_fetch_assoc($q)){
$labels[] = $r['label'];
$data[] = $r['total'];
}

}

elseif(isset($_GET['filter']) && $_GET['filter']=="month"){

$q = mysqli_query($conn,"
SELECT DATE(created_at) as label,
SUM(paid_amount) as total
FROM service_orders
WHERE MONTH(created_at)=MONTH(CURDATE())
GROUP BY DATE(created_at)
");

while($r=mysqli_fetch_assoc($q)){
$labels[] = $r['label'];
$data[] = $r['total'];
}

}

else{

$q = mysqli_query($conn,"
SELECT MONTHNAME(created_at) as label,
SUM(paid_amount) as total
FROM service_orders
GROUP BY MONTH(created_at)
");

while($r=mysqli_fetch_assoc($q)){
$labels[] = $r['label'];
$data[] = $r['total'];
}

}

?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

/* BODY */

html,body{
height:100%;
overflow:hidden;
font-family:'Segoe UI', sans-serif;
background:#eef2f7;
}

/* LAYOUT */

body{
display:flex;
}

/* SIDEBAR FIX */

.sidebar{
width:240px;
height:100vh;
position:fixed;
left:0;
top:0;
}

/* CONTENT AREA */

.content{
margin-left:240px;
height:100vh;
overflow-y:auto;
padding:30px;
flex:1;
}

/* HEADER */

.dashboard-title{
font-size:24px;
font-weight:600;
margin-bottom:20px;
}

/* FILTER */

.filter{
background:#fff;
padding:15px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.05);
margin-bottom:25px;
}

.filter select,
.filter input{
padding:8px;
border-radius:6px;
border:1px solid #ccc;
margin-right:10px;
}

.filter button{
background:#4a6cf7;
color:white;
border:none;
padding:8px 14px;
border-radius:6px;
cursor:pointer;
}

/* CHART */

.chart-box{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 4px 15px rgba(0,0,0,0.05);
margin-bottom:25px;
}

.chart-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:15px;
}

.chart-header h3{
font-size:18px;
}

/* CARDS */

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:18px;
margin-bottom:25px;
}

.card{
padding:20px;
border-radius:12px;
color:white;
box-shadow:0 5px 15px rgba(0,0,0,0.1);
transition:0.3s;
}

.card:hover{
transform:translateY(-5px);
}

.card i{
font-size:22px;
margin-bottom:10px;
}

.card h3{
font-size:15px;
opacity:0.9;
}

.card p{
font-size:22px;
font-weight:bold;
margin-top:5px;
}

/* CARD COLORS */

.revenue{background:linear-gradient(45deg,#3a7bd5,#3a6073);}
.expense{background:linear-gradient(45deg,#ff6a00,#ee0979);}
.orders{background:linear-gradient(45deg,#11998e,#38ef7d);}
.subscription{background:linear-gradient(45deg,#6a11cb,#2575fc);}
.active{background:linear-gradient(45deg,#00b09b,#96c93d);}
.inactive{background:linear-gradient(45deg,#ff512f,#dd2476);}
.cash{background:linear-gradient(45deg,#43cea2,#185a9d);}
.bank{background:linear-gradient(45deg,#4568dc,#b06ab3);}
.paid{background:linear-gradient(45deg,#00c6ff,#0072ff);}
.unpaid{background:linear-gradient(45deg,#f953c6,#b91d73);}

/* DEADLINE BOX */

.main-row{
    display:grid;
    grid-template-columns: 70% 30%;
    gap:20px;
}

.right-30{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.deadline-box{
    max-height:250px;
    overflow-y:auto;
}

.deadline-box table{
    font-size:13px;
}

.deadline-box h3{
margin-bottom:10px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
}

table th{
background:#f4f6fa;
font-weight:600;
}

table th,table td{
padding:9px;
border-bottom:1px solid #eee;
text-align:left;
}

table tr:hover{
background:#f9fafc;
}

/* BADGES */

.badge{
padding:4px 10px;
border-radius:20px;
font-size:12px;
font-weight:600;
color:white;
}

.badge.expired{
background:#e74c3c;
}

.badge.warning{
background:#f39c12;
}

.badge.active{
background:#2ecc71;
}

.small-chart{
height:180px;
width:100%;
}

.small-chart canvas{
height:180px !important;
}

.chart-container{
height:250px;
width:100%;
}

.chart-box{
background:white;
padding:20px;
border-radius:14px;
box-shadow:0 4px 15px rgba(0,0,0,0.05);
margin-bottom:25px;
}

.chart-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:10px;
font-weight:600;
}

.chart-header i{
color:#5b6df6;
margin-right:6px;
}

.popup {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
}

.popup-content {
    background: #fff;
    padding: 20px;
    width: 300px;
    margin: 10% auto;
    border-radius: 10px;
    text-align: center;
}

.closeBtn {
    float: right;
    font-size: 20px;
    cursor: pointer;
}

.openPopup {
    cursor: pointer;
}

</style>
</head>

<body>

<?php include_once 'sidebar.php'; ?>

<div class="content" id="mainContent">
<div class="dashboard-container">

<div class="dashboard-title">
Welcome, <?php echo htmlspecialchars($admin['email']); ?> 👋
</div>

<div class="filter">
<form method="GET">

<select name="filter" onchange="this.form.submit()">
<option value="">All</option>
<option value="today" <?= ($_GET['filter'] ?? '')=='today'?'selected':'' ?>>Today</option>
<option value="week" <?= ($_GET['filter'] ?? '')=='week'?'selected':'' ?>>Week</option>
<option value="month" <?= ($_GET['filter'] ?? '')=='month'?'selected':'' ?>>Month</option>
<option value="year" <?= ($_GET['filter'] ?? '')=='year'?'selected':'' ?>>Year</option>
<option value="fy" <?= ($_GET['filter'] ?? '')=='fy'?'selected':'' ?>>FY 25-26</option>
</select>

<input type="date" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
<input type="date" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">

<button type="submit">
<i class="fa fa-filter"></i> Apply
</button>

</form><br> 
<div class="cards">

<div class="card revenue">
<i class="fa fa-chart-line"></i>
<h3>Total Revenue</h3>
<p>₹ <?php echo $revenue['total_revenue']; ?></p>
</div>

<div class="card expense">
<i class="fa fa-money-bill-wave"></i>
<h3>Total Expenses</h3>
<p>₹ <?php echo $expenses['total_expenses']; ?></p>
</div>

<div class="card orders">
<i class="fa fa-cart-shopping"></i>
<h3>Total Orders</h3>
<p><?php echo $total_orders['total']; ?></p>
</div>

<div class="card subscription">
<i class="fa fa-layer-group"></i>
<h3>Total Subscription</h3>
<p><?php echo $total_subscription['total']; ?></p>
</div>

<div class="card active">
<i class="fa fa-check-circle"></i>
<h3>Active Subscription</h3>
<p><?php echo $active_subscription['total']; ?></p>
</div>

<div class="card inactive">
<i class="fa fa-times-circle"></i>
<h3>Inactive Subscription</h3>
<p><?php echo $inactive_subscription['total']; ?></p>
</div>

<div class="card cash">
<i class="fa fa-wallet"></i>
<h3>Cash Balance</h3>
<p>₹ <?php echo $cash['balance']; ?></p>
</div>

<div class="card bank">
<i class="fa fa-building-columns"></i>
<h3>Bank Balance</h3>
<p>₹ <?php echo $bank['balance']; ?></p>
</div>

<div class="card paid">
<i class="fa fa-circle-check"></i>
<h3>Total Recieve</h3>
<p>₹ <?php echo $paid['total_paid']; ?></p>
</div>

<div class="card unpaid">
<i class="fa fa-triangle-exclamation"></i>
<h3>Total Unpaid</h3>
<p>₹ <?php echo $unpaid['total_unpaid']; ?></p>
</div>

</div>
</div>
<!-- graph -->
<!-- FILTER -->
<!-- CHART -->
<div class="main-row">

    <!-- LEFT 70% GRAPH -->
    <div class="left-70">
        <div class="chart-box">
            <div class="chart-header">
                <h3><i class="fa fa-chart-line"></i> Sales Overview</h3>
            </div>

            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- RIGHT 30% DEADLINES -->
    <div class="right-30">

    <!-- BOX 1 -->
    <div class="deadline-box">
        <h3>⚠ Company Asset Expiry (Next 7 Days)</h3>

        <table>
            <tr>
                <th>Asset</th>
                <th>Expiry Date</th>
                <th>Status</th>
            </tr>

          <?php 
$data = [];

while($row = mysqli_fetch_assoc($company_deadline)){ 
    $expiry = strtotime($row['expiry_date']);
    $today  = strtotime(date("Y-m-d")); // ✅ FIXED format
    $days   = ($expiry - $today) / 86400;

    if($expiry < $today || $days <= 7){

        $row['is_expired'] = ($expiry < $today) ? 1 : 0; // flag
        $row['days'] = $days;

        $data[] = $row;
    }
}

// ✅ SORT: expired first, then nearest expiry
usort($data, function($a, $b){
    if($a['is_expired'] != $b['is_expired']){
        return $b['is_expired'] - $a['is_expired']; // expired first
    }
    return $a['days'] - $b['days']; // nearest first
});

// ✅ DISPLAY
foreach($data as $row){

    if($row['is_expired']){
        $status = "<span class='badge expired'>Expired</span>";
    } else {
        $status = "<span class='badge warning'>Expiring Soon</span>";
    }
?>
<tr class="openPopup" 
    data-name="<?= htmlspecialchars($row['name'] ?? 'No Name'); ?>"
    data-phone="<?= htmlspecialchars($row['phone'] ?? 'No Phone'); ?>"
    data-email="<?= htmlspecialchars($row['email'] ?? 'No Email'); ?>">

    <td><?= $row['asset_name']; ?></td>
    <td><?= $row['expiry_date']; ?></td>
    <td><?= $status; ?></td>
</tr>
<?php } ?>
        </table>
    </div>
    <!-- BOX 2 -->
<div class="deadline-box">
    <h3>⚠ Client Purchase Expiry (Next 7 Days)</h3>

    <table>
        <tr>
            <th>Asset</th>
            <th>Expiry Date</th>
            <th>Status</th>
        </tr>

        <?php 
$data = [];

while ($row = mysqli_fetch_assoc($client_deadline)) { 

    $expiry = strtotime($row['expiry_date'] ?? '');
    $today  = strtotime(date("Y-m-d")); // ✅ FIXED
    $days   = ($expiry - $today) / 86400;

    if ($expiry && ($expiry < $today || $days <= 7)) {

        $row['is_expired'] = ($expiry < $today) ? 1 : 0;
        $row['days'] = $days;

        $data[] = $row;
    }
}

// ✅ SORT
usort($data, function($a, $b){
    if($a['is_expired'] != $b['is_expired']){
        return $b['is_expired'] - $a['is_expired']; // expired first
    }
    return $a['days'] - $b['days']; // nearest first
});

// ✅ DISPLAY
foreach ($data as $row) {

    if ($row['is_expired']) {
        $status = "<span class='badge expired'>Expired</span>";
    } else {
        $status = "<span class='badge warning'>Expiring Soon</span>";
    }

    $name  = htmlspecialchars($row['name'] ?? 'No Name');
    $phone = htmlspecialchars($row['phone'] ?? 'No Phone');
    $email = htmlspecialchars($row['email'] ?? 'No Email');
?>

<tr class="openPopup"
    data-name="<?= $name ?>"
    data-phone="<?= $phone ?>"
    data-email="<?= $email ?>">

    <td><?= htmlspecialchars($row['asset_name']) ?></td>
    <td><?= htmlspecialchars($row['expiry_date']) ?></td>
    <td><?= $status ?></td>

</tr>

<?php } ?>
    </table>
</div>
    <!-- BOX 3 -->
    <div class="deadline-box">
        <h3>⚠ Subscription Expiry (Next 7 Days)</h3>

        <table>
            <tr>
                <th>Plan</th>
                <th>End Date</th>
                <th>Status</th>
            </tr>

            <?php 
$data = [];

while($row = mysqli_fetch_assoc($subscription_deadline)){ 

    $expiry = strtotime($row['end_date']);
    $today  = strtotime(date("Y-m-d")); // ✅ FIXED
    $days   = ($expiry - $today) / 86400;

    if($expiry < $today || $days <= 7){

        $row['is_expired'] = ($expiry < $today) ? 1 : 0;
        $row['days'] = $days;

        $data[] = $row;
    }
}

// ✅ SORT
usort($data, function($a, $b){
    if($a['is_expired'] != $b['is_expired']){
        return $b['is_expired'] - $a['is_expired']; // expired first
    }
    return $a['days'] - $b['days']; // nearest first
});

// ✅ DISPLAY
foreach($data as $row){

    if($row['is_expired']){
        $status = "<span class='badge expired'>Expired</span>";
    } else {
        $status = "<span class='badge warning'>Expiring Soon</span>";
    }
?>

<tr class="openPopup" 
    data-name="<?= htmlspecialchars($row['name'] ?? 'No Name'); ?>"
    data-phone="<?= htmlspecialchars($row['phone'] ?? 'No Phone'); ?>"
    data-email="<?= htmlspecialchars($row['email'] ?? 'No Email'); ?>">

    <td><?= htmlspecialchars($row['plan_name'] ?? 'N/A'); ?></td>
    <td><?= htmlspecialchars($row['end_date'] ?? 'N/A'); ?></td>
    <td><?= $status; ?></td>

</tr>

<?php } ?>
        </table>
    </div>

</div>
<div id="popupBox" class="popup">
    <div class="popup-content">
        <span class="closeBtn">&times;</span>
        <h3>User Details</h3>

        <p><strong>Name:</strong> <span id="p_name"></span></p>
        <p><strong>Contact:</strong> <span id="p_phone"></span></p>
        <p><strong>Email:</strong> <span id="p_email"></span></p>
    </div>
</div>
</body>
<script>
    <?php if(isset($_SESSION['login_success'])): ?>
Swal.fire({
    toast: true,
    position: 'top-end', // ✅ top right
    icon: 'success',
    title: 'Login Successful',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
});
<?php unset($_SESSION['login_success']); endif; ?>

document.querySelectorAll(".openPopup").forEach(row => {
    row.addEventListener("click", function(){

        document.getElementById("p_name").innerText = this.dataset.name;
        document.getElementById("p_phone").innerText = this.dataset.phone;
        document.getElementById("p_email").innerText = this.dataset.email;

        document.getElementById("popupBox").style.display = "block";
    });
});

// Close popup
document.querySelector(".closeBtn").onclick = function(){
    document.getElementById("popupBox").style.display = "none";
}

// Click outside to close
window.onclick = function(e){
    if(e.target.id === "popupBox"){
        document.getElementById("popupBox").style.display = "none";
    }
}
</script>
<script>

const labels = <?php echo json_encode($labels); ?>;
const data = <?php echo json_encode($data); ?>;

const ctx = document.getElementById('salesChart');

new Chart(ctx, {

type: 'line',

data: {
labels: labels,
datasets: [{
label: "Sales",
data: data,

borderColor: "#5b6df6",
backgroundColor: "rgba(91,109,246,0.08)",

borderWidth:3,
tension:0.4,
fill:true,

pointBackgroundColor:"#5b6df6",
pointBorderColor:"#ffffff",
pointBorderWidth:2,
pointRadius:5,
pointHoverRadius:7
}]
},

options: {

responsive:true,
maintainAspectRatio:false,

plugins:{
legend:{
display:false
}
},

scales:{

x:{
grid:{
display:false
}
},

y:{
beginAtZero:true,
grid:{
color:"#eef1f7"
},
ticks:{
callback:function(value){
return "₹ "+value;
}
}
}

}

}

});

</script>

</html>