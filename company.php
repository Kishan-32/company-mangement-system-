<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("UNAUTHORIZED");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Company Assets</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#eef2f7,#d9e4f5);
    margin:0;
    padding:30px;
}

/* CARD CONTAINER */
.card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.header h2{
    margin:0;
    color:#0c1b33;
}

button{
    padding:10px 16px;
    background:linear-gradient(45deg,#0c1b33,#1f3c88);
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:0.3s;
}

button:hover{
    transform:scale(1.05);
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    overflow:hidden;
    border-radius:10px;
}

table th{
    background:#0c1b33;
    color:white;
    padding:12px;
}

table td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}

table tr:hover{
    background:#f5f8ff;
}

/* STATUS */
.active{
    color:#28a745;
    font-weight:bold;
}

.expired{
    color:#dc3545;
    font-weight:bold;
}

/* ACTION BUTTONS */
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

<div class="card">

<div class="header">
    <h2>💼 Company Assets Management</h2>
    <button onclick="addAsset()">➕ Add Asset</button>
</div>

<table>
<thead>
<tr>
    <th>Sr</th>
    <th>Asset</th>
    <th>Purchase</th>
    <th>Expiry</th>
    <th>Price</th>
    <th>Payment</th>
    <th>Platform</th>
    <th>Status</th>
    <th>Description</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
$q = mysqli_query($conn,"SELECT * FROM company_assets ORDER BY purchase_date ASC");
$i=1;
while($row=mysqli_fetch_assoc($q)){

    $today = date("Y-m-d");
    $status = ($row['expiry_date'] >= $today) ? "Active" : "Expired";
    $statusClass = ($status == "Active") ? "active" : "expired";
?>
<tr>
<td><?= $i++; ?></td>
<td><?= htmlspecialchars($row['asset_name']); ?></td>
<td><?= date("d-m-Y", strtotime($row['purchase_date'])); ?></td>
<td><?= date("d-m-Y", strtotime($row['expiry_date'])); ?></td>
<td>₹<?= number_format($row['price'],2); ?></td>
<td><?= htmlspecialchars($row['payment_method']); ?></td>
<td><?= htmlspecialchars($row['asset_platform']); ?></td>
<td class="<?= $statusClass; ?>"><?= $status; ?></td>
<td><?= htmlspecialchars($row['description']); ?></td>
<td>
<button class="btn edit-btn"
onclick="editAsset(
<?= $row['id']; ?>,
'<?= addslashes($row['asset_name']); ?>',
'<?= $row['purchase_date']; ?>',
'<?= $row['expiry_date']; ?>',
'<?= $row['price']; ?>',
'<?= $row['payment_method']; ?>',
'<?= addslashes($row['asset_platform']); ?>',
'<?= addslashes($row['description']); ?>'
)"><i class="fa fa-edit"></i></button>

<button class="btn delete-btn"
onclick="deleteAsset(<?= $row['id']; ?>)"><i class="fa fa-trash"></i></button>
</td>
</tr>
<?php } ?>
</tbody>
</table>

</div>

<script>

function addAsset(){
    assetForm("Add Company Asset","save_asset.php");
}

function editAsset(id,name,purchase,expiry,price,payment,platform,desc){
    assetForm("Edit Company Asset","update_asset.php",
        id,name,purchase,expiry,price,payment,platform,desc);
}

function assetForm(title,url,id="",name="",purchase="",expiry="",price="",payment="",platform="",desc=""){

Swal.fire({
title:title,
width:500,

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

<input type="hidden" id="id" value="${id}">

<label>Asset Name</label>
<input type="text" id="asset_name" value="${name}" placeholder="Asset Name">

<label>Purchase Date</label>
<input type="date" id="purchase_date" value="${purchase}">

<label>Expiry Date</label>
<input type="date" id="expiry_date" value="${expiry}">

<label>Price</label>
<input type="number" id="price" value="${price}" placeholder="Price">

<label>Payment Method</label>
<select id="payment_method">
<option value="Cash" ${payment=="Cash"?"selected":""}>Cash</option>
<option value="Bank Transfer" ${payment=="Bank Transfer"?"selected":""}>Bank Transfer</option>
<option value="UPI" ${payment=="UPI"?"selected":""}>UPI</option>
<option value="Cheque" ${payment=="Cheque"?"selected":""}>Cheque</option>
<option value="Card" ${payment=="Card"?"selected":""}>Card</option>
</select>

<label>Platform</label>
<input type="text" id="asset_platform" value="${platform}" placeholder="Platform">

<label>Description</label>
<textarea id="description" placeholder="Description">${desc}</textarea>

</div>
`,

showCancelButton:true,
confirmButtonText:"Save",

preConfirm:()=>{
return{
id:$('#id').val(),
asset_name:$('#asset_name').val(),
purchase_date:$('#purchase_date').val(),
expiry_date:$('#expiry_date').val(),
price:$('#price').val(),
payment_method:$('#payment_method').val(),
asset_platform:$('#asset_platform').val(),
description:$('#description').val()
}
}

}).then(res=>{
if(res.isConfirmed){
$.post(url,res.value,function(r){
if(r==="SUCCESS" || r==="UPDATED"){
location.reload();
}else{
Swal.fire("Error",r,"error");
}
});
}
});

}

function deleteAsset(id){
Swal.fire({
title:"Are you sure?",
icon:"warning",
showCancelButton:true
}).then((result)=>{
if(result.isConfirmed){
$.post("delete_asset.php",{id:id},function(res){
if(res=="success"){
Swal.fire("Deleted","Asset Removed","success")
.then(()=>location.reload());
}
});
}
});
}
</script>

</body>
</html>