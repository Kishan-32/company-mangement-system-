<?php
include 'db.php';

$id = $_POST['id'];

mysqli_begin_transaction($conn);

try{

    // Delete cash entry first
    mysqli_query($conn,"DELETE FROM cash WHERE cp_id='$id'");

    // Delete purchase
    $q = mysqli_query($conn,"DELETE FROM client_purchases WHERE id='$id'");

    if(!$q){
        throw new Exception("Delete failed");
    }

    mysqli_commit($conn);
    echo "success";

}catch(Exception $e){

    mysqli_rollback($conn);
    echo "error";
}
?>