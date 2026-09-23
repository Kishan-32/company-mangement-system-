<?php
include 'db.php';

$q=mysqli_query($conn,"SELECT id, client_name, email FROM client");

while($row=mysqli_fetch_assoc($q)){
    echo "<option value='{$row['id']}'>
            {$row['client_name']} ({$row['email']})
          </option>";
}
?>
