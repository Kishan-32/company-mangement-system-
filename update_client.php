<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

$id = intval($_POST['id'] ?? 0);

$client_name     = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
$mobile          = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$email           = mysqli_real_escape_string($conn,$_POST['email'] ?? '');
$service_type    = mysqli_real_escape_string($conn, $_POST['service_type'] ?? '');
$reference       = mysqli_real_escape_string($conn, $_POST['reference'] ?? '');
$price           = floatval($_POST['price'] ?? 0);
$discount_price  = floatval($_POST['discount_price'] ?? 0);
$paid_amount     = floatval($_POST['paid_amount'] ?? 0);
$purchase_date   = $_POST['purchase_date'] ?? '';
$payment_mode    = strtolower(mysqli_real_escape_string($conn, $_POST['payment_mode'] ?? ''));
$description     = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$status          = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if($price < 0 || $discount_price < 0 || $paid_amount < 0){
    exit("INVALID_PRICE");
}

/* CALCULATIONS */
$final_price   = $price - $discount_price;
$unpaid_amount = $final_price - $paid_amount;
if($unpaid_amount < 0){ $unpaid_amount = 0; }

/* GET CLIENT ID */
$get = mysqli_query($conn,"SELECT client_id FROM client_subscription WHERE id='$id'");
$row = mysqli_fetch_assoc($get);

if(!$row){ exit("NOT_FOUND"); }

$client_id = $row['client_id'];

/* UPDATE CLIENT */
mysqli_query($conn,"
    UPDATE client SET
        client_name='$client_name',
        mobile_number='$mobile',
        email='$email'
    WHERE id='$client_id'
");

/* UPDATE SUBSCRIPTION */
mysqli_query($conn,"
    UPDATE client_subscription SET
        service_type='$service_type',
        reference='$reference',
        price='$price',
        discount_price='$discount_price',
        final_price='$final_price',
        paid_amount='$paid_amount',
        unpaid_amount='$unpaid_amount',
        purchase_date='$purchase_date',
        payment_mode='$payment_mode',
        description='$description',
        status='$status'
    WHERE id='$id'
");

/* ================= PAYMENT SYNC ================= */

/* ---------- CASH MODE ---------- */
if($payment_mode == "cash" && $paid_amount > 0){

    $checkCash = mysqli_query($conn,"
        SELECT id FROM cash 
        WHERE client_subscription_id='$id'
    ");

    if(mysqli_num_rows($checkCash) > 0){

        mysqli_query($conn,"
            UPDATE cash SET
                payment_in='$paid_amount'
            WHERE client_subscription_id='$id'
        ");

    } else {

        mysqli_query($conn,"
            INSERT INTO cash
            (client_subscription_id, payment_in)
            VALUES
            ('$id','$paid_amount')
        ");
    }

    // Remove bank entry
    mysqli_query($conn,"DELETE FROM bank WHERE client_subscription_id='$id'");
}


/* ---------- BANK MODE ---------- */
elseif(
    $payment_mode == "bank transfer" ||
    $payment_mode == "upi" ||
    $payment_mode == "cheque" ||
    $payment_mode == "card"
){

    if($paid_amount > 0){

        $checkBank = mysqli_query($conn,"
            SELECT id FROM bank 
            WHERE client_subscription_id='$id'
        ");

        if(mysqli_num_rows($checkBank) > 0){

            mysqli_query($conn,"
                UPDATE bank SET
                    payment_in='$paid_amount',
                    payment_type='$payment_mode'
                WHERE client_subscription_id='$id'
            ");

        } else {

            mysqli_query($conn,"
                INSERT INTO bank
                (client_subscription_id, payment_in, payment_type)
                VALUES
                ('$id','$paid_amount','$payment_mode')
            ");
        }
    }

    // Remove cash entry
    mysqli_query($conn,"DELETE FROM cash WHERE client_subscription_id='$id'");
}


/* ---------- NO PAYMENT ---------- */
else{
    mysqli_query($conn,"DELETE FROM cash WHERE client_subscription_id='$id'");
    mysqli_query($conn,"DELETE FROM bank WHERE client_subscription_id='$id'");
}

echo "UPDATED";
?>