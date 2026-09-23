<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

$client_id     = intval($_POST['client_id'] ?? 0);
$plan_type     = mysqli_real_escape_string($conn, $_POST['plan_type'] ?? '');
$start_date    = $_POST['start_date'] ?? '';
$end_date      = $_POST['end_date'] ?: NULL;

$price         = floatval($_POST['price'] ?? 0);
$discount      = floatval($_POST['discount'] ?? 0);
$total         = floatval($_POST['total'] ?? 0);
$paid_amount   = floatval($_POST['paid_amount'] ?? 0);

$payment_mode  = mysqli_real_escape_string($conn, $_POST['payment_mode'] ?? '');
$payment_date  = $_POST['payment_date'] ?: NULL;

$description   = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$reference_no  = mysqli_real_escape_string($conn, $_POST['reference_no'] ?? '');
$status        = ($_POST['status'] === 'active') ? 'active' : 'inactive';

/* VALIDATION */
if (!$client_id || !$plan_type || !$start_date) exit("REQUIRED_MISSING");
if (!empty($end_date) && $start_date > $end_date) exit("INVALID_DATE");
if ($paid_amount > $total) exit("PAID_GREATER_THAN_TOTAL");

/* INSERT SUBSCRIPTION */
$sql = "
INSERT INTO subscriptions
(client_id,plan_type,start_date,end_date,price,discount,total,
 paid_amount,payment_mode,payment_date,description,reference_no,status)
VALUES
('$client_id','$plan_type','$start_date',
" . ($end_date ? "'$end_date'" : "NULL") . ",
'$price','$discount','$total',
'$paid_amount',
" . ($payment_mode ? "'$payment_mode'" : "NULL") . ",
" . ($payment_date ? "'$payment_date'" : "NULL") . ",
'$description','$reference_no','$status')
";

if (mysqli_query($conn, $sql)) {

    $subscription_id = mysqli_insert_id($conn);

    /* ================= CASH ================= */
    if ($payment_mode === "Cash" && $paid_amount > 0) {

        mysqli_query($conn,"
            INSERT INTO cash
            (subscription_plan_id, payment_in, payment_out, created_at)
            VALUES
            ('$subscription_id','$paid_amount',0,NOW())
        ");
    }

    /* ================= BANK ================= */
    if (
        $payment_mode === "UPI" ||
        $payment_mode === "Card" ||
        $payment_mode === "Cheque" ||
        $payment_mode === "Bank Transfer"
    ) {
        if ($paid_amount > 0) {
            mysqli_query($conn,"
                INSERT INTO bank
                (client_purchase_id, payment_in, payment_out, payment_type, created_at)
                VALUES
                ('$subscription_id','$paid_amount',0,'$payment_mode',NOW())
            ");
        }
    }

    echo "SUCCESS";

} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>