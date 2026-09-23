<?php
include 'db.php';

$asset_name      = mysqli_real_escape_string($conn, $_POST['asset_name']);
$purchase_date   = $_POST['purchase_date'];
$expiry_date     = $_POST['expiry_date'];
$price           = $_POST['price'];
$payment_method  = mysqli_real_escape_string($conn, $_POST['payment_method']);
$asset_platform  = mysqli_real_escape_string($conn, $_POST['asset_platform']);
$description     = mysqli_real_escape_string($conn, $_POST['description']);

if($expiry_date <= $purchase_date){
    exit("Invalid expiry date");
}

if($asset_name=="" || $price=="" || $payment_method==""){
    exit("All fields required");
}

mysqli_begin_transaction($conn);

try{

    // 1️⃣ Insert Asset
    $insertAsset = mysqli_query($conn,"INSERT INTO company_assets
    (asset_name,purchase_date,expiry_date,price,payment_method,asset_platform,description)
    VALUES
    ('$asset_name','$purchase_date','$expiry_date','$price','$payment_method','$asset_platform','$description')");

    if(!$insertAsset){
        throw new Exception("Asset insert failed");
    }

    $asset_id = mysqli_insert_id($conn);

    // 2️⃣ Accounting Entry
    if($payment_method == "Cash"){

        $insertCash = mysqli_query($conn,"INSERT INTO cash
        (asset_id, payment_in, payment_out)
        VALUES
        ('$asset_id', 0, '$price')");

       if(!$insertCash){
    throw new Exception(mysqli_error($conn));
}

    }else{

        $insertBank = mysqli_query($conn,"INSERT INTO bank
        (company_asset_id, payment_in, payment_out, payment_type)
        VALUES
        ('$asset_id', 0, '$price', '$payment_method')");

        if(!$insertBank){
            throw new Exception("Bank entry failed");
        }
    }

    mysqli_commit($conn);
    echo "success";

}catch(Exception $e){

    mysqli_rollback($conn);
    echo "Database Error: " . $e->getMessage();
}
?>