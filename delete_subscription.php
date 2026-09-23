<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

if (empty($_POST['id'])) exit("INVALID");

$id = intval($_POST['id']);

$check = mysqli_query($conn,"SELECT id FROM subscriptions WHERE id='$id'");
if (mysqli_num_rows($check) == 0) exit("NOT_FOUND");

/* DELETE RELATED PAYMENTS FIRST */
mysqli_query($conn,"DELETE FROM cash WHERE subscription_plan_id='$id'");
mysqli_query($conn,"DELETE FROM bank WHERE client_purchase_id='$id'");

/* DELETE SUBSCRIPTION */
if (mysqli_query($conn,"DELETE FROM subscriptions WHERE id='$id'")) {
    echo "DELETED";
} else {
    echo "ERROR";
}
?>