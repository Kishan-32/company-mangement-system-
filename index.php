<?php
session_start();
include 'db.php';

/* FETCH ADMIN LOGO */
$logo = "";
$logoQuery = mysqli_query($conn, "SELECT login_logo FROM admin LIMIT 1");
if ($row = mysqli_fetch_assoc($logoQuery)) {
    $logo = $row['login_logo'];
}

/* LOGIN PROCESS */
if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email == "" || $password == "") {
        $error = "Email and Password are required!";
    } else {

        $query = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");
        $admin = mysqli_fetch_assoc($query);

        if ($admin && password_verify($password, $admin['password'])) {

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['login_success']=true ;
            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>

<style>
body{
    background: linear-gradient(135deg, #0c1b33, #0c1b33);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Arial,sans-serif;
}

.login-box{
    background:#fff;
    padding:40px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    width:350px;
    text-align:center;
}

.logo-box{
    width:100px;
    height:100px;
    margin:0 auto 20px;
    border-radius:50%;
    overflow:hidden;
    background:#f1f1f1;
    display:flex;
    justify-content:center;
    align-items:center;
}
.logo-box img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.login-box h2{
    margin-bottom:20px;
    color:#333;
}

.login-box input{
    width:100%;
    padding:12px 15px;
    margin-bottom:6px;
    border:1px solid #ccc;
    border-radius:8px;
}

.login-box input:focus{
    border-color:#6C63FF;
    outline:none;
}

button{
    width:100%;
    padding:12px;
    background:#6C63FF;
    color:white;
    font-size:16px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-top:10px;
}
button:hover{
    background:#5548c8;
}

.error-msg{
    color:red;
    font-size:13px;
    margin-bottom:8px;
    text-align:left;
}

input.error{
    border:1px solid red;
}

</style>
</head>

<body>

<div class="login-box">

    <!-- LOGO -->
    <?php if($logo != ""){ ?>
        <div class="logo-box">
            <img src="uploads/logo/<?php echo $logo; ?>">
        </div>
    <?php } ?>

    <h2>Admin Login</h2>

    <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>

    <form method="post" onsubmit="return validateForm()">

        <!-- EMAIL -->
        <input type="text"
               name="email"
               id="email"
               placeholder="Email"
               onkeyup="validateEmail()">
        <div id="emailError" class="error-msg"></div>

        <!-- PASSWORD WITH EYE -->
        <div style="position:relative;">
            <input type="password"
                   name="password"
                   id="password"
                   placeholder="Password"
                   onkeyup="validatePassword()" >

        </div>
        <div id="passwordError" class="error-msg"></div>

        <button type="submit" name="login">Login</button>
    </form>

</div>

<script>
function validateEmail(){
    let email = document.getElementById("email");
    let error = document.getElementById("emailError");
    let pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    error.innerHTML = "";
    email.classList.remove("error");

    if(email.value === ""){
        error.innerHTML = "Email is required";
        email.classList.add("error");
        return false;
    }
    if(!pattern.test(email.value)){
        error.innerHTML = "Enter a valid email address";
        email.classList.add("error");
        return false;
    }
    return true;
}

function validatePassword(){
    let pass = document.getElementById("password");
    let error = document.getElementById("passwordError");

    error.innerHTML = "";
    pass.classList.remove("error");

    if(pass.value === ""){
        error.innerHTML = "Password is required";
        pass.classList.add("error");
        return false;
    }
    if(pass.value.length < 6){
        error.innerHTML = "Minimum 6 characters required";
        pass.classList.add("error");
        return false;
    }
    return true;
}

function validateForm(){
    return validateEmail() && validatePassword();
}

</script>

</body>
</html>
