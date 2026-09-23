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
<title>Client Purchases</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#e3f2fd,#f4f6f9);
    margin:0;
    padding:30px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h2{
    color:#0c1b33;
}

button{
    padding:10px 18px;
    background: linear-gradient(45deg,#0c1b33,#1e3c72);
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
}

button:hover{
    transform:scale(1.05);
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}

table th, table td{
    padding:12px;
    border-bottom:1px solid #eee;
    text-align:center;
}

table th{
    background:#0c1b33;
    color:white;
}

tr:hover{
    background:#f1f9ff;
}

.active{ color:green; font-weight:bold; }
.expired{ color:red; font-weight:bold; }

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

.action-wrapper{
    display:flex;
    justify-content:center;
    gap:6px;
}

</style>
</head>
<body>

<div class="header">
    <h2>Client Purchases</h2>
    <button onclick="addPurchase()">➕ Add Purchase</button>
</div>

<table>
<thead>
<tr>
    <th>Sr No</th>
    <th>Client Name</th>
    <th>Email</th>
    <th>Asset Name</th>
    <th>Purchase Date</th>
    <th>Expiry Date</th>
    <th>Price</th>
    <th>Payment Mode</th> 
    <th>Platform</th>
    <th>Status</th>
    <th>Description</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
$sql = "
    SELECT cp.*, c.client_name, c.email 
    FROM client_purchases cp
    JOIN client c ON cp.client_id = c.id
    ORDER BY cp.purchase_date ASC
";

$q = mysqli_query($conn, $sql);
if(!$q){
    die("Query Failed: " . mysqli_error($conn));
}

$i=1;
while($row=mysqli_fetch_assoc($q)){

    $today = date("Y-m-d");
    $status = ($row['expiry_date'] >= $today) ? "Active" : "Expired";
    $statusClass = ($status=="Active") ? "active" : "expired";
?>
<tr>
<td><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['client_name']); ?></td>
<td><?php echo htmlspecialchars($row['email']); ?></td>
<td><b><?php echo htmlspecialchars($row['asset_name']); ?></b></td>
<td><?php echo date("d-m-Y",strtotime($row['purchase_date'])); ?></td>
<td><?php echo date("d-m-Y",strtotime($row['expiry_date'])); ?></td>
<td>₹ <?php echo $row['price']; ?></td>
<td><?php echo htmlspecialchars($row['payment_mode']); ?></td>
<td><?php echo htmlspecialchars($row['asset_platform']); ?></td>
<td class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
<td><?php echo htmlspecialchars($row['description']); ?></td>
<td>
    <div class="action-wrapper">
        <button class="btn edit-btn"
            onclick='editPurchase(<?php echo json_encode($row); ?>)'>
            <i class="fa fa-edit"></i>
        </button>

        <button class="btn delete-btn"
            onclick="deletePurchase(<?php echo $row['id']; ?>)">
            <i class="fa fa-trash"></i>
        </button>
    </div>
</td>
</td>
</tr>
<?php } ?>
</tbody>
</table>

<script>

