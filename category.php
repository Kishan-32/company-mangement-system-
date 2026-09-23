<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* ================= ADD CATEGORY ================= */
if (isset($_POST['action']) && $_POST['action'] == 'add_category') {

    $name = mysqli_real_escape_string($conn, $_POST['category_name']);

    if ($name == "") exit("EMPTY");

    $insert = mysqli_query($conn, "INSERT INTO expense_category (category_name) VALUES ('$name')");

    if ($insert) exit("SUCCESS");
    else exit("ERROR");
}

/* ================= UPDATE CATEGORY ================= */
if (isset($_POST['action']) && $_POST['action'] == 'update_category') {

    $id   = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['category_name']);

    if ($name == "") exit("EMPTY");

    $update = mysqli_query($conn, "UPDATE expense_category SET category_name='$name' WHERE id='$id'");

    if ($update) exit("UPDATED");
    else exit("ERROR");
}

/* ================= DELETE CATEGORY ================= */
if (isset($_POST['action']) && $_POST['action'] == 'delete_category') {

    $id = intval($_POST['id']);

    $delete = mysqli_query($conn, "DELETE FROM expense_category WHERE id='$id'");

    if ($delete) exit("DELETED");
    else exit("ERROR");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Expense Category Management</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

body{
    font-family: 'Segoe UI', sans-serif;
    background:#f4f6f9;
    margin:0;
    padding:30px;
}

.card{
    background:white;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    padding:25px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.header h2{
    margin:0;
    font-weight:600;
    color:#333;
}

.search-box{
    padding:8px 12px;
    border:1px solid #ddd;
    border-radius:6px;
    width:200px;
}

button{
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    font-weight:500;
    transition:0.3s;
}

.add-btn{
    background:linear-gradient(45deg,#0c1b33,#1e3c72);
    color:white;
}

.add-btn:hover{
    opacity:0.9;
}

.edit-btn{
    background:linear-gradient(135deg,#007bff,#0056b3);
    color:#ffff;
}

.edit-btn:hover{
    background:#007bff,#0056b3;
}

.delete-btn{
    background:linear-gradient(135deg,#dc3545,#a71d2a);
    color:white;
}

.delete-btn:hover{
    background:linear-gradient(135deg,#dc3545,#a71d2a);
}

table{
    width:100%;
    border-collapse:collapse;
}

table thead{
    background:#0c1b33;
    color:white;
}

table th, table td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}

table tbody tr:hover{
    background:#f9f9f9;
}

@media(max-width:768px){
    .header{
        flex-direction:column;
        gap:10px;
        align-items:flex-start;
    }
    .search-box{
        width:100%;
    }
}

</style>
</head>
<body>

<div class="card">

<div class="header">
    <h2>Expense Category Management</h2>
    <div>
        <input type="text" id="search" class="search-box" placeholder="Search category...">
        <button class="add-btn" onclick="addCategory()">➕ Add Category</button>
    </div>
</div>

<table id="categoryTable">
<thead>
<tr>
    <th>SR No</th>
    <th>Category Name</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
$query = mysqli_query($conn, "SELECT * FROM expense_category ORDER BY id ASC");
$sr = 1;
while($row = mysqli_fetch_assoc($query)){
?>
<tr>
    <td><?php echo $sr++; ?></td>
    <td><span class="badge"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
    <td>
        <button class="edit-btn"
            onclick="editCategory(<?php echo $row['id']; ?>,'<?php echo htmlspecialchars($row['category_name'],ENT_QUOTES); ?>')">
            <i class="fa fa-edit"></i>
        </button>

        <button class="delete-btn"
            onclick="deleteCategory(<?php echo $row['id']; ?>)">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

</div>

<script>

/* ================= SEARCH FILTER ================= */
$("#search").on("keyup", function(){
    let value = $(this).val().toLowerCase();
    $("#categoryTable tbody tr").filter(function(){
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

/* ================= ADD ================= */
function addCategory(){
    Swal.fire({
        title: "Add Category",
        input: "text",
        inputLabel: "Category Name",
        showCancelButton: true,
        confirmButtonText: "Save",
        confirmButtonColor:"#1e3c72",
        preConfirm: (name) => {

            if(name === ""){
                Swal.showValidationMessage("Category name required");
                return false;
            }

            return $.post("category.php", {
                action:"add_category",
                category_name:name
            }, function(response){

                if(response=="SUCCESS"){
                    Swal.fire("Added!","Category created successfully","success")
                        .then(()=>location.reload());
                } else {
                    Swal.fire("Error!","Something went wrong","error");
                }

            });
        }
    });
}

/* ================= EDIT ================= */
function editCategory(id, oldName){

    Swal.fire({
        title: "Edit Category",
        input: "text",
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: "Update",
        confirmButtonColor:"#ffc107",
        preConfirm: (name) => {

            if(name === ""){
                Swal.showValidationMessage("Category name required");
                return false;
            }

            return $.post("category.php", {
                action:"update_category",
                id:id,
                category_name:name
            }, function(response){

                if(response=="UPDATED"){
                    Swal.fire("Updated!","Category updated successfully","success")
                        .then(()=>location.reload());
                } else {
                    Swal.fire("Error!","Update failed","error");
                }

            });
        }
    });
}

/* ================= DELETE ================= */
function deleteCategory(id){

    Swal.fire({
        title: "Are you sure?",
        text: "This category will be permanently deleted",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        confirmButtonText: "Yes, Delete"
    }).then((result)=>{

        if(result.isConfirmed){

            $.post("category.php", {
                action:"delete_category",
                id:id
            }, function(response){

                if(response=="DELETED"){
                    Swal.fire("Deleted!","Category removed successfully","success")
                        .then(()=>location.reload());
                } else {
                    Swal.fire("Error!","Delete failed","error");
                }

            });

        }

    });
}

</script>

</body>
</html>