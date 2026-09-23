<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit;
}

$id = intval($_POST['id']);

$q = mysqli_query($conn,"SELECT * FROM subscriptions WHERE id='$id'");

if ($row = mysqli_fetch_assoc($q)) {
    echo json_encode($row); // includes description
} else {
    echo "NOT_FOUND";
}
