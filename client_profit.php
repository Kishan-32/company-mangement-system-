<?php
session_start();
include 'db.php';

// सुरक्षा (optional)
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// ✅ CORRECT QUERY (NO DUPLICATE ISSUE)
$query = mysqli_query($conn, "
SELECT 
    c.id,
    c.client_name,

    (SELECT IFNULL(SUM(final_price),0) 
     FROM client_subscription 
     WHERE client_id = c.id) AS subscription_total,

    (SELECT IFNULL(SUM(final_price),0) 
     FROM service_orders 
     WHERE client_id = c.id) AS service_total,

    (SELECT IFNULL(SUM(total),0) 
     FROM subscriptions 
     WHERE client_id = c.id) AS plan_total

FROM client c
");

$clients = [];

while ($row = mysqli_fetch_assoc($query)) {

    $total = $row['subscription_total'] 
           + $row['service_total'] 
           + $row['plan_total'];

    $row['total'] = $total;
    $clients[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Profit</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body{
            font-family: Arial;
            background:#f4f6f9;
            padding:20px;
        }

        h2{
            text-align:center;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:#fff;
            margin-top:20px;
        }

        th, td{
            padding:12px;
            border:1px solid #ddd;
            text-align:center;
        }

        th{
            background:#0c1b33;
            color:white;
        }

        .chart-box{
            width:400px;
            margin:40px auto;
        }
    </style>
</head>
<body>

<h2>📊 Client Profit Report</h2>

<table>
    <tr>
        <th>#</th>
        <th>Client Name</th>
        <th>Total Profit (₹)</th>
    </tr>

    <?php 
    $i = 1;
    foreach($clients as $row){ 
    ?>
    <tr>
        <td><?= $i++; ?></td>
        <td><?= $row['client_name']; ?></td>
        <td>₹ <?= number_format($row['total'],2); ?></td>
    </tr>
    <?php } ?>
</table>


<div class="chart-box">
    <canvas id="pieChart"></canvas>
</div>

<script>
const labels = [
    <?php foreach($clients as $row){ echo "'".$row['client_name']."',"; } ?>
];

const data = [
    <?php foreach($clients as $row){ echo $row['total'].","; } ?>
];

new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: [
                '#ff6384','#36a2eb','#ffce56',
                '#4bc0c0','#9966ff','#ff9f40',
                '#2ecc71','#e74c3c'
            ]
        }]
    }
});
</script>

</body>
</html>