<?php
include 'db.php';

$query = mysqli_query($conn,"SELECT * FROM expense_category ORDER BY category_name ASC");

while($row = mysqli_fetch_assoc($query)){
    echo "<option value='".$row['id']."'>".$row['category_name']."</option>";
}
?>
