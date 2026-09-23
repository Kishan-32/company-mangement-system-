<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* SHOW MYSQL ERRORS (IMPORTANT FOR DEBUG) */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

/* ================= GET DATA ================= */

$client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
$mobile      = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$email       = mysqli_real_escape_string($conn, $_POST['email'] ?? '');

$service_type   = mysqli_real_escape_string($conn, $_POST['service_type'] ?? '');
$reference      = mysqli_real_escape_string($conn, $_POST['reference'] ?? '');

$price          = floatval($_POST['price'] ?? 0);
$discount_price = floatval($_POST['discount_price'] ?? 0);
$paid_amount    = floatval($_POST['paid_amount'] ?? 0);

$purchase_date = $_POST['purchase_date'] ?? '';
$payment_mode  = mysqli_real_escape_string($conn, $_POST['payment_mode'] ?? '');
$status        = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

/* ================= VALIDATION ================= */

if($price < 0 || $discount_price < 0 || $paid_amount < 0){
    exit("INVALID_PRICE");
}

/* FIX DATE (IMPORTANT) */
if(empty($purchase_date)){
    $purchase_date = date('Y-m-d'); // default today
}

/* ================= CALCULATIONS ================= */

$final_price   = $price - $discount_price;
$unpaid_amount = $final_price - $paid_amount;

if($unpaid_amount < 0){
    $unpaid_amount = 0;
}

/* ================= INSERT CLIENT ================= */

$q1 = mysqli_query($conn,"
    INSERT INTO client (client_name, mobile_number, email)
    VALUES ('$client_name','$mobile','$email')
");

if(!$q1){
    exit("CLIENT_ERROR: " . mysqli_error($conn));
}

$client_id = mysqli_insert_id($conn);

/* ================= INSERT SUBSCRIPTION ================= */

$q2 = mysqli_query($conn,"
    INSERT INTO client_subscription
    (client_id, service_type, price, discount_price, final_price, paid_amount, unpaid_amount, purchase_date, payment_mode, reference, status)
    VALUES
    ('$client_id','$service_type','$price','$discount_price','$final_price','$paid_amount','$unpaid_amount','$purchase_date','$payment_mode','$reference','$status')
");

if(!$q2){
    exit("SUB_ERROR: " . mysqli_error($conn));
}

$client_subscription_id = mysqli_insert_id($conn);

/* ================= PAYMENT ENTRY ================= */

/* CASH PAYMENT */
if(strtolower($payment_mode) == "cash" && $paid_amount > 0){

    $cash_q = mysqli_query($conn,"
        INSERT INTO cash (client_subscription_id, payment_in)
        VALUES ('$client_subscription_id','$paid_amount')
    ");

    if(!$cash_q){
        exit("CASH_ERROR: " . mysqli_error($conn));
    }
}

/* BANK / UPI / CARD / CHEQUE */
if(in_array(strtolower($payment_mode), ["bank transfer","upi","cheque","card"])){

    if($paid_amount > 0){

        $bank_q = mysqli_query($conn,"
            INSERT INTO bank (client_subscription_id, payment_in, payment_type)
            VALUES ('$client_subscription_id','$paid_amount','$payment_mode')
        ");

        if(!$bank_q){
            exit("BANK_ERROR: " . mysqli_error($conn));
        }
    }
}

/* ================= SUCCESS ================= */

echo "SUCCESS";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>