function addPurchase(){

$.get("get_clients.php", function(clientOptions){

Swal.fire({
title:"<strong style='color:#0c1b33'>Add Client Purchase</strong>",
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

<label>Client</label>
<select id="client_id">
${clientOptions}
</select>

<label>Asset Name</label>
<input type="text" id="asset_name" placeholder="Asset Name">

<label>Purchase Date</label>
<input type="date" id="purchase_date">

<label>Expiry Date</label>
<input type="date" id="expiry_date">

<label>Price</label>
<input type="number" id="price" placeholder="Price">

<label>Payment Mode</label>
<select id="payment_mode">
<option value="Cash">Cash</option>
<option value="Bank Transfer">Bank Transfer</option>
<option value="UPI">UPI</option>
<option value="Cheque">Cheque</option>
<option value="Card">Card</option>
</select>

<label>Asset Platform</label>
<input type="text" id="asset_platform" placeholder="Asset Platform">

<label>Description</label>
<textarea id="description" placeholder="Description"></textarea>

</div>
`,

showCancelButton:true,
confirmButtonText:"Save",
confirmButtonColor:"#0c1b33",

preConfirm:()=>{

let purchase = $('#purchase_date').val();
let expiry = $('#expiry_date').val();

if(!$('#asset_name').val()){
Swal.showValidationMessage("Asset Name Required");
return false;
}

if(!purchase || !expiry){
Swal.showValidationMessage("Select both dates");
return false;
}

if(expiry <= purchase){
Swal.showValidationMessage("Expiry must be after purchase date");
return false;
}

return{
client_id:$('#client_id').val(),
asset_name:$('#asset_name').val(),
purchase_date:purchase,
expiry_date:expiry,
price:$('#price').val(),
payment_mode:$('#payment_mode').val(),
asset_platform:$('#asset_platform').val(),
description:$('#description').val()
}

}

}).then((result)=>{
if(result.isConfirmed){

$.post("save_client_purchase.php",result.value,function(res){

if(res.trim()==="success"){
Swal.fire("Saved","Purchase Added Successfully","success")
.then(()=>location.reload());
}else{
Swal.fire("Error",res,"error");
}

});

}
});

});
}



function editPurchase(data){

$.get("get_clients.php", function(clientOptions){

Swal.fire({
title:"<strong style='color:#0c1b33'>Edit Client Purchase</strong>",
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

<input type="hidden" id="edit_id" value="${data.id}">

<label>Client</label>
<select id="client_id">
${clientOptions}
</select>

<label>Asset Name</label>
<input type="text" id="asset_name" value="${data.asset_name}">

<label>Purchase Date</label>
<input type="date" id="purchase_date" value="${data.purchase_date}">

<label>Expiry Date</label>
<input type="date" id="expiry_date" value="${data.expiry_date}">

<label>Price</label>
<input type="number" id="price" value="${data.price}">

<label>Payment Mode</label>
<select id="payment_mode">
<option value="Cash">Cash</option>
<option value="Bank Transfer">Bank Transfer</option>
<option value="UPI">UPI</option>
<option value="Cheque">Cheque</option>
<option value="Card">Card</option>
</select>

<label>Asset Platform</label>
<input type="text" id="asset_platform" value="${data.asset_platform}">

<label>Description</label>
<textarea id="description">${data.description}</textarea>

</div>
`,

showCancelButton:true,
confirmButtonText:"Update",
confirmButtonColor:"#28a745",

didOpen:()=>{
$('#client_id').val(data.client_id);
$('#payment_mode').val(data.payment_mode);
},

preConfirm:()=>{

let purchase = $('#purchase_date').val();
let expiry = $('#expiry_date').val();

if(!$('#asset_name').val()){
Swal.showValidationMessage("Asset Name Required");
return false;
}

if(!purchase || !expiry){
Swal.showValidationMessage("Select both dates");
return false;
}

if(expiry <= purchase){
Swal.showValidationMessage("Expiry must be after purchase date");
return false;
}

return{
id:$('#edit_id').val(),
client_id:$('#client_id').val(),
asset_name:$('#asset_name').val(),
purchase_date:purchase,
expiry_date:expiry,
price:$('#price').val(),
payment_mode:$('#payment_mode').val(),
asset_platform:$('#asset_platform').val(),
description:$('#description').val()
}

}

}).then((result)=>{
if(result.isConfirmed){

$.post("save_client_purchase.php",result.value,function(res){

if(res.trim()==="success"){
Swal.fire("Updated","Purchase Updated Successfully","success")
.then(()=>location.reload());
}else{
Swal.fire("Error",res,"error");
}

});

}
});

});
}

function deletePurchase(id){
    Swal.fire({
        title:"Are you sure?",
        text:"This record will be deleted!",
        icon:"warning",
        showCancelButton:true,
        confirmButtonColor:"#d33",
        confirmButtonText:"Yes, Delete"
    }).then((result)=>{
        if(result.isConfirmed){
            $.post("delete_client_purchase.php",{id:id},function(res){
                if(res=="success"){
                    Swal.fire("Deleted!","Record removed","success")
                    .then(()=>location.reload());
                }else{
                    Swal.fire("Error",res,"error");
                }
            });
        }
    });
}

</script>

</body>
</html>
