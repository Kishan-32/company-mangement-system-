<?php
session_start();
include 'db.php';

// सुरक्षा (optional)
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// ✅ FETCH REFERENCE-WISE PROFIT (NO DUPLICATE ISSUE)
$query = mysqli_query($conn, "

SELECT reference, SUM(amount) AS total_amount
FROM (

    SELECT reference AS reference, final_price AS amount
    FROM client_subscription
    WHERE reference IS NOT NULL AND reference != ''

    UNION ALL

    SELECT reference_no AS reference, final_price AS amount
    FROM service_orders
    WHERE reference_no IS NOT NULL AND reference_no != ''

    UNION ALL

    SELECT reference_no AS reference, total AS amount
    FROM subscriptions
    WHERE reference_no IS NOT NULL AND reference_no != ''

) AS combined

GROUP BY reference
ORDER BY total_amount DESC

");

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reference Profit</title>

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

        tr:hover{
            background:#f1f1f1;
        }

        .chart-box{
            width:450px;
            margin:40px auto;
        }
    </style>
</head>
<body>

<h2>📊 Reference Profit Report</h2>

<!-- TABLE -->
<table>
    <tr>
        <th>#</th>
        <th>Reference</th>
        <th>Total Amount (₹)</th>
    </tr>

    <?php 
    $i = 1;
    foreach($data as $row){ 
    ?>
    <tr>
        <td><?= $i++; ?></td>
        <td><?= $row['reference']; ?></td>
        <td>₹ <?= number_format($row['total_amount'],2); ?></td>
    </tr>
    <?php } ?>
</table>

<!-- PIE CHART -->
<div class="chart-box">
    <canvas id="pieChart"></canvas>
</div>

<script>
const labels = [
    <?php foreach($data as $row){ echo "'".$row['reference']."',"; } ?>
];

const values = [
    <?php foreach($data as $row){ echo $row['total_amount'].","; } ?>
];

new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
        labels: labels,
        datasets: [{
            data: values,
            backgroundColor: [
                '#ff6384','#36a2eb','#ffce56',
                '#4bc0c0','#9966ff','#ff9f40',
                '#2ecc71','#e74c3c','#1abc9c'
            ]
        }]
    }
});
</script>

</body>
</html>