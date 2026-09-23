<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}

/* AUTO INACTIVATE EXPIRED */
mysqli_query($conn,"
    UPDATE subscriptions
    SET status='inactive'
    WHERE end_date IS NOT NULL
    AND end_date < CURDATE()
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Subscription Plans</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#eef2f7,#d9e4f5);
}

.container{
    margin:30px;
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 15px 40px rgba(0,0,0,0.08);
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h3{
    margin:0;
    font-size:22px;
    color:#0c1b33;
}

.add-btn{
    background:linear-gradient(45deg,#0c1b33,#1f3c88);
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:.3s;
}
.add-btn:hover{ transform:scale(1.05); }

table{
    width:100%;
    border-collapse:collapse;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.06);
}

th{
    background:#0c1b33;
    color:white;
    padding:14px;
    font-size:14px;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
    text-align:center;
    font-size:14px;
}

tr:hover{ background:#f5f8ff; }

.badge{
    padding:5px 12px;
    border-radius:20px;
    color:#fff;
    font-size:12px;
}

.active{ background:#28a745; }
.inactive{ background:#dc3545; }

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

.desc-cell{
    max-width:180px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
</style>
</head>

<body>

<div class="container">

<div class="header">
    <h3>📋 Subscription Management</h3>
    <button class="add-btn" onclick="openSubscription()">➕ Add Subscription</button>
</div>

<table>
<tr>
    <th>Sr</th>
    <th>Client</th>
    <th>Plan</th>
    <th>Start</th>
    <th>End</th>
    <th>Price</th>
    <th>Discount</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Unpaid</th>
    <th>Payment</th>
    <th>Description</th>
    <th>Reference</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php
$q = mysqli_query($conn,"
SELECT s.*, c.client_name
FROM subscriptions s
JOIN client c ON c.id = s.client_id
ORDER BY s.id DESC
");

$i=1;
$today=date('Y-m-d');

while($row=mysqli_fetch_assoc($q)){
    
$total  = $row['total'] ?? 0;
$paid   = $row['paid_amount'] ?? 0;
$unpaid = $total - $paid;

$status = (!empty($row['end_date']) && $row['end_date'] < $today)
    ? 'inactive'
    : ($row['status'] ?? 'inactive');
?>

<tr>
    
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($row['client_name'] ?? '') ?></td>
<td><?= ucfirst($row['plan_type'] ?? '') ?></td>
<td><?= $row['start_date'] ?? '' ?></td>
<td><?= $row['end_date'] ?? '' ?></td>

<td>₹<?= number_format($row['price'] ?? 0,2) ?></td>
<td>₹<?= number_format($row['discount'] ?? 0,2) ?></td>
<td><strong>₹<?= number_format($row['total'] ?? 0,2) ?></strong></td>

<td style="color:green;font-weight:600;">
₹<?= number_format($paid,2) ?>
</td>

<td style="color:<?= $unpaid > 0 ? '#dc3545' : '#28a745' ?>;font-weight:600;">
₹<?= number_format($unpaid,2) ?>
</td>

<td><?= htmlspecialchars($row['payment_mode'] ?? '') ?></td>

<td class="desc-cell" title="<?= htmlspecialchars($row['description'] ?? '') ?>">
<?= !empty($row['description'])
    ? htmlspecialchars(mb_strimwidth($row['description'],0,30,'...'))
    : '<span style="color:#999">—</span>'
?>
</td>
<td><?= htmlspecialchars($row['reference_no'] ?? '-') ?></td>
<td>
<span class="badge <?= $status ?>">
<?= ucfirst($status) ?>
</span>
</td>

<td>
<button onclick="editSubscription(<?= $row['id'] ?>)" class="btn edit-btn"><i class="fa fa-edit"></i></button>
<button onclick="deleteSubscription(<?= $row['id'] ?>)" class="btn delete-btn"><i class="fa fa-trash"></i></button>
</td>
</tr>

<?php } ?>
</table>

</div>
<script>

/* ================= ADD ================= */
function openSubscription(){

Swal.fire({
title:"Add Subscription",
width:500,
showCancelButton:true,
confirmButtonText:"Save",
confirmButtonColor:"#0c1b33",

html:`

<style>
.form-vertical{
display:flex;
flex-direction:column;
gap:10px;
text-align:left;
}

.form-vertical label{
font-size:13px;
font-weight:600;
color:#333;
}

.form-vertical input,
.form-vertical select,
.form-vertical textarea{
width:100%;
padding:8px;
border:1px solid #ccc;
border-radius:5px;
font-size:13px;
}

.form-vertical textarea{
height:70px;
resize:none;
}
</style>

<div class="form-vertical">

<label>Client</label>
<select id="client_id">
<option value="">Select Client</option>
<?php
$c=mysqli_query($conn,"SELECT id,client_name FROM client");
while($r=mysqli_fetch_assoc($c)){
echo "<option value='{$r['id']}'>{$r['client_name']}</option>";
}
?>
</select>

<label>Plan Type</label>
<select id="plan_type">
<option value="">Select Plan</option>
<option value="monthly">Monthly</option>
<option value="quarterly">Quarterly</option>
<option value="practical">Practical</option>
<option value="yearly">Yearly</option>
</select>

<label>Start Date</label>
<input type="date" id="start_date">

<label>End Date</label>
<input type="date" id="end_date">

<label>Price</label>
<input type="number" id="price" placeholder="Price">

<label>Discount</label>
<input type="number" id="discount" placeholder="Discount">

<label>Total</label>
<input type="number" id="total" placeholder="Total" readonly>

<label>Paid Amount</label>
<input type="number" id="paid_amount" value="0">

<label>Payment Mode</label>
<select id="payment_mode">
<option value="Cash">Cash</option>
<option value="Bank Transfer">Bank Transfer</option>
<option value="UPI">UPI</option>
<option value="Cheque">Cheque</option>
<option value="Card">Card</option>
</select>

<label>Payment Date</label>
<input type="date" id="payment_date">

<label>Description</label>
<textarea id="description" placeholder="Description"></textarea>

<label>Reference No</label>
<input type="text" id="reference_no" placeholder="Reference No (Optional)">

<label>Status</label>
<select id="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>

</div>
`,

didOpen:()=>{
$('#price,#discount').on('input',()=>{
let p=parseFloat($('#price').val())||0;
let d=parseFloat($('#discount').val())||0;
$('#total').val(p-d);
});
},

preConfirm:()=>{

let start = $('#start_date').val();
let end   = $('#end_date').val();

if(!$('#client_id').val() || !$('#plan_type').val() || !start){
Swal.showValidationMessage("Please fill required fields");
return false;
}

if(end && start > end){
Swal.showValidationMessage("Start date cannot be greater than End date");
return false;
}

return{
client_id:$('#client_id').val(),
plan_type:$('#plan_type').val(),
start_date:start,
end_date:end,
price:$('#price').val(),
discount:$('#discount').val(),
total:$('#total').val(),
paid_amount:$('#paid_amount').val(),
payment_mode:$('#payment_mode').val(),
payment_date:$('#payment_date').val(),
description:$('#description').val(),
reference_no:$('#reference_no').val(),
status:$('#status').val()
}

}

}).then(res=>{
if(res.isConfirmed){
$.post("save_subscription.php",res.value,function(r){
if(r==="SUCCESS") location.reload();
else Swal.fire("Error",r,"error");
});
}
});

}
/* ================= Edit ================= */
function editSubscription(id){

$.post("get_subscription.php",{id:id},function(res){

if(res.trim()==="NOT_FOUND"){
Swal.fire("Error","Data not found","error");
return;
}

let d = JSON.parse(res);

// normalize status
d.status = d.status ? d.status.toLowerCase() : 'inactive';

Swal.fire({
title:"Edit Subscription",
width:500,
showCancelButton:true,
confirmButtonText:"Update",
confirmButtonColor:"#0c1b33",

html:`

<style>
.form-vertical{
display:flex;
flex-direction:column;
gap:10px;
text-align:left;
}

.form-vertical label{
font-size:13px;
font-weight:600;
color:#333;
}

.form-vertical input,
.form-vertical select,
.form-vertical textarea{
width:100%;
padding:8px;
border:1px solid #ccc;
border-radius:5px;
font-size:13px;
}

.form-vertical textarea{
height:80px;
resize:none;
}
</style>

<div class="form-vertical">

<input type="hidden" id="id">

<label>Client</label>
<select id="client_id">
<?php
$c=mysqli_query($conn,"SELECT id,client_name FROM client");
while($r=mysqli_fetch_assoc($c)){
echo "<option value='{$r['id']}'>{$r['client_name']}</option>";
}
?>
</select>

<label>Plan Type</label>
<select id="plan_type">
<option value="monthly">Monthly</option>
<option value="quarterly">Quarterly</option>
<option value="practical">Practical</option>
<option value="yearly">Yearly</option>
</select>

<label>Start Date</label>
<input type="date" id="start_date">

<label>End Date</label>
<input type="date" id="end_date">

<label>Price</label>
<input type="number" id="price">

<label>Discount</label>
<input type="number" id="discount">

<label>Total</label>
<input type="number" id="total" readonly>

<label>Paid Amount</label>
<input type="number" id="paid_amount">

<label>Payment Mode</label>
<select id="payment_mode">
<option value="Cash">Cash</option>
<option value="Bank Transfer">Bank Transfer</option>
<option value="UPI">UPI</option>
<option value="Cheque">Cheque</option>
<option value="Card">Card</option>
</select>

<label>Payment Date</label>
<input type="date" id="payment_date">

<label>Description</label>
<textarea id="description" placeholder="Enter subscription details..."></textarea>

<label>Reference No</label>
<input type="text" id="reference_no">

<label>Status</label>
<select id="status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>

</div>
`,

didOpen:()=>{

$('#id').val(d.id);
$('#client_id').val(d.client_id);
$('#plan_type').val(d.plan_type);
$('#start_date').val(d.start_date);
$('#end_date').val(d.end_date);
$('#price').val(d.price);
$('#discount').val(d.discount);
$('#total').val(d.total);
$('#paid_amount').val(d.paid_amount);
$('#payment_mode').val(d.payment_mode);
$('#payment_date').val(d.payment_date);
$('#description').val(d.description);
$('#reference_no').val(d.reference_no);
$('#status').val(d.status);

// auto total
$('#price,#discount').on('input',()=>{
let p=parseFloat($('#price').val())||0;
let dis=parseFloat($('#discount').val())||0;
$('#total').val(p-dis);
});

},

preConfirm:()=>{
return{
id:$('#id').val(),
client_id:$('#client_id').val(),
plan_type:$('#plan_type').val(),
start_date:$('#start_date').val(),
end_date:$('#end_date').val(),
price:$('#price').val(),
discount:$('#discount').val(),
total:$('#total').val(),
paid_amount:$('#paid_amount').val(),
payment_mode:$('#payment_mode').val(),
payment_date:$('#payment_date').val(),
description:$('#description').val(),
reference_no:$('#reference_no').val(),
status:$('#status').val()
}
}

}).then(r=>{
if(r.isConfirmed){
$.post("update_subscription.php",r.value,function(x){
if(x.trim()==="UPDATED"){
Swal.fire("Updated","Subscription updated","success")
.then(()=>location.reload());
}else{
Swal.fire("Error",x,"error");
}
});
}
});

});

}
/* ================= DELETE ================= */
function deleteSubscription(id){
    Swal.fire({
        title: "Are you sure?",
        text: "This record will be deleted",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it"
    }).then((res) => {
        if (res.isConfirmed) {
            $.post("delete_subscription.php", { id: id }, function (r) {
                r = r.trim();
                if (r === "DELETED") {
                    Swal.fire("Deleted!", "Subscription removed", "success")
                        .then(() => location.reload());
                } else {
                    Swal.fire("Error", r, "error");
                }
            });
        }
    });
}

</script>


</body>
</html>