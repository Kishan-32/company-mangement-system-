<?php
include 'db.php';

/* TYPE */

$type = isset($_GET['type']) ? $_GET['type'] : 'cash';


/* ================= DELETE RECORD ================= */

if(isset($_GET['delete'])){

$id = intval($_GET['delete']);

if($type == "cash"){
    mysqli_query($conn,"DELETE FROM cash WHERE id='$id'");
}else{
    mysqli_query($conn,"DELETE FROM bank WHERE id='$id'");
}

header("Location: earning.php?type=".$type);
exit;

}


/* ================= SAVE / UPDATE ================= */

if(isset($_POST['save_adjust'])){

$description = mysqli_real_escape_string($conn,$_POST['description']);
$amount      = floatval($_POST['amount']);
$mode        = $_POST['payment_mode'];
$method      = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$edit_id     = isset($_POST['edit_id']) ? $_POST['edit_id'] : '';

$payment_in  = 0;
$payment_out = 0;

/* PAYMENT MODE */

if($mode == 'in'){
    $payment_in = $amount;
}else{
    $payment_out = $amount;
}


/* ================= UPDATE RECORD ================= */

if($edit_id != ''){

    if($type == "cash"){

        mysqli_query($conn,"
        UPDATE cash 
        SET description='$description',
            payment_in='$payment_in',
            payment_out='$payment_out'
        WHERE id='$edit_id'
        ");

        header("Location: earning.php?type=cash");
        exit;

    }else{

        mysqli_query($conn,"
        UPDATE bank 
        SET description='$description',
            payment_in='$payment_in',
            payment_out='$payment_out',
            payment_type='$method'
        WHERE id='$edit_id'
        ");

        header("Location: earning.php?type=bank");
        exit;

    }

}


/* ================= INSERT RECORD ================= */

if($type == "cash"){

    mysqli_query($conn,"
    INSERT INTO cash(description,payment_in,payment_out,created_at)
    VALUES('$description','$payment_in','$payment_out',NOW())
    ");

    header("Location: earning.php?type=cash");
    exit;

}else{

    mysqli_query($conn,"
    INSERT INTO bank(description,payment_in,payment_out,payment_type,created_at)
    VALUES('$description','$payment_in','$payment_out','$method',NOW())
    ");

    header("Location: earning.php?type=bank");
    exit;

}

}


/* ================= TOTAL CALCULATION ================= */

if($type == 'cash'){

$totalQuery = mysqli_query($conn,"
SELECT 
IFNULL(SUM(payment_in),0) as total_in,
IFNULL(SUM(payment_out),0) as total_out
FROM cash
");

}else{

$totalQuery = mysqli_query($conn,"
SELECT 
IFNULL(SUM(payment_in),0) as total_in,
IFNULL(SUM(payment_out),0) as total_out
FROM bank
");

}

$totalData = mysqli_fetch_assoc($totalQuery);

$total_in  = $totalData['total_in'];
$total_out = $totalData['total_out'];

$net = $total_in - $total_out;

?>

<!DOCTYPE html>
<html>
<head>

<title>Cash & Bank Report</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

body{
margin:0;
padding:25px;
font-family:'Poppins',sans-serif;
background:linear-gradient(135deg,#eef2f7,#dbe7ff);
}

/* HEADER */

.page-title{
font-size:24px;
font-weight:600;
margin-bottom:25px;
color:#0c1b33;
}

/* SUMMARY */

.summary{
display:flex;
gap:20px;
margin-bottom:30px;
}

.card{
flex:1;
padding:25px;
border-radius:15px;
color:#fff;
box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

.green{background:linear-gradient(135deg,#28a745,#1e7e34);}
.red{background:linear-gradient(135deg,#dc3545,#b21f2d);}
.blue{background:linear-gradient(135deg,#007bff,#0056b3);}

.card h4{margin:0;}
.card h2{margin-top:10px}

/* FILTER */

.top-buttons{
margin-bottom:20px;
}

.top-buttons a{
padding:10px 20px;
text-decoration:none;
border-radius:30px;
margin-right:10px;
background:#fff;
color:#000;
box-shadow:0 5px 10px rgba(0,0,0,0.1);
}

.active{
background:#0c1b33 !important;
color:#fff !important;
}

/* ADJUST BUTTON */

.adjust-btn{
float:right;
padding:10px 18px;
border:none;
border-radius:30px;
background:#ff9800;
color:#fff;
cursor:pointer;
font-weight:600;
box-shadow:0 5px 12px rgba(0,0,0,0.2);
}

/* TABLE */

.table-container{
background:#fff;
padding:20px;
border-radius:15px;
box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

table{
width:100%;
border-collapse:collapse;
}

thead{
background:#0c1b33;
color:#fff;
}

th,td{
padding:12px;
text-align:center;
}

tbody tr{
border-bottom:1px solid #eee;
}

/* POPUP */

.popup{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.4);
display:none;
align-items:center;
justify-content:center;
backdrop-filter:blur(4px);
}

.popup-box{
background:#fff;
padding:30px;
width:360px;
border-radius:15px;
box-shadow:0 15px 40px rgba(0,0,0,0.2);
}

.popup-box h3{
margin-top:0;
margin-bottom:15px;
}

.popup-box input,
.popup-box select{
width:100%;
padding:12px;
border-radius:8px;
border:1px solid #ddd;
margin-bottom:12px;
font-family:'Poppins',sans-serif;
font-size:14px;
background:#fff;
box-sizing:border-box;
}

.mode-buttons{
display:flex;
gap:10px;
margin-bottom:15px;
}

.mode-buttons button{
flex:1;
padding:10px;
border:none;
border-radius:8px;
cursor:pointer;
font-weight:600;
}

.in-btn{background:#28a745;color:#fff;}
.out-btn{background:#dc3545;color:#fff;}

.save-btn{
background:#0c1b33;
color:#fff;
border:none;
padding:12px;
border-radius:8px;
cursor:pointer;
width:100%;
}

.cancel-btn{
background:#eee;
border:none;
padding:10px;
border-radius:8px;
cursor:pointer;
width:100%;
margin-top:10px;
}

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

.btn:hover{
transform:translateY(-2px);
box-shadow:0 6px 12px rgba(0,0,0,0.15);
}
</style>
</head>

<body>

<div class="page-title">💳 Cash & Bank Report</div>

<!-- SUMMARY -->

<div class="summary">

<div class="card green">
<h4>Total Payment In</h4>
<h2>₹<?php echo number_format($total_in,2); ?></h2>
</div>

<div class="card red">
<h4>Total Payment Out</h4>
<h2>₹<?php echo number_format($total_out,2); ?></h2>
</div>

<div class="card blue">
<h4>Net Balance</h4>
<h2>₹<?php echo number_format($net,2); ?></h2>
</div>

</div>

<!-- FILTER -->

<div class="top-buttons">

<a href="earning.php?type=cash" class="<?php if($type=='cash') echo 'active'; ?>">Cash</a>

<a href="earning.php?type=bank" class="<?php if($type=='bank') echo 'active'; ?>">Bank</a>

<?php if($type=='cash'): ?>
<button class="adjust-btn" onclick="openAdjust()">+ Cash Adjust</button>
<?php endif; ?>

<?php if($type=='bank'): ?>
<button class="adjust-btn" onclick="openAdjust()">+ deposite and withdraw</button>
<?php endif; ?>
</div>

<!-- TABLE -->

<div class="table-container">

<table>

<thead>
<tr>
<th>#</th>
<th>Description</th>
<th>Payment In (₹)</th>
<th>Payment Out (₹)</th>

<?php if($type=='bank'): ?>
<th>Payment Method</th>
<?php endif; ?>

<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php

if($type == 'cash'){
$query = mysqli_query($conn,"SELECT * FROM cash ORDER BY id DESC");
}else{
$query = mysqli_query($conn,"SELECT * FROM bank ORDER BY id DESC");
}

$i=1;

while($row = mysqli_fetch_assoc($query)){

/* ================= DESCRIPTION LOGIC ================= */

$description = "-";

/* MANUAL DESCRIPTION */
if(!empty($row['description'])){
$description = $row['description'];
}

else{

if($type == 'cash'){

if(!empty($row['expense_item_id'])){
$description = "Expense For Company Item";
}
elseif(!empty($row['cp_id'])){
$description = "Customer Purchase";
}
elseif(!empty($row['asset_id'])){
$description = "Company Purchase";
}
elseif(!empty($row['client_subscription_id'])){
$description = "Client Subscription";
}
elseif(!empty($row['subscription_plan_id'])){
$description = "Subscription Plan";
}
elseif(!empty($row['service_order_id'])){
$description = "Customer Order";
}

} else {

if(!empty($row['expense_item_id'])){
$description = "Expense On Item";
}
elseif(!empty($row['company_asset_id'])){
$description = "Company Asset";
}
elseif(!empty($row['client_purchase_id'])){
$description = "Client Asset";
}
elseif(!empty($row['subscription_plan_id'])){
$description = "Subscription Plan";
}
elseif(!empty($row['client_subscription_id'])){
$description = "Client Subscription";
}
elseif(!empty($row['service_order_id'])){
$description = "Order";
}

}

}

echo "<tr>

<td>".$i++."</td>

<td>".$description."</td>

<td>₹".$row['payment_in']."</td>

<td>₹".$row['payment_out']."</td>";

if($type=='bank'){
echo "<td>".$row['payment_type']."</td>";
}

echo "<td>".$row['created_at']."</td>";

echo "<td>";

if(!empty($row['description'])){
echo "
<button onclick='editRow(
\"".$row['id']."\",
\"".$row['description']."\",
\"".$row['payment_in']."\",
\"".$row['payment_out']."\"
)' class='btn edit-btn'>
<i class=\"fa fa-edit \"></i>
</button>

<button onclick='deleteRow(".$row['id'].",\"".$type."\")' class='btn delete-btn'>
<i class=\"fa fa-trash\"></i>
</button>
";

}else{

echo "No Action";

}

echo "</td></tr>";

}

?>

</tbody>
</table>

</div>

<!-- POPUP -->

<div class="popup" id="adjustPopup">

<div class="popup-box">

<form method="POST">
<input type="hidden" name="edit_id" id="edit_id">

<?php if($type=='cash'): ?>
<h3>💰 Cash Adjust Balance</h3>
<?php endif; ?>

<?php if($type=='bank'): ?>
<h3>💰 Bank  Adjust Balance</h3>
<?php endif; ?>


<input type="text" name="description" placeholder="Description" required>

<div class="mode-buttons">
<button type="button" class="in-btn" onclick="setMode('in')">Payment In</button>
<button type="button" class="out-btn" onclick="setMode('out')">Payment Out</button>
</div>

<input type="hidden" name="payment_mode" id="payment_mode">

<input type="number" step="0.01" name="amount" placeholder="Amount" required>

<?php if($type=='bank'): ?>

<select name="payment_method">
<option value="UPI">UPI</option>
<option value="Bank Transfer">Bank Transfer</option>
<option value="Cheque">Cheque</option>
<option value="Card">Card</option>
</select>

<?php endif; ?>

<button type="submit" name="save_adjust" class="save-btn">Save Adjustment</button>

<button type="button" onclick="closeAdjust()" class="cancel-btn">Cancel</button>

</form>

</div>

</div>

<script>
function editRow(id,description,payment_in,payment_out){

openAdjust();

document.getElementById("edit_id").value=id;
document.querySelector("input[name='description']").value=description;

let amount = payment_in > 0 ? payment_in : payment_out;

document.querySelector("input[name='amount']").value=amount;

if(payment_in > 0){
setMode('in');
}else{
setMode('out');
}

}

function openAdjust(){
document.getElementById("adjustPopup").style.display="flex";
}

function closeAdjust(){
document.getElementById("adjustPopup").style.display="none";
}

function deleteRow(id,type){

Swal.fire({
title: "Delete Record?",
text: "This record will be permanently deleted!",
icon: "warning",
showCancelButton: true,
confirmButtonColor: "#d33",
cancelButtonColor: "#3085d6",
confirmButtonText: "Yes, delete it!"
}).then((result) => {

if(result.isConfirmed){

window.location.href = "?delete="+id+"&type="+type;

}

});

}

function setMode(mode){

document.getElementById("payment_mode").value = mode;

let inBtn=document.querySelector(".in-btn");
let outBtn=document.querySelector(".out-btn");

if(mode=="in"){
inBtn.style.opacity="1";
outBtn.style.opacity=".5";
}else{
outBtn.style.opacity="1";
inBtn.style.opacity=".5";
}

}

</script>

</body>
</html>