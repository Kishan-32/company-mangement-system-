<?php
include 'db.php';

$id = intval($_POST['id']);

mysqli_begin_transaction($conn);

try{

    // Foreign key will auto delete from bank & cash if enabled
    // But safe manual delete:
    mysqli_query($conn,"DELETE FROM cash WHERE company_asset_id='$id'");
    mysqli_query($conn,"DELETE FROM bank WHERE company_asset_id='$id'");

    $delete = mysqli_query($conn,"DELETE FROM company_assets WHERE id='$id'");

    if(!$delete){
        throw new Exception("Delete failed");
    }

    mysqli_commit($conn);
    echo "success";

}catch(Exception $e){

    mysqli_rollback($conn);
    echo "error";
}
?>