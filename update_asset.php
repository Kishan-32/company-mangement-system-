<?php
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_POST['id'])){
    exit("Invalid Request");
}

$id        = intval($_POST['id']);
$name      = mysqli_real_escape_string($conn, $_POST['asset_name']);
$purchase  = $_POST['purchase_date'];
$expiry    = $_POST['expiry_date'];
$price     = floatval($_POST['price']);
$payment   = mysqli_real_escape_string($conn, $_POST['payment_method']);
$platform  = mysqli_real_escape_string($conn, $_POST['asset_platform']);
$desc      = mysqli_real_escape_string($conn, $_POST['description']);

/* ================= VALIDATION ================= */
if(empty($name) || empty($purchase) || empty($expiry) || empty($price)){
    exit("All fields are required");
}

if($expiry < $purchase){
    exit("Expiry must be after purchase date");
}

if(empty($payment)){
    exit("Please select payment method");
}

/* ================= START TRANSACTION ================= */
mysqli_begin_transaction($conn);

try{

    /* ================= GET OLD DATA ================= */
    $oldQuery = mysqli_query($conn,"SELECT payment_method FROM company_assets WHERE id='$id'");

    if(!$oldQuery || mysqli_num_rows($oldQuery) == 0){
        throw new Exception("Asset not found");
    }

    $oldData = mysqli_fetch_assoc($oldQuery);
    $oldPayment = $oldData['payment_method'];

    /* ================= UPDATE ASSET ================= */
    $update = mysqli_query($conn,"UPDATE company_assets SET
        asset_name      = '$name',
        purchase_date   = '$purchase',
        expiry_date     = '$expiry',
        price           = '$price',
        payment_method  = '$payment',
        asset_platform  = '$platform',
        description     = '$desc'
        WHERE id = '$id'
    ");

    if(!$update){
        throw new Exception("Asset update failed: " . mysqli_error($conn));
    }

    /* ================= HANDLE CASH ================= */
    if($payment == "Cash"){

        // Remove from bank if changed
        if($oldPayment != "Cash"){
            if(!mysqli_query($conn,"DELETE FROM bank WHERE company_asset_id='$id'")){
                throw new Exception(mysqli_error($conn));
            }
        }

        $checkCash = mysqli_query($conn,"SELECT id FROM cash WHERE asset_id='$id'");

        if(mysqli_num_rows($checkCash) > 0){

            $updateCash = mysqli_query($conn,"UPDATE cash 
                SET payment_out='$price'
                WHERE asset_id='$id'");

            if(!$updateCash){
                throw new Exception("Cash update failed: " . mysqli_error($conn));
            }

        }else{

            $insertCash = mysqli_query($conn,"INSERT INTO cash 
                (asset_id, payment_in, payment_out)
                VALUES ('$id', 0, '$price')");

            if(!$insertCash){
                throw new Exception("Cash insert failed: " . mysqli_error($conn));
            }
        }

    }

    /* ================= HANDLE BANK ================= */
    else{

        // Remove from cash if changed
        if($oldPayment == "Cash"){
            if(!mysqli_query($conn,"DELETE FROM cash WHERE asset_id='$id'")){
                throw new Exception(mysqli_error($conn));
            }
        }

        $checkBank = mysqli_query($conn,"SELECT id FROM bank WHERE company_asset_id='$id'");

        if(mysqli_num_rows($checkBank) > 0){

            $updateBank = mysqli_query($conn,"UPDATE bank 
                SET payment_out='$price',
                    payment_type='$payment'
                WHERE company_asset_id='$id'");

            if(!$updateBank){
                throw new Exception("Bank update failed: " . mysqli_error($conn));
            }

        }else{

            $insertBank = mysqli_query($conn,"INSERT INTO bank
                (company_asset_id, payment_in, payment_out, payment_type)
                VALUES ('$id', 0, '$price', '$payment')");

            if(!$insertBank){
                throw new Exception("Bank insert failed: " . mysqli_error($conn));
            }
        }

    }

    /* ================= COMMIT ================= */
    mysqli_commit($conn);

    echo "UPDATED"; // ✅ IMPORTANT

}catch(Exception $e){

    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}
?>