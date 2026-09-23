<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* ================= ADD EXPENSE ================= */
if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {

    $item_name      = mysqli_real_escape_string($conn,$_POST['item_name']);
    $category_id    = intval($_POST['category_id']);
    $expense_date   = $_POST['expense_date'];
    $expense_amount = $_POST['expense_amount'];
    $payment_mode   = mysqli_real_escape_string($conn,$_POST['payment_mode']);
    $description    = mysqli_real_escape_string($conn,$_POST['description']);

    if ($item_name=="" || $category_id=="" || $expense_date=="" || $expense_amount=="")
        exit("EMPTY");

    $insert = mysqli_query($conn,"INSERT INTO expense_item
        (item_name,category_id,expense_date,expense_amount,payment_mode,description)
        VALUES
        ('$item_name','$category_id','$expense_date','$expense_amount','$payment_mode','$description')");

    if($insert){

        $expense_item_id = mysqli_insert_id($conn);

        // CASH ENTRY
        if(strtolower($payment_mode) == "cash"){
            mysqli_query($conn,"INSERT INTO cash
                (expense_item_id, payment_out)
                VALUES
                ('$expense_item_id', '$expense_amount')");
        }
        // BANK ENTRY (Bank Transfer, UPI, Cheque, Card)
        else{
            mysqli_query($conn,"INSERT INTO bank
                (expense_item_id, payment_out, payment_type)
                VALUES
                ('$expense_item_id', '$expense_amount', '$payment_mode')");
        }

        exit("SUCCESS");
    }
    else exit("ERROR");
}


/* ================= UPDATE EXPENSE ================= */
if (isset($_POST['action']) && $_POST['action'] == 'update_expense') {

    $id             = intval($_POST['id']);
    $item_name      = mysqli_real_escape_string($conn,$_POST['item_name']);
    $category_id    = intval($_POST['category_id']);
    $expense_date   = $_POST['expense_date'];
    $expense_amount = $_POST['expense_amount'];
    $payment_mode   = mysqli_real_escape_string($conn,$_POST['payment_mode']);
    $description    = mysqli_real_escape_string($conn,$_POST['description']);

    $update = mysqli_query($conn,"UPDATE expense_item SET
        item_name='$item_name',
        category_id='$category_id',
        expense_date='$expense_date',
        expense_amount='$expense_amount',
        payment_mode='$payment_mode',
        description='$description'
        WHERE id='$id'");

    if($update){

        // Delete old entries first
        mysqli_query($conn,"DELETE FROM cash WHERE expense_item_id='$id'");
        mysqli_query($conn,"DELETE FROM bank WHERE expense_item_id='$id'");

        // Reinsert based on new payment mode
        if(strtolower($payment_mode) == "cash"){
            mysqli_query($conn,"INSERT INTO cash
                (expense_item_id, payment_out)
                VALUES
                ('$id','$expense_amount')");
        }
        else{
            mysqli_query($conn,"INSERT INTO bank
                (expense_item_id, payment_out, payment_type)
                VALUES
                ('$id','$expense_amount','$payment_mode')");
        }

        exit("UPDATED");
    }
    else exit("ERROR");
}


/* ================= DELETE EXPENSE ================= */
if (isset($_POST['action']) && $_POST['action'] == 'delete_expense') {

    $id = intval($_POST['id']);

    mysqli_query($conn,"DELETE FROM cash WHERE expense_item_id='$id'");
    mysqli_query($conn,"DELETE FROM bank WHERE expense_item_id='$id'");
    $delete = mysqli_query($conn,"DELETE FROM expense_item WHERE id='$id'");

    if($delete) exit("DELETED");
    else exit("ERROR");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Expense Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f4f6f9;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 30px;
    background:#fff;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

.header h2{
    margin:0;
    color:#0c1b33;
}

.add-btn{
    background:linear-gradient(135deg,#0c1b33,#122a52);
    color:#fff;
    padding:10px 18px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:0.3s;
}
.add-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 15px rgba(0,0,0,0.15);
}

/* CONTENT */
.container{
    padding:30px;
}

.card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,0.05);
    overflow:hidden;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#0c1b33;
    color:#fff;
}

th, td{
    padding:14px;
    text-align:center;
    font-size:14px;
}

tbody tr{
    border-bottom:1px solid #eee;
    transition:0.2s;
}

tbody tr:hover{
    background:#f9fbff;
}

/* BUTTONS */
.btn{
    padding:6px 10px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:13px;
    margin:0 3px;
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

/* SWEETALERT */
.swal2-popup input,
.swal2-popup select,
.swal2-popup textarea{
    width:100%;
    padding:10px;
    margin-top:8px;
    border-radius:6px;
    border:1px solid #ddd;
}
</style>
</head>

<body>

<div class="header">
    <h2>💸 Expense Management</h2>
    <button class="add-btn" onclick="addExpense()">➕ Add Expense</button>
</div>

<div class="container">
<div class="card">
<table>
<thead>
<tr>
<th>sr.no</th>
<th>Item</th>
<th>Category</th>
<th>Date</th>
<th>Amount</th>
<th>Payment</th>
<th>Description</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php
$query = mysqli_query($conn,"
SELECT ei.*, ec.category_name 
FROM expense_item ei
JOIN expense_category ec ON ei.category_id=ec.id
ORDER BY ei.id ASC
");
$sr=1;
while($row=mysqli_fetch_assoc($query)){
?>
<tr>
<td><?= $sr++; ?></td>
<td><?= htmlspecialchars($row['item_name']); ?></td>
<td><?= htmlspecialchars($row['category_name']); ?></td>
<td><?= $row['expense_date']; ?></td>
<td><strong>₹ <?= number_format($row['expense_amount'],2); ?></strong></td>
<td><?= htmlspecialchars($row['payment_mode']); ?></td>
<td><?= htmlspecialchars($row['description']); ?></td>
<td>
<button class="btn edit-btn"
onclick="editExpense(
<?= $row['id']; ?>,
'<?= htmlspecialchars($row['item_name'],ENT_QUOTES); ?>',
<?= $row['category_id']; ?>,
'<?= $row['expense_date']; ?>',
'<?= $row['expense_amount']; ?>',
'<?= htmlspecialchars($row['payment_mode'],ENT_QUOTES); ?>',
'<?= htmlspecialchars($row['description'],ENT_QUOTES); ?>'
)"> <i class="fa fa-edit"></i>
</button>

<button class="btn delete-btn"
onclick="deleteExpense(<?= $row['id']; ?>)">
<i class="fa fa-trash"></i>
</button>
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>
</div>

<script>
function loadCategories(selected=""){
return $.get("get_expence_categories.php", function(options){
$("#category_id").html('<option value="">Select Category</option>'+options);
if(selected) $("#category_id").val(selected);
});
}

function addExpense(){
Swal.fire({
title:"Add Expense",
showCancelButton:true,
confirmButtonText:"Save",
html:`
<input id="item_name" placeholder="Item Name">
<select id="category_id"></select>
<input type="date" id="expense_date">
<input type="number" id="expense_amount" placeholder="Amount">
<select id="payment_mode">
<option value="">Payment Mode</option>
<option>Cash</option>
<option>Card</option>
<option>UPI</option>
<option>Bank Transfer</option>
<option>cheqe</option>
</select>
<textarea id="description" placeholder="Description"></textarea>
`,
didOpen:()=>{ loadCategories(); },
preConfirm:()=>{
let data={
action:"add_expense",
item_name:$("#item_name").val(),
category_id:$("#category_id").val(),
expense_date:$("#expense_date").val(),
expense_amount:$("#expense_amount").val(),
payment_mode:$("#payment_mode").val(),
description:$("#description").val()
};
if(!data.item_name||!data.category_id||!data.expense_date||!data.expense_amount){
Swal.showValidationMessage("Fill required fields");
return false;
}
return $.post("expense_item.php",data,function(res){
if(res=="SUCCESS"){
Swal.fire("Added!","Expense added","success").then(()=>location.reload());
}else{
Swal.fire("Error","Failed","error");
}
});
}
});
}

function editExpense(id,name,cat,date,amount,payment,desc){
Swal.fire({
title:"Edit Expense",
showCancelButton:true,
confirmButtonText:"Update",
html:`
<input id="item_name" value="${name}">
<select id="category_id"></select>
<input type="date" id="expense_date" value="${date}">
<input type="number" id="expense_amount" value="${amount}">
<select id="payment_mode">
<option>Cash</option>
<option>Card</option>
<option>UPI</option>
<option>Bank Transfer</option>
<option>cheqe</option>s
</select>
<textarea id="description">${desc}</textarea>
`,
didOpen:()=>{
loadCategories(cat);
$("#payment_mode").val(payment);
},
preConfirm:()=>{
return $.post("expense_item.php",{
action:"update_expense",
id:id,
item_name:$("#item_name").val(),
category_id:$("#category_id").val(),
expense_date:$("#expense_date").val(),
expense_amount:$("#expense_amount").val(),
payment_mode:$("#payment_mode").val(),
description:$("#description").val()
},function(res){
if(res=="UPDATED"){
Swal.fire("Updated!","Expense updated","success").then(()=>location.reload());
}else{
Swal.fire("Error","Update failed","error");
}
});
}
});
}

function deleteExpense(id){
Swal.fire({
title:"Are you sure?",
icon:"warning",
showCancelButton:true,
confirmButtonColor:"#dc3545",
confirmButtonText:"Yes, Delete"
}).then((r)=>{
if(r.isConfirmed){
$.post("expense_item.php",{action:"delete_expense",id:id},function(res){
if(res=="DELETED"){
Swal.fire("Deleted!","Expense removed","success").then(()=>location.reload());
}else{
Swal.fire("Error","Delete failed","error");
}
});
}
});
}
</script>

</body>
</html>