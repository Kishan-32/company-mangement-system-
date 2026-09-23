<?php
// sidebar.php
// Assumes session already started and $admin data is available
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* SIDEBAR */
.sidebar{
    width:220px;
    background:#0c1b33;
    color:white;
    height:100vh;
    padding:20px;
    box-sizing:border-box;
    overflow-y:auto;
}

.sidebar img{
    width:100%;
    border-radius:10px;
    margin-bottom:15px;
    background:#fff;
    padding:5px;
}

.sidebar h3{
    margin:10px 0 20px;
    font-size:16px;
    text-align:center;
    word-break:break-word;
}

body{
    margin:0;
    padding:0;
}

html, body{
    height:100%;
}

/* LINKS */
.sidebar a{
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:white;
    text-decoration:none;
    padding:10px 12px;
    border-radius:6px;
    margin-bottom:8px;
    transition:0.3s;
    cursor:pointer;
}

.sidebar a:hover{
    background:rgba(255,255,255,0.25);
}

/* DROPDOWN */
.dropdown{
    display:none;
    margin-left:10px;
}

.dropdown a{
    font-size:14px;
    padding-left:20px;
    background:rgba(255,255,255,0.08);
}

.arrow{
    font-size:12px;
    transition:0.3s;
}

.rotate{
    transform:rotate(180deg);
}

/* iframe styling */
#contentFrame{
    width:100%;
    height:100vh;
    border:none;
}
</style>

<div class="sidebar">

    <!-- LOGO -->
    <?php if(!empty($admin['login_logo'])): ?>
        <img src="uploads/logo/<?php echo htmlspecialchars($admin['login_logo']); ?>" alt="Logo">
    <?php endif; ?>

    <!-- ADMIN EMAIL -->
    <h3><?php echo htmlspecialchars($admin['email']); ?></h3>

    <!-- DASHBOARD -->
    <a onclick="loadDashboard()">🏠 Dashboard</a>

    <!-- ================= EXPENSES DROPDOWN ================= -->
    <a onclick="toggleExpenses()">
        💸 Expenses
        <span class="arrow" id="expenseArrow">▼</span>
    </a>

    <div class="dropdown" id="expenseMenu">
        <a onclick="loadExpenseCategory()">📂 Category</a>
        <a onclick="loadExpenseItem()">💰 Expense Item</a>
    </div>

    <!-- ================= SUBSCRIPTION DROPDOWN ================= -->
    <a onclick="toggleSubscription()">
        📦 Subscription
        <span class="arrow" id="subArrow">▼</span>
    </a>

    <div class="dropdown" id="subscriptionMenu">
        <a onclick="loadClient()">👥 Add Client</a>
        <a onclick="loadSubscriptionPlan()">🧾 Subscription Plan</a>
    </div> 

    <!-- ================= PURCHASE DROPDOWN ================= -->
    <a onclick="togglePurchase()">
        🛒 Purchase
        <span class="arrow" id="purchaseArrow">▼</span>
    </a>

    <div class="dropdown" id="purchaseMenu">
        <a onclick="loadCompany()">🏢 Company</a>
        <a onclick="loadPurchaseClient()">👤 Client</a>
    </div>

    <!-- ================= SERVICE DROPDOWN ================= -->
    <a onclick="toggleService()">
        🛠 Service
        <span class="arrow" id="serviceArrow">▼</span>
    </a>

    <div class="dropdown" id="serviceMenu">
        <a onclick="loadServiceOrder()">📦 Order</a>
    </div>

    <!-- ================= CASH & BANK DROPDOWN ================= -->
    <a onclick="toggleCashBank()">
             💳 Cash & Bank
        <span class="arrow" id="cashbankArrow">▼</span>
    </a>

    <div class="dropdown" id="cashbankMenu">
        <a onclick="loadDailyEarning()">📅 Earning</a>
    </div>
    <!-- ================= REPORT DROPDOWN ================= -->
    <a onclick="toggleReport()">
    📊 Report
        <span class="arrow" id="reportArrow">▼</span>
    </a>

    <div class="dropdown" id="reportMenu">
    <a onclick="loadClientProfit()">👤 Client Wise Profit</a>
    <a onclick="loadReferenceProfit()">🔗 Reference Wise Profit</a>
    </div>

    <!-- PROFILE -->
    <a onclick="loadProfile()">👤 Update Profile</a>

    <!-- LOGOUT -->
    <a onclick="confirmLogout()">🚪 Logout</a>

</div>
<script>

/* ================= TOGGLE FUNCTIONS ================= */

function toggleExpenses(){
    toggleMenu("expenseMenu", "expenseArrow");
}

function toggleSubscription(){
    toggleMenu("subscriptionMenu", "subArrow");
}

function togglePurchase(){
    toggleMenu("purchaseMenu", "purchaseArrow");
}

function toggleService(){
    toggleMenu("serviceMenu", "serviceArrow");
}

function toggleMenu(menuId, arrowId){
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);

    menu.style.display = (menu.style.display === "block") ? "none" : "block";
    arrow.classList.toggle("rotate");
}

/* ================= LOAD PAGES ================= */

function loadDashboard(){
    window.location.href = "dashboard.php";
}

function loadClient(){
    loadPage("client.php");
}

function loadSubscriptionPlan(){
    loadPage("subscription_plan.php");
}

function loadExpenseCategory(){
    loadPage("category.php");
}

function loadExpenseItem(){
    loadPage("expense_item.php");
}

function loadCompany(){
    loadPage("company.php");
}

function loadPurchaseClient(){
    loadPage("purchase_client.php");
}

function loadServiceOrder(){
    loadPage("service_order.php");
}

function loadProfile(){
    loadPage("profile_content.php");
}

function loadPage(page){
    const frame = document.getElementById("contentFrame");

    if(frame){
        frame.src = page;
    }else{
        document.getElementById('mainContent').innerHTML =
        `<iframe id="contentFrame" src="${page}"></iframe>`;
    }
}

function toggleCashBank(){
    toggleMenu("cashbankMenu", "cashbankArrow");
}

function loadDailyEarning(){
    loadPage("earning.php");  // change file name if different
}

function toggleReport(){
    toggleMenu("reportMenu", "reportArrow");
}

function loadClientProfit(){
    loadPage("client_profit.php");
}

function loadReferenceProfit(){
    loadPage("reference_profit.php");
}   

/* ================= LOGOUT CONFIRM ================= */

function confirmLogout(){
    Swal.fire({
        title: "Are you sure?",
        text: "You will be logged out",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#6C63FF",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Logout"
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Logged out!",
                text: "Logout successful",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "logout.php";
            });
        }
    });
}

</script>