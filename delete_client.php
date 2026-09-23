<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

if(!isset($_POST['id'])){
    exit("INVALID ID");
}

$id = intval($_POST['id']);

/* GET CLIENT ID */
$get = mysqli_query($conn,"SELECT client_id FROM client_subscription WHERE id='$id'");
if(!$get){
    exit("ERROR: ".mysqli_error($conn));
}

$row = mysqli_fetch_assoc($get);

if(!$row){
    exit("NOT FOUND");
}

$client_id = $row['client_id'];

    
/* DELETE SUBSCRIPTION */
$subDelete = mysqli_query($conn,"DELETE FROM client_subscription WHERE id='$id'");
if(!$subDelete){
    exit("SUB DELETE ERROR: ".mysqli_error($conn));
}

/* CHECK IF ANY SERVICES LEFT */
$check = mysqli_query($conn,"SELECT id FROM client_subscription WHERE client_id='$client_id'");
if(!$check){
    exit("CHECK ERROR: ".mysqli_error($conn));
}

/* DELETE CLIENT ONLY IF NO RECORD LEFT */
if(mysqli_num_rows($check) == 0){
    $clientDelete = mysqli_query($conn,"DELETE FROM client WHERE id='$client_id'");
    if(!$clientDelete){
        exit("CLIENT DELETE ERROR: ".mysqli_error($conn));
    }
}

echo "DELETED";
?>