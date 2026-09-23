<?php
include 'db.php';

$id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
$client_id    = intval($_POST['client_id']);
$asset_name   = mysqli_real_escape_string($conn,$_POST['asset_name']);
$purchase     = $_POST['purchase_date'];
$expiry       = $_POST['expiry_date'];
$price        = floatval($_POST['price']);
$payment_mode = mysqli_real_escape_string($conn,$_POST['payment_mode']);
$platform     = mysqli_real_escape_string($conn,$_POST['asset_platform']);
$desc         = mysqli_real_escape_string($conn,$_POST['description']);

if($expiry <= $purchase){
    exit("Invalid expiry date");
}

if(empty($asset_name)){
    exit("Asset name required");
}

mysqli_begin_transaction($conn);

try{

/* ======================== UPDATE ======================== */
if($id > 0){

    // Get old payment mode
    $oldQuery = mysqli_query($conn,
        "SELECT payment_mode FROM client_purchases WHERE id='$id'"
    );

    if(mysqli_num_rows($oldQuery) == 0){
        throw new Exception("Purchase not found");
    }

    $oldData     = mysqli_fetch_assoc($oldQuery);
    $oldPayment  = $oldData['payment_mode'];

    // Update purchase table
    $update = mysqli_query($conn,"UPDATE client_purchases SET
        client_id      = '$client_id',
        asset_name     = '$asset_name',
        purchase_date  = '$purchase',
        expiry_date    = '$expiry',
        price          = '$price',
        payment_mode   = '$payment_mode',
        asset_platform = '$platform',
        description    = '$desc'
        WHERE id = '$id'
    ");

    if(!$update){
        throw new Exception(mysqli_error($conn));
    }

    /* ================= PAYMENT SWITCH LOGIC ================= */

    /* ======== IF CASH SELECTED ======== */
    if($payment_mode == "Cash"){

        // Remove from bank if previously bank
        if($oldPayment != "Cash"){
            mysqli_query($conn,"DELETE FROM bank WHERE client_purchase_id='$id'");
        }

        // Check if already exists in cash
        $checkCash = mysqli_query($conn,"SELECT id FROM cash WHERE cp_id='$id'");

        if(mysqli_num_rows($checkCash) > 0){

            mysqli_query($conn,"UPDATE cash 
                SET payment_in=0,
                    payment_out='$price'
                WHERE cp_id='$id'");

        }else{

            mysqli_query($conn,"INSERT INTO cash
                (cp_id,payment_in,payment_out)
                VALUES ('$id',0,'$price')");
        }
    }

    /* ======== IF BANK SELECTED ======== */
    else{

        // Remove from cash if previously cash
        if($oldPayment == "Cash"){
            mysqli_query($conn,"DELETE FROM cash WHERE cp_id='$id'");
        }

        $checkBank = mysqli_query($conn,"SELECT id FROM bank WHERE client_purchase_id='$id'");

        if(mysqli_num_rows($checkBank) > 0){

            mysqli_query($conn,"UPDATE bank 
                SET payment_in=0,
                    payment_out='$price',
                    payment_type='$payment_mode'
                WHERE client_purchase_id='$id'");

        }else{

            mysqli_query($conn,"INSERT INTO bank
                (client_purchase_id,payment_in,payment_out,payment_type)
                VALUES ('$id',0,'$price','$payment_mode')");
        }
    }

}

/* ======================== INSERT ======================== */
else{

   $insert = mysqli_query($conn,"INSERT INTO client_purchases
    (client_id,asset_name,purchase_date,expiry_date,price,payment_mode,asset_platform,description)
    VALUES
    ('$client_id','$asset_name','$purchase','$expiry','$price','$payment_mode','$platform','$desc')
   ");

   if(!$insert){
        throw new Exception(mysqli_error($conn));
   }

   $cp_id = mysqli_insert_id($conn);

   /* ===== CASH INSERT ===== */
   if($payment_mode == "Cash"){

        mysqli_query($conn,"INSERT INTO cash
            (cp_id,payment_in,payment_out)
            VALUES ('$cp_id',0,'$price')");
   }

   /* ===== BANK INSERT ===== */
   else{

        mysqli_query($conn,"INSERT INTO bank
            (client_purchase_id,payment_in,payment_out,payment_type)
            VALUES ('$cp_id',0,'$price','$payment_mode')");
   }
}

mysqli_commit($conn);
echo "success";

}catch(Exception $e){
    mysqli_rollback($conn);
    echo "Database Error: " . $e->getMessage();
}
?>