<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* ================= GET DATA ================= */

$id            = intval($_POST['id'] ?? 0);
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

$reference_no  = mysqli_real_escape_string($conn, $_POST['reference_no'] ?? '');
$description   = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$status        = ($_POST['status'] === 'active') ? 'active' : 'inactive';

/* ================= VALIDATION ================= */

if (!$id || !$client_id || !$plan_type || !$start_date) {
    exit("MISSING_FIELDS");
}

if (!empty($end_date) && $start_date > $end_date) {
    exit("INVALID_DATE");
}

if ($paid_amount > $total) {
    exit("PAID_GREATER_THAN_TOTAL");
}

/* AUTO EXPIRE */
$today = date('Y-m-d');
if (!empty($end_date) && $end_date < $today) {
    $status = 'inactive';
}

/* ================= UPDATE SUBSCRIPTION ================= */

$query = "
UPDATE subscriptions SET
    client_id     = '$client_id',
    plan_type     = '$plan_type',
    start_date    = '$start_date',
    end_date      = " . ($end_date ? "'$end_date'" : "NULL") . ",
    price         = '$price',
    discount      = '$discount',
    total         = '$total',
    paid_amount   = '$paid_amount',
    payment_mode  = '$payment_mode',
    payment_date  = " . ($payment_date ? "'$payment_date'" : "NULL") . ",
    reference_no  = '$reference_no',
    description   = '$description',
    status        = '$status'
WHERE id = '$id'
";

if (mysqli_query($conn, $query)) {

    /* ================= SYNC PAYMENTS ================= */

    /* -------- CASH MODE -------- */
    if ($payment_mode === "Cash" && $paid_amount > 0) {

        $checkCash = mysqli_query($conn,"
            SELECT id FROM cash 
            WHERE subscription_plan_id='$id'
        ");

        if (mysqli_num_rows($checkCash) > 0) {

            mysqli_query($conn,"
                UPDATE cash SET
                    payment_in  = '$paid_amount',
                    payment_out = 0,
                    created_at  = NOW()
                WHERE subscription_plan_id='$id'
            ");

        } else {

            mysqli_query($conn,"
                INSERT INTO cash
                (subscription_plan_id, payment_in, payment_out, created_at)
                VALUES
                ('$id', '$paid_amount', 0, NOW())
            ");
        }

        // Remove bank entry if exists
        mysqli_query($conn,"DELETE FROM bank WHERE subscription_plan_id='$id'");
    }


    /* -------- BANK MODE -------- */
    elseif (
        $payment_mode === "UPI" ||
        $payment_mode === "Card" ||
        $payment_mode === "Cheque" ||
        $payment_mode === "Bank Transfer"
    ) {

        if ($paid_amount > 0) {

            $checkBank = mysqli_query($conn,"
                SELECT id FROM bank 
                WHERE subscription_plan_id='$id'
            ");

            if (mysqli_num_rows($checkBank) > 0) {

                mysqli_query($conn,"
                    UPDATE bank SET
                        payment_in  = '$paid_amount',
                        payment_out = 0,
                        payment_type= '$payment_mode',
                        created_at  = NOW()
                    WHERE subscription_plan_id='$id'
                ");

            } else {

                mysqli_query($conn,"
                    INSERT INTO bank
                    (subscription_plan_id, payment_in, payment_out, payment_type, created_at)
                    VALUES
                    ('$id', '$paid_amount', 0, '$payment_mode', NOW())
                ");
            }
        }

        // Remove cash entry if exists
        mysqli_query($conn,"DELETE FROM cash WHERE subscription_plan_id='$id'");
    }


    /* -------- NO PAYMENT -------- */
    else {
        mysqli_query($conn,"DELETE FROM cash WHERE subscription_plan_id='$id'");
        mysqli_query($conn,"DELETE FROM bank WHERE subscription_plan_id='$id'");
    }

    echo "UPDATED";

} else {
    echo "FAILED: " . mysqli_error($conn);
}
?>