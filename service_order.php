<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* ================= SAVE / UPDATE ================= */
if(isset($_POST['save_order'])){

    mysqli_begin_transaction($conn);

    try {

        $id             = $_POST['id'] ?? '';
       $client_id = $_POST['client_id'];

if($client_id == "new_client"){

    $new_name  = mysqli_real_escape_string($conn,$_POST['new_client_name']);
    $new_mobile = mysqli_real_escape_string($conn,$_POST['new_mobile']);
    $new_email  = mysqli_real_escape_string($conn,$_POST['new_email']);

    if(empty($new_name)){
        throw new Exception("Client name required");
    }

    mysqli_query($conn,"INSERT INTO client (client_name,mobile_number,email)
    VALUES('$new_name','$new_mobile','$new_email')");

    $client_id = mysqli_insert_id($conn);

}else{
    $client_id = intval($client_id);
}
        $service_name   = mysqli_real_escape_string($conn,$_POST['service_name']);
        $price          = floatval($_POST['price']);
        $discount_type  = mysqli_real_escape_string($conn,$_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $final_price    = floatval($_POST['final_price']);
        $paid_amount    = floatval($_POST['paid_amount'] ?? 0);
        $payment_mode   = mysqli_real_escape_string($conn,$_POST['payment_mode']);
        $description    = mysqli_real_escape_string($conn,$_POST['description']);
        $reference      = mysqli_real_escape_string($conn,$_POST['reference']);

        if(empty($client_id) || empty($service_name)){
            throw new Exception("Required fields missing");
        }

        if($paid_amount > $final_price){
            throw new Exception("Paid amount cannot be greater than Final price");
        }

        /* ---------- INSERT ---------- */
        if(empty($id)){

            mysqli_query($conn,"INSERT INTO service_orders 
            (client_id,service_name,price,discount_type,discount_value,final_price,paid_amount,description,reference_no,payment_mode)
            VALUES
            ('$client_id','$service_name','$price','$discount_type','$discount_value','$final_price','$paid_amount','$description','$reference','$payment_mode')");

            $order_id = mysqli_insert_id($conn);
        }
        /* ---------- UPDATE ---------- */
        else{

            $order_id = intval($id);

            mysqli_query($conn,"UPDATE service_orders SET
                client_id='$client_id',
                service_name='$service_name',
                price='$price',
                discount_type='$discount_type',
                discount_value='$discount_value',
                final_price='$final_price',
                paid_amount='$paid_amount',
                description='$description',
                reference_no='$reference',
                payment_mode='$payment_mode'
                WHERE id='$order_id'");
        }

        /* ================= PAYMENT SYNC ================= */

        /* Remove old entries first */
        mysqli_query($conn,"DELETE FROM cash WHERE service_order_id='$order_id'");
        mysqli_query($conn,"DELETE FROM bank WHERE service_order_id='$order_id'");

        /* -------- CASH -------- */
        if($payment_mode === "Cash" && $paid_amount > 0){

            mysqli_query($conn,"INSERT INTO cash
            (service_order_id, payment_in, payment_out)
            VALUES('$order_id','$paid_amount','0')");
        }

        /* -------- BANK (4 METHODS) -------- */
        if(
            $payment_mode === "UPI" ||
            $payment_mode === "Card" ||
            $payment_mode === "Bank Transfer" ||
            $payment_mode === "Cheque"
        ){

            if($paid_amount > 0){

                mysqli_query($conn,"INSERT INTO bank
                (service_order_id, payment_in, payment_out, payment_type)
                VALUES
                ('$order_id','$paid_amount','0','$payment_mode')");
            }
        }

        mysqli_commit($conn);
        header("Location: service_order.php?success=1");
        exit;

    } catch (Exception $e) {

        mysqli_rollback($conn);
        die("Transaction Failed: ".$e->getMessage());
    }
}

/* ================= DELETE ================= */
if(isset($_POST['delete_id'])){

    mysqli_begin_transaction($conn);

    try {

        $id = intval($_POST['delete_id']);

        /* No need to manually delete bank because of FK CASCADE */
        mysqli_query($conn,"DELETE FROM cash WHERE service_order_id='$id'");

        mysqli_query($conn,"DELETE FROM service_orders WHERE id='$id'");

        mysqli_commit($conn);

        echo "success";
        exit;

    } catch (Exception $e) {

        mysqli_rollback($conn);
        echo "error";
        exit;
    }
}   
?>

<!DOCTYPE html>
<html>
<head>
<title>Service Orders</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#eef2f7,#d9e4f5);
    padding:30px;
    margin:0;
}

/* CARD */
.page-card{
    background:white;
    padding:30px;
    border-radius:14px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h2{
    margin:0;
    color:#0c1b33;
}

.add-btn{
    background:linear-gradient(45deg,#6C63FF,#4a3fff);
    color:white;
    border:none;
    padding:12px 22px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:0.3s;
}

.add-btn:hover{
    transform:scale(1.05);
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.6);
    justify-content:center;
    align-items:center;
    backdrop-filter:blur(4px);
}

.modal-content{
    background:white;
    width:550px;
    max-height:90vh;
    overflow-y:auto;
    padding:30px;
    border-radius:14px;
    position:relative;
    box-shadow:0 15px 40px rgba(0,0,0,0.2);
}

.close{
    position:absolute;
    right:18px;
    top:15px;
    font-size:20px;
    cursor:pointer;
}

.form-group{
    margin-bottom:18px;
}

label{
    font-weight:600;
    margin-bottom:6px;
    display:block;
}

input,select,textarea{
    width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:8px;
    font-size:14px;
    transition:0.3s;
}

input:focus,select:focus,textarea:focus{
    border-color:#6C63FF;
    outline:none;
    box-shadow:0 0 0 3px rgba(108,99,255,0.1);
}

textarea{ height:80px; }

.save-btn{
    width:100%;
    background:linear-gradient(45deg,#28a745,#1e7e34);
    color:white;
    border:none;
    padding:12px;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
}

/* TABLE */
table{
    width:100%;
    margin-top:30px;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
}

th{
    background:#0c1b33;
    color:white;
    padding:14px;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
    text-align:center;
}

tr:hover{ background:#f8f9ff; }

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
</style>
</head>
<body>

<div class="page-card">

<div class="header">
<h2>📋 Service Orders Management</h2>
<button class="add-btn" onclick="openModal()">+ Add Order</button>
</div>

<!-- MODAL -->
<div class="modal" id="orderModal">
<div class="modal-content">
<span class="close" onclick="closeModal()">✖</span>
<h3 id="modalTitle">Add Service Order</h3>

<form method="POST">
<input type="hidden" name="id" id="order_id">

<div class="form-group">
<label>Select Client</label>

<select name="client_id" id="client_id" required onchange="handleClientSelect()">
<option value="">Select Client</option>

<?php
$clients=mysqli_query($conn,"SELECT id, client_name, mobile_number, email FROM client");

while($c=mysqli_fetch_assoc($clients)){
echo "<option 
value='{$c['id']}'
data-mobile='".htmlspecialchars($c['mobile_number'])."'
data-email='".htmlspecialchars($c['email'])."'
>
".htmlspecialchars($c['client_name'])."
</option>";
}
?>

<option value="new_client">+ New Client</option>

</select>
</div>

<div id="new_client_box" style="display:none">

<div class="form-group">
<label>Client Name</label>
<input type="text" name="new_client_name" id="new_client_name">
</div>

<div class="form-group">
<label>Mobile Number</label>
<input type="text" name="new_mobile" id="new_mobile">
</div>

<div class="form-group">
<label>Email</label>
<input type="text" name="new_email" id="new_email">
</div>

</div>

<div class="form-group">
<label>Mobile</label>
<input type="text" id="mobile" >
</div>

<div class="form-group">
<label>Email</label>
<input type="text" id="email" >
</div>

<div class="form-group">
<label>Service Name</label>
<input type="text" name="service_name" id="service_name" required>
</div>

<div class="form-group">
<label>Price</label>
<input type="number" name="price" id="price" required>
</div>

<div class="form-group">
<label>Discount Type</label>
<select name="discount_type" id="discount_type">
<option value="amount">Amount (₹)</option>
<option value="percent">Percentage (%)</option>
</select>
</div>

<div class="form-group">
<label>Discount Value</label>
<input type="number" name="discount_value" id="discount_value" value="0">
</div>

<div class="form-group">
<label>Final Price</label>
<input type="number" name="final_price" id="final_price" readonly>
</div>

<div class="form-group">
<label>Paid Amount</label>
<input type="number" name="paid_amount" id="paid_amount" value="0">
</div>

<div class="form-group">
<label>Unpaid Amount</label>
<input type="number" id="unpaid_amount" readonly>
</div>

<div class="form-group">
<label>Payment Mode</label>
<select name="payment_mode" id="payment_mode" required>
<option value="">Select Payment Mode</option>
<option>Cash</option>
<option>UPI</option>
<option>Card</option>
<option>Bank Transfer</option>
<option>Cheque</option>
</select>
</div>

<div class="form-group">
<label>Description</label>
<textarea name="description" id="description"></textarea>
</div>

<div class="form-group">
<label>Reference</label>
<input type="text" name="reference" id="reference">
</div>

<button type="submit" name="save_order" class="save-btn">Save Order</button>
</form>
</div>
</div>

<!-- TABLE -->
<table>
<tr>
<th>SR</th>
<th>Client</th>
<th>Service</th>
<th>Price</th>
<th>Discount</th>
<th>Final</th>
<th>Paid</th>
<th>Unpaid</th>
<th>Payment</th>
<th>Description</th>
<th>Reference</th>
<th>Action</th>
</tr>

<?php
$q=mysqli_query($conn,"SELECT so.*,c.client_name 
FROM service_orders so
LEFT JOIN client c ON so.client_id=c.id
ORDER BY so.id DESC");

$sr=1;


while($d=mysqli_fetch_assoc($q)){
?>
<tr>
<td><?= $sr++ ?></td>
<td><?= htmlspecialchars($d['client_name']) ?></td>
<td><?= htmlspecialchars($d['service_name']) ?></td>
<td>₹<?= number_format($d['price'],2) ?></td>
<td>
<?= $d['discount_value'] ?>
<?= $d['discount_type']=="percent" ? "%" : "₹" ?>
</td>
<?php
$paid   = $d['paid_amount'] ?? 0;
$unpaid = $d['final_price'] - $paid;
?>
<td><strong>₹<?= number_format($d['final_price'],2) ?></strong></td>

<td style="color:green;font-weight:600;">
₹<?= number_format($paid,2) ?>
</td>

<td style="color:<?= $unpaid>0 ? '#dc3545' : '#28a745' ?>;font-weight:600;">
₹<?= number_format($unpaid,2) ?>
</td>
<td><?= htmlspecialchars($d['payment_mode']) ?></td>
<td style="max-width:180px;word-wrap:break-word;">
<?= htmlspecialchars($d['description']) ?>
</td>
<td><?= htmlspecialchars($d['reference_no']) ?></td>
<td>
<button onclick='editOrder(<?= json_encode($d) ?>)' class="btn edit-btn"> <i class="fa fa-edit"></i></button>
<button onclick="deleteOrder(<?= $d['id'] ?>)" class="btn delete-btn"><i class="fa fa-trash"></i></button>
</td>
</tr>
<?php } ?>
</table>

</div>

<script>

    $("#paid_amount,#final_price").on("input",function(){
let final=parseFloat($("#final_price").val())||0;
let paid=parseFloat($("#paid_amount").val())||0;

let unpaid = final - paid;
if(unpaid < 0) unpaid = 0;

$("#unpaid_amount").val(unpaid.toFixed(2));
});
    function fillClient(){

    var selectedOption = $("#client_id option:selected");

    if(selectedOption.val() !== ""){
        $("#mobile").val(selectedOption.data("mobile") || "");
        $("#email").val(selectedOption.data("email") || "");
    } else {
        $("#mobile").val("");
        $("#email").val("");
    }
}
function openModal(){

document.getElementById("orderModal").style.display="flex";

$("#client_id").val("");
$("#new_client_box").hide();

$("#mobile").closest(".form-group").show();
$("#email").closest(".form-group").show();

$("#mobile").val("");
$("#email").val("");

}

function closeModal(){ document.getElementById("orderModal").style.display="none"; }

$("#price,#discount_value,#discount_type").on("input change",function(){
let price=parseFloat($("#price").val())||0;
let discount=parseFloat($("#discount_value").val())||0;
let type=$("#discount_type").val();
let final= type=="amount"? price-discount : price-(price*discount/100);
if(final<0) final=0;
$("#final_price").val(final.toFixed(2));
});

function handleClientSelect(){

let val = $("#client_id").val();

if(val == "new_client"){

    // show new client inputs
    $("#new_client_box").show();

    // hide existing client mobile/email fields
    $("#mobile").closest(".form-group").hide();
    $("#email").closest(".form-group").hide();

    $("#mobile").val("");
    $("#email").val("");

}
else{

    // hide new client inputs
    $("#new_client_box").hide();

    // show existing mobile/email fields
    $("#mobile").closest(".form-group").show();
    $("#email").closest(".form-group").show();

    var selectedOption = $("#client_id option:selected");

    $("#mobile").val(selectedOption.data("mobile") || "");
    $("#email").val(selectedOption.data("email") || "");

}

}

function editOrder(data){

$("#modalTitle").text("Edit Service Order");
$("#order_id").val(data.id);
$("#client_id").val(data.client_id).change(); // trigger change

$("#service_name").val(data.service_name);
$("#price").val(data.price);
$("#paid_amount").val(data.paid_amount);
let unpaid = data.final_price - data.paid_amount;
$("#unpaid_amount").val(unpaid.toFixed(2));
$("#discount_type").val(data.discount_type);
$("#discount_value").val(data.discount_value);
$("#final_price").val(data.final_price);
$("#payment_mode").val(data.payment_mode);
$("#description").val(data.description);
$("#reference").val(data.reference_no);

openModal();
}

function openModal(){
    document.getElementById("orderModal").style.display="flex";

    if($("#order_id").val()==""){
        $("#mobile").val("");
        $("#email").val("");
    }
}

function deleteOrder(id){
Swal.fire({
title:"Are you sure?",
icon:"warning",
showCancelButton:true
}).then((result)=>{
if(result.isConfirmed){
$.post("service_order.php",{delete_id:id},function(res){
if(res=="success"){
Swal.fire("Deleted!","","success");
setTimeout(()=>location.reload(),1000);
}
});
}
});
}
</script>

<?php if(isset($_GET['success'])){ ?>
<script>
Swal.fire({
title:"Success!",
text:"Service Order Saved Successfully!",
icon:"success",
timer:1500,
showConfirmButton:false
});
</script>
<?php } ?>

</body>
</html>