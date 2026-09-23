<?php
include 'db.php';
$id=$_GET['id'];
$q=mysqli_query($conn,"SELECT * FROM client WHERE id='$id'");
echo json_encode(mysqli_fetch_assoc($q));