<?php
session_start();
include 'db.php';


/* AJAX CHECK */
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

/* SESSION CHECK */
if (!isset($_SESSION['admin_id'])) {
    if ($isAjax) {
        exit("SESSION_EXPIRED");
    } else {
        echo '<script>window.top.location="index.php";</script>';
        exit;
    }
}

$id = $_SESSION['admin_id'];

/* FETCH ADMIN */
$admin = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM admin WHERE id='$id'")
);

/* ================= HANDLE AJAX ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- UPDATE LOGO ---------- */
    if ($_POST['action'] === 'update_logo') {

        if (empty($_FILES['logo']['name'])) exit("NO_FILE");

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext,$allowed)) exit("INVALID_FORMAT");
        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) exit("FILE_TOO_LARGE");

        $dir = "uploads/logo/";
        if (!is_dir($dir)) mkdir($dir,0777,true);

        $logo = "logo_".$id."_".time().".".$ext;

        if (!move_uploaded_file($_FILES['logo']['tmp_name'],$dir.$logo))
            exit("UPLOAD_FAILED");

        mysqli_query($conn,
            "UPDATE admin SET login_logo='$logo' WHERE id='$id'"
        );

        session_destroy();
        exit("LOGOUT");
    }

    /* ---------- UPDATE EMAIL ---------- */
    if ($_POST['action'] === 'update_email') {

        if ($_POST['old_email'] !== $admin['email'])
            exit("Old email does not match");

        if (!password_verify($_POST['password'],$admin['password']))
            exit("Wrong password");

        if (!filter_var($_POST['new_email'],FILTER_VALIDATE_EMAIL))
            exit("Invalid email format");

        mysqli_query($conn,
            "UPDATE admin SET email='".$_POST['new_email']."' WHERE id='$id'"
        );

        session_destroy();
        exit("LOGOUT");
    }

    /* ---------- UPDATE PASSWORD ---------- */
    if ($_POST['action'] === 'update_password') {

        if ($_POST['old_email'] !== $admin['email'])
            exit("Old email does not match");

        if (!password_verify($_POST['password'],$admin['password']))
            exit("Wrong password");

        if ($_POST['new_password'] !== $_POST['confirm_password'])
            exit("Passwords do not match");

        if (strlen($_POST['new_password']) < 6)
            exit("Password must be at least 6 characters");

        $hash = password_hash($_POST['new_password'],PASSWORD_DEFAULT);

        mysqli_query($conn,
            "UPDATE admin SET password='$hash' WHERE id='$id'"
        );

        session_destroy();
        exit("LOGOUT");
    }

    exit("INVALID_REQUEST");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Profile</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body{font-family:Arial;background:#f4f6fb;padding:30px;}
.box{background:#fff;padding:20px;border-radius:8px;margin-bottom:25px;}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
label{font-weight:bold;display:block;margin-top:10px;}
input{width:100%;padding:8px;margin-top:5px;border:1px solid #ccc;border-radius:5px;}
input.error{border-color:red;}
button{margin-top:15px;padding:10px 18px;background:#6C63FF;color:#fff;border:none;border-radius:5px;cursor:pointer;}
.logo{width:140px;height:90px;object-fit:contain;border:1px solid #ddd;padding:6px;}
.error-text{color:red;font-size:13px;}
</style>
</head>

<body>

<!-- LOGO -->
<div class="box">
<h3>Login Page Logo</h3>

<img src="uploads/logo/<?php echo $admin['login_logo']; ?>" class="logo">

<form id="logoForm" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_logo">
    <input type="file" name="logo" required>
    <button>Update Logo</button>
</form>
</div>

<!-- DETAILS -->
<div class="box">
<h3>Update Login Details</h3>

<div class="grid">

<form class="ajaxForm">
    <input type="hidden" name="action" value="update_email">

    <label>Old Email</label>
    <input type="email" name="old_email">
    <div class="error-text"></div>

    <label>Password</label>
    <input type="password" name="password">
    <div class="error-text"></div>

    <label>New Email</label>
    <input type="email" name="new_email">
    <div class="error-text"></div>

    <button>Update Email</button>
</form>

<form class="ajaxForm">
    <input type="hidden" name="action" value="update_password">

    <label>Old Email</label>
    <input type="email" name="old_email">
    <div class="error-text"></div>

    <label>Current Password</label>
    <input type="password" name="password">
    <div class="error-text"></div>

    <label>New Password</label>
    <input type="password" name="new_password">
    <div class="error-text"></div>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password">
    <div class="error-text"></div>

    <button>Update Password</button>
</form>

</div>
</div>

<script>
/* ================= SWEETALERT HELPERS ================= */
function swalConfirm(msg, cb){
    Swal.fire({
        title: "Are you sure?",
        text: msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#6C63FF",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, continue",
        cancelButtonText: "Cancel"
    }).then(r=>{
        if(r.isConfirmed) cb();
    });
}

function swalSessionExpired(){
    Swal.fire({
        icon:"warning",
        title:"Session Expired",
        text:"Please login again"
    }).then(()=>{
        window.top.location="index.php";
    });
}
/* ================= RUNTIME VALIDATION ================= */

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Validate input field
 * @param {HTMLElement} input
 * @param {boolean} condition
 * @param {string} message
 * @returns {boolean}
 */
function validateInput(input, condition, message) {
    const errorBox = input.nextElementSibling;

    if (!condition) {
        input.classList.add("error");
        errorBox.innerText = message;
        return false;
    }

    input.classList.remove("error");
    errorBox.innerText = "";
    return true;
}

/* Attach runtime validation */
document.querySelectorAll("input").forEach(input => {

    input.addEventListener("input", () => {

        /* Email validation */
        if (input.type === "email") {
            validateInput(
                input,
                emailPattern.test(input.value),
                "Enter a valid email address"
            );
        }

        /* Password validation (except confirm password) */
        if (input.type === "password" && input.name !== "confirm_password") {
            validateInput(
                input,
                input.value.length >= 6,
                "Minimum 6 characters required"
            );
        }

        /* Confirm password validation */
        if (input.name === "confirm_password") {
            const password = input.form.querySelector("[name='new_password']");
            validateInput(
                input,
                input.value === password.value,
                "Passwords do not match"
            );
        }

    });

});
/* ================= AJAX FORM ================= */
document.querySelectorAll(".ajaxForm").forEach(form=>{
    form.addEventListener("submit",e=>{
        e.preventDefault();

        swalConfirm(
            "This change will log you out. Continue?",
            ()=>{
                fetch("profile_content.php",{
                    method:"POST",
                    body:new FormData(form),
                    headers:{"X-Requested-With":"XMLHttpRequest"}
                })
                .then(r=>r.text())
                .then(res=>{
                    if(res==="SESSION_EXPIRED") swalSessionExpired();
                    else if(res==="LOGOUT"){
                        Swal.fire({
                            icon:"success",
                            title:"Updated Successfully",
                            text:"Please login again"
                        }).then(()=>{
                            window.top.location="logout.php";
                        });
                    } else {
                        Swal.fire("Error",res,"error");
                    }
                });
            }
        );
    });
});

/* LOGO CONFIRM */
document.getElementById("logoForm").addEventListener("submit",e=>{
    e.preventDefault();

    swalConfirm(
        "Updating logo will log you out. Continue?",
        ()=>{
            fetch("profile_content.php",{
                method:"POST",
                body:new FormData(e.target),
                headers:{"X-Requested-With":"XMLHttpRequest"}
            })
            .then(r=>r.text())
            .then(res=>{
                if(res==="SESSION_EXPIRED") swalSessionExpired();
                else if(res==="LOGOUT"){
                    Swal.fire({
                        icon:"success",
                        title:"Logo Updated",
                        text:"Please login again"
                    }).then(()=>{
                        window.top.location="logout.php";
                    });
                } else {
                    Swal.fire("Error",res,"error");
                }
            });
        }
    );
});
</script>

</body>
</html>
