<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.top.location="index.php";</script>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Client Subscription Management</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f4f6f9;
}
.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 30px;
    background:#ffffff;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.page-header h2{
    margin:0;
    font-size:22px;
    color:#0c1b33;
}
.add-btn{
    background:linear-gradient(135deg,#0c1b33,#122a52);
    color:#fff;
    border:none;
    padding:10px 20px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
}
.page-content{ padding:30px; }
.card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.06);
    overflow:hidden;
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
    padding:14px 12px;
    text-align:left;
    font-size:14px;
}
tbody tr{
    border-bottom:1px solid #eee;
}
.badge{
    padding:5px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    color:#fff;
}
.badge.active{ background:#28a745; }
.badge.inactive{ background:#dc3545; }
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
.swal2-popup input, .swal2-popup select{
    width:100%;
    padding:10px;
    margin-top:6px;
    border:1px solid #dcdcdc;
    border-radius:6px;
}
.form-row{ margin-bottom:12px; }
</style>
</head>

<body>

<div class="page-header">
    <h2>👥 Client Subscription Management</h2>
    <button class="add-btn" onclick="openAddClient()">➕ Add Client</button>
</div>

<div class="page-content">
<div class="card">
<table>
<thead>
<tr>
    <th>Sr.no</th>
    <th>Name</th>
    <th>Mobile</th>
    <th>Service</th>
    <th>Price</th>
    <th>Discount</th>
    <th>Final Price</th>
    <th>Paid</th>
    <th>Unpaid</th>
    <th>Purchase Date</th>
    <th>Payment</th>
    <th>Reference</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
$q = mysqli_query($conn,"
SELECT client_subscription.*, 
       client.client_name, 
       client.mobile_number,
       client.email
FROM client_subscription
INNER JOIN client ON client_subscription.client_id = client.id
ORDER BY client_subscription.id ASC
");

$sr=1;
while($row=mysqli_fetch_assoc($q)){
?>
<tr>
<td><?= $sr ?></td>
<td><?= htmlspecialchars($row['client_name']) ?></td>
<td><?= htmlspecialchars($row['mobile_number']) ?></td>
<td><?= htmlspecialchars($row['service_type']) ?></td>
<td>₹<?= number_format($row['price'],2) ?></td>
<td>₹<?= number_format($row['discount_price'],2) ?></td>
<td><strong>₹<?= number_format($row['final_price'],2) ?></strong></td>
<td>₹<?= number_format($row['paid_amount'],2) ?></td>
<td>₹<?= number_format($row['unpaid_amount'],2) ?></td>
<td><?= htmlspecialchars($row['purchase_date']) ?></td>
<td><?= htmlspecialchars($row['payment_mode']) ?></td>
<td><?= htmlspecialchars($row['reference']) ?></td>
<td>
<span class="badge <?= $row['status']=='active'?'active':'inactive' ?>">
<?= ucfirst($row['status']) ?>
</span>
</td>
<td>
<button class="btn edit-btn" onclick='editClient(<?= json_encode($row) ?>)'><i class="fa fa-edit"></i></button>
<button class="btn delete-btn" onclick="deleteClient(<?= $row['id'] ?>)"><i class="fa fa-trash"></i></button>
</td>
</tr>
<?php $sr++; } ?>

</tbody>
</table>
</div>
</div>

<script>
function calculateAmounts(){
let price = parseFloat($('#price').val()) || 0;
let discount = parseFloat($('#discount_price').val()) || 0;
let paid = parseFloat($('#paid_amount').val()) || 0;

let finalPrice = price - discount;
let unpaid = finalPrice - paid;

$('#final_price').val(finalPrice);
$('#unpaid_amount').val(unpaid >= 0 ? unpaid : 0);
}

function openAddClient(){
Swal.fire({
title:"Add Client",
width:600,
showCancelButton:true,
confirmButtonText:"Save",
html:`
<div class="form-row"><input id="client_name" placeholder="Client Name"></div>
<div class="form-row"><input id="mobile" placeholder="Mobile Number"></div>
<div class="form-row"><input id="email" type="email" placeholder="Email Address"></div>
<div class="form-row"><input id="service_type" placeholder="Service Type"></div>
<div class="form-row"><input id="price" type="number" placeholder="Price"></div>
<div class="form-row"><input id="discount_price" type="number" placeholder="Discount"></div>
<div class="form-row"><input id="final_price" type="number" placeholder="Final Price" readonly></div>
<div class="form-row"><input id="paid_amount" type="number" placeholder="Paid Amount"></div>
<div class="form-row"><input id="unpaid_amount" type="number" placeholder="Unpaid Amount" readonly></div>
<div class="form-row"><input id="purchase_date" type="date"></div>
<div class="form-row">
<select id="payment_mode">
<option>Cash</option>
<option>Bank Transfer</option>
<option>UPI</option>
<option>Cheque</option>
<option>Card</option>
</select>
</div>
<div class="form-row"><input id="reference" placeholder="Reference"></div>
<div class="form-row">
<select id="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>
</div>
`,
didOpen:()=>{
$('#price,#discount_price,#paid_amount').on('input', calculateAmounts);
}
}).then(res=>{
if(res.isConfirmed){

let name = $('#client_name').val().trim();
let mobile = $('#mobile').val().trim();
let email = $('#email').val().trim();
let service = $('#service_type').val().trim();
let price = parseFloat($('#price').val()) || 0;
let discount = parseFloat($('#discount_price').val()) || 0;
let finalPrice = parseFloat($('#final_price').val()) || 0;
let paid = parseFloat($('#paid_amount').val()) || 0;
let unpaid = parseFloat($('#unpaid_amount').val()) || 0;
let date = $('#purchase_date').val();
let payment = $('#payment_mode').val();

// ✅ Name validation
if(name === ""){
    Swal.showValidationMessage("Client name is required");
    return false;
}

// ✅ Mobile validation (10 digit)
if(!/^[0-9]{10}$/.test(mobile)){
    Swal.showValidationMessage("Enter valid 10-digit mobile number");
    return false;
}

// ✅ Email validation
let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
if(!emailPattern.test(email)){
    Swal.showValidationMessage("Enter valid email address");
    return false;
}

// ✅ Service validation
if(service === ""){
    Swal.showValidationMessage("Service type is required");
    return false;
}

// ✅ Price validation
if(price <= 0){
    Swal.showValidationMessage("Price must be greater than 0");
    return false;
}

// ✅ Discount validation
if(discount < 0){
    Swal.showValidationMessage("Discount cannot be negative");
    return false;
}

// ✅ Paid validation
if(paid < 0){
    Swal.showValidationMessage("Paid amount cannot be negative");
    return false;
}

// ✅ Paid > Final Price
if(paid > finalPrice){
    Swal.showValidationMessage("Paid amount cannot exceed final price");
    return false;
}

// ✅ Date validation
if(date === ""){
    Swal.showValidationMessage("Purchase date is required");
    return false;
}

// ✅ If all valid → send data
$.post("save_client.php",{
    client_name:name,
    mobile:mobile,
    email:email,
    service_type:service,
    price:price,
    discount_price:discount,
    final_price:finalPrice,
    paid_amount:paid,
    unpaid_amount:unpaid,
    purchase_date:date,
    payment_mode:payment,
    reference:$('#reference').val(),
    status:$('#status').val()
},
function(r){
    console.log("SERVER RESPONSE:", r);

    if(r=="SUCCESS"){
        Swal.fire("Saved","Client Added","success")
        .then(()=>location.reload());
    }else{
        Swal.fire("Error", r, "error");
    }
});
}
});
}

function editClient(data){
Swal.fire({
title:"Update Client",
width:600,
showCancelButton:true,
confirmButtonText:"Update",
html:`
<input type="hidden" id="id" value="${data.id}">
<div class="form-row"><input id="client_name" value="${data.client_name}"></div>
<div class="form-row"><input id="mobile" value="${data.mobile_number}"></div>
<div class="form-row"><input id="email" type="email" value="${data.email}"></div>
<div class="form-row"><input id="service_type" value="${data.service_type}"></div>
<div class="form-row"><input id="price" type="number" value="${data.price}"></div>
<div class="form-row"><input id="discount_price" type="number" value="${data.discount_price}"></div>
<div class="form-row"><input id="final_price" type="number" value="${data.final_price}" readonly></div>
<div class="form-row"><input id="paid_amount" type="number" value="${data.paid_amount}"></div>
<div class="form-row"><input id="unpaid_amount" type="number" value="${data.unpaid_amount}" readonly></div>
<div class="form-row"><input id="purchase_date" type="date" value="${data.purchase_date}"></div>
<div class="form-row">
<select id="payment_mode">
<option ${data.payment_mode=='Cash'?'selected':''}>Cash</option>
<option ${data.payment_mode=='Bank Transfer'?'selected':''}>Bank Transfer</option>
<option ${data.payment_mode=='UPI'?'selected':''}>UPI</option>
<option ${data.payment_mode=='Cheque'?'selected':''}>Cheque</option>
<option ${data.payment_mode=='Card'?'selected':''}>Card</option>
</select>
</div>
<div class="form-row"><input id="reference" value="${data.reference}"></div>
<div class="form-row">
<select id="status">
<option value="active" ${data.status=='active'?'selected':''}>Active</option>
<option value="inactive" ${data.status=='inactive'?'selected':''}>Inactive</option>
</select>
</div>
`,
didOpen:()=>{
$('#price,#discount_price,#paid_amount').on('input', calculateAmounts);
}
}).then(res=>{
if(res.isConfirmed){
$.post("update_client.php",{
id:$('#id').val(),
client_name:$('#client_name').val(),
mobile:$('#mobile').val(),
email:$('#email').val(),
service_type:$('#service_type').val(),
price:$('#price').val(),
discount_price:$('#discount_price').val(),
final_price:$('#final_price').val(),
paid_amount:$('#paid_amount').val(),
unpaid_amount:$('#unpaid_amount').val(),
purchase_date:$('#purchase_date').val(),
payment_mode:$('#payment_mode').val(),
reference:$('#reference').val(),
status:$('#status').val()
},function(r){
if(r=="UPDATED"){
Swal.fire("Updated","Client Updated","success").then(()=>location.reload());
}else{
Swal.fire("Error","Update Failed","error");
}
});
}
});
}

function deleteClient(id){
Swal.fire({
    title:"Are you sure?",
    icon:"warning",
    showCancelButton:true,
    confirmButtonColor:"#dc3545"
}).then(res=>{
    if(res.isConfirmed){
        $.post("delete_client.php",{id:id},function(r){

            console.log("DELETE RESPONSE:", r); // 👈 DEBUG

            if(r.trim() == "DELETED"){
                Swal.fire("Deleted","Client Removed","success")
                .then(()=>location.reload());
            }else{
                Swal.fire("Error", r, "error"); // 👈 SHOW REAL ERROR
            }

        });
    }
});
}
</script>

</body>
</html